import React, { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { ActivityIndicator, AppState, Pressable, Text, View } from 'react-native';
import {
  ArrowLeft,
  AudioLines,
  LocateFixed,
  Map as MapIcon,
  MapPin,
  Navigation,
  Play,
  RefreshCw,
  Route,
  RotateCcw,
  Volume2,
  VolumeX,
  X,
} from 'lucide-react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { useNavigation, useRoute } from '@react-navigation/native';
import { StatusBar } from 'expo-status-bar';
import * as Location from 'expo-location';

import useMapAppearance from '../hooks/useMapAppearance';
import { voiceNavigation } from '../services/voiceNavigation';
import { Mapbox, MAP_DEFAULT_CAMERA, MAP_FALLBACK_STYLE_URL, MAP_STYLE_URL, QUERETARO_CENTER } from '../utils/mapbox';
import { fetchMapboxRoute } from '../utils/directions';
import { isValidCoordinate as validCoordinate } from '../utils/coordinates';

const PROFILES = [
  { id: 'walking', label: 'Caminar' },
  { id: 'cycling', label: 'Bicicleta' },
  { id: 'driving', label: 'Auto' },
  { id: 'driving-traffic', label: 'Tráfico' },
];
const OFF_ROUTE_MINIMUM_METERS = 45;
const ARRIVAL_METERS = 30;
const REROUTE_COOLDOWN_MS = 25_000;

const haversine = (origin, destination) => {
  if (!validCoordinate(origin) || !validCoordinate(destination)) return Infinity;
  const toRadians = (value) => Number(value) * Math.PI / 180;
  const latitude1 = toRadians(origin[1]);
  const latitude2 = toRadians(destination[1]);
  const deltaLatitude = latitude2 - latitude1;
  const deltaLongitude = toRadians(destination[0] - origin[0]);
  const a = Math.sin(deltaLatitude / 2) ** 2 + Math.cos(latitude1) * Math.cos(latitude2) * Math.sin(deltaLongitude / 2) ** 2;
  return 6_371_000 * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
};

const routeBounds = (feature) => {
  const coordinates = (feature?.geometry?.coordinates || []).filter(validCoordinate);
  if (coordinates.length < 2) return null;
  const longitudes = coordinates.map(([longitude]) => Number(longitude));
  const latitudes = coordinates.map(([, latitude]) => Number(latitude));
  const northEast = [Math.max(...longitudes), Math.max(...latitudes)];
  const southWest = [Math.min(...longitudes), Math.min(...latitudes)];
  return validCoordinate(northEast) && validCoordinate(southWest) ? { northEast, southWest } : null;
};

const routeMetrics = (feature) => {
  const coordinates = (feature?.geometry?.coordinates || []).filter(validCoordinate);
  const cumulative = [0];
  for (let index = 1; index < coordinates.length; index += 1) cumulative[index] = cumulative[index - 1] + haversine(coordinates[index - 1], coordinates[index]);
  return { coordinates, cumulative, total: cumulative.at(-1) || 0 };
};

const nearestProgress = (metrics, location) => {
  if (!validCoordinate(location) || metrics.coordinates.length < 2) return { traveled: 0, distance: Infinity, ratio: 0 };
  let best = { traveled: 0, distance: Infinity };
  const latitudeScale = 111_320;
  for (let index = 1; index < metrics.coordinates.length; index += 1) {
    const start = metrics.coordinates[index - 1];
    const end = metrics.coordinates[index];
    const longitudeScale = latitudeScale * Math.cos(Number(location[1]) * Math.PI / 180);
    const segmentX = (end[0] - start[0]) * longitudeScale;
    const segmentY = (end[1] - start[1]) * latitudeScale;
    const pointX = (location[0] - start[0]) * longitudeScale;
    const pointY = (location[1] - start[1]) * latitudeScale;
    const squaredLength = segmentX ** 2 + segmentY ** 2;
    const fraction = squaredLength ? Math.max(0, Math.min(1, (pointX * segmentX + pointY * segmentY) / squaredLength)) : 0;
    const projected = [start[0] + (end[0] - start[0]) * fraction, start[1] + (end[1] - start[1]) * fraction];
    const distance = haversine(location, projected);
    if (distance < best.distance) {
      const segmentDistance = metrics.cumulative[index] - metrics.cumulative[index - 1];
      best = { distance, traveled: metrics.cumulative[index - 1] + segmentDistance * fraction };
    }
  }
  return { ...best, ratio: metrics.total ? Math.max(0, Math.min(1, best.traveled / metrics.total)) : 0 };
};

const formatDistance = (meters) => {
  if (!Number.isFinite(meters)) return '—';
  if (meters < 1000) return `${Math.max(0, Math.round(meters / 10) * 10)} m`;
  return `${(meters / 1000).toFixed(meters < 10_000 ? 1 : 0)} km`;
};

const formatDuration = (seconds) => Number.isFinite(seconds) && seconds > 0 ? `${Math.max(1, Math.round(seconds / 60))} min` : '—';

const primaryBanner = (step) => step?.bannerInstructions?.[0]?.primary?.text || step?.instruction || 'Sigue la ruta marcada.';

export default function RouteNavigationScreen() {
  const navigation = useNavigation();
  const insets = useSafeAreaInsets();
  const point = useRoute().params?.point;
  const { lightPreset } = useMapAppearance();
  const cameraRef = useRef(null);
  const watcherRef = useRef(null);
  const requestRef = useRef({ id: 0, controller: null });
  const cacheRef = useRef(new globalThis.Map());
  const routeRef = useRef(null);
  const activeNavigationRef = useRef(false);
  const arrivedRef = useRef(false);
  const profileTimerRef = useRef(null);
  const fittedRouteRef = useRef(null);
  const offRouteSamplesRef = useRef(0);
  const lastRerouteRef = useRef(0);
  const lastLocationTimestampRef = useRef(0);

  const [origin, setOrigin] = useState(null);
  const [profile, setProfile] = useState('driving-traffic');
  const [routeData, setRouteData] = useState(null);
  const [loading, setLoading] = useState(true);
  const [rerouting, setRerouting] = useState(false);
  const [error, setError] = useState('');
  const [mapReady, setMapReady] = useState(false);
  const [mapError, setMapError] = useState('');
  const [usingFallbackStyle, setUsingFallbackStyle] = useState(false);
  const [mapKey, setMapKey] = useState(0);
  const [following, setFollowing] = useState(false);
  const [activeNavigation, setActiveNavigation] = useState(false);
  const [threeDimensional, setThreeDimensional] = useState(true);
  const voiceAvailable = voiceNavigation.isAvailable();
  const [muted, setMuted] = useState(() => !voiceAvailable || voiceNavigation.isMuted());
  const [arrived, setArrived] = useState(false);
  const [routeProgress, setRouteProgress] = useState(0);
  const [distanceRemaining, setDistanceRemaining] = useState(0);
  const [durationRemaining, setDurationRemaining] = useState(0);
  const [currentStepIndex, setCurrentStepIndex] = useState(0);
  const [offRoute, setOffRoute] = useState(false);
  const [followZoom, setFollowZoom] = useState(17);

  const destination = useMemo(() => [Number(point?.longitud), Number(point?.latitud)], [point?.longitud, point?.latitud]);
  const metrics = useMemo(() => routeMetrics(routeData?.geometry), [routeData?.geometry]);
  const currentStep = routeData?.steps?.[currentStepIndex] || routeData?.steps?.[0] || null;
  const alternatives = routeData?.alternatives || [];
  const mapConfig = useMemo(() => ({ lightPreset, show3dBuildings: true, show3dObjects: true, show3dFacades: true, show3dLandmarks: true, show3dTrees: true, showRoadLabels: true, showPlaceLabels: true, showPointOfInterestLabels: false }), [lightPreset]);

  useEffect(() => { routeRef.current = routeData; }, [routeData]);
  useEffect(() => { activeNavigationRef.current = activeNavigation; }, [activeNavigation]);
  useEffect(() => { voiceNavigation.hydrate().then(() => setMuted(!voiceAvailable || voiceNavigation.isMuted())); }, [voiceAvailable]);

  const fitRoute = useCallback((route, force = false) => {
    const bounds = routeBounds(route?.geometry);
    if (!bounds || (!force && fittedRouteRef.current === route?.id)) return;
    fittedRouteRef.current = route?.id;
    requestAnimationFrame(() => cameraRef.current?.fitBounds(bounds.northEast, bounds.southWest, [105, 44, 330, 44], 700));
  }, []);

  const activateRoute = useCallback((result, { reroute = false } = {}) => {
    routeRef.current = result;
    setRouteData(result);
    setRouteProgress(0);
    setDistanceRemaining(result.distanceMeters);
    setDurationRemaining(result.durationSeconds);
    setCurrentStepIndex(0);
    setOffRoute(false);
    offRouteSamplesRef.current = 0;
    fittedRouteRef.current = null;
    voiceNavigation.resetRoute();
    if (activeNavigationRef.current) {
      voiceNavigation.enqueue({ id: reroute ? `reroute:${result.id}` : `start:${result.id}`, text: reroute ? 'Recalculando. Sigue la nueva ruta.' : `Iniciando ruta hacia ${point?.nombre || 'tu destino'}.` }, { interrupt: true });
    }
  }, [point?.nombre]);

  const loadRoute = useCallback(async (currentOrigin, currentProfile, options = {}) => {
    if (!validCoordinate(currentOrigin) || !validCoordinate(destination)) {
      setLoading(false);
      setError('No fue posible determinar coordenadas válidas para la ruta.');
      return;
    }
    const requestId = requestRef.current.id + 1;
    requestRef.current.controller?.abort();
    const controller = new AbortController();
    requestRef.current = { id: requestId, controller };
    setLoading(!routeRef.current);
    setRerouting(Boolean(options.reroute));
    setError('');
    const cacheKey = `${currentProfile}:${currentOrigin.map((value) => Number(value).toFixed(4)).join(',')}:${destination.join(',')}`;
    try {
      const cached = options.reroute ? null : cacheRef.current.get(cacheKey);
      const result = cached || await fetchMapboxRoute(currentOrigin, destination, currentProfile, { signal: controller.signal, alternatives: !options.reroute });
      if (!cached) cacheRef.current.set(cacheKey, result);
      if (requestId !== requestRef.current.id || controller.signal.aborted) return;
      activateRoute(result, options);
    } catch (requestError) {
      if (controller.signal.aborted || requestId !== requestRef.current.id) return;
      setError(requestError.message || 'No fue posible calcular la ruta.');
    } finally {
      if (requestId === requestRef.current.id) {
        setLoading(false);
        setRerouting(false);
      }
    }
  }, [activateRoute, destination]);

  const updateNavigationProgress = useCallback((coordinates, accuracy = 0, speed = 0) => {
    const activeRoute = routeRef.current;
    if (!activeRoute?.geometry) return;
    const activeMetrics = routeMetrics(activeRoute.geometry);
    const progress = nearestProgress(activeMetrics, coordinates);
    const remaining = Math.max(0, activeRoute.distanceMeters * (1 - progress.ratio));
    setRouteProgress(progress.ratio);
    setDistanceRemaining(remaining);
    setDurationRemaining(Math.max(0, activeRoute.durationSeconds * (1 - progress.ratio)));

    const steps = activeRoute.steps || [];
    const stepDistances = steps.map((step) => nearestProgress(activeMetrics, step?.maneuver?.location).traveled);
    let nextStep = steps.length ? steps.length - 1 : 0;
    for (let index = 0; index < stepDistances.length; index += 1) {
      if (stepDistances[index] >= progress.traveled - 10) { nextStep = index; break; }
    }
    setCurrentStepIndex(nextStep);
    const step = steps[nextStep];
    const distanceToManeuver = Math.max(0, (stepDistances[nextStep] || progress.traveled) - progress.traveled);
    const speedMetersPerSecond = Math.max(0, Number(speed) || 0);
    setFollowZoom(distanceToManeuver < 120 ? 17.5 : speedMetersPerSecond > 16 ? 15.8 : speedMetersPerSecond > 8 ? 16.4 : 17);
    const voiceItems = step?.voiceInstructions || [];
    const eligible = voiceItems
      .map((voice, index) => ({ ...voice, index }))
      .filter((voice) => distanceToManeuver <= Number(voice.distanceAlongGeometry || 0) + Math.max(12, accuracy))
      .sort((a, b) => Number(a.distanceAlongGeometry || 0) - Number(b.distanceAlongGeometry || 0))[0];
    if (activeNavigationRef.current && eligible?.announcement) {
      voiceNavigation.enqueue({ id: `${activeRoute.id}:${step.id}:voice:${eligible.index}`, text: eligible.announcement });
    }

    if (haversine(coordinates, destination) <= ARRIVAL_METERS && !arrivedRef.current) {
      arrivedRef.current = true;
      setArrived(true);
      setActiveNavigation(false);
      setFollowing(false);
      voiceNavigation.enqueue({ id: `arrival:${activeRoute.id}`, text: `Has llegado a ${point?.nombre || 'tu destino'}.` }, { interrupt: true });
      watcherRef.current?.remove();
      watcherRef.current = null;
      return;
    }

    const offRouteThreshold = Math.max(OFF_ROUTE_MINIMUM_METERS, Number(accuracy || 0) * 1.5, speedMetersPerSecond > 18 ? 65 : 0);
    const isOffRoute = progress.distance > offRouteThreshold;
    offRouteSamplesRef.current = isOffRoute ? offRouteSamplesRef.current + 1 : 0;
    setOffRoute(offRouteSamplesRef.current >= 2);
    const now = Date.now();
    if (activeNavigationRef.current && offRouteSamplesRef.current >= 2 && now - lastRerouteRef.current >= REROUTE_COOLDOWN_MS) {
      lastRerouteRef.current = now;
      offRouteSamplesRef.current = 0;
      voiceNavigation.cancelObsolete();
      voiceNavigation.enqueue({ id: `rerouting:${now}`, text: 'Te has desviado de la ruta. Recalculando.' }, { interrupt: true });
      void loadRoute(coordinates, activeRoute.profile, { reroute: true });
    }
  }, [destination, loadRoute, point?.nombre]);

  useEffect(() => {
    let active = true;
    if (!validCoordinate(destination)) {
      setLoading(false);
      setError('Este punto no tiene coordenadas válidas.');
      return undefined;
    }
    (async () => {
      const permission = await Location.requestForegroundPermissionsAsync();
      if (!active) return;
      if (permission.status !== 'granted') {
        setError('Permite el acceso a tu ubicación para calcular la ruta.');
        setLoading(false);
        return;
      }
      const current = await Location.getCurrentPositionAsync({ accuracy: Location.Accuracy.High });
      if (!active) return;
      const coordinates = [current.coords.longitude, current.coords.latitude];
      setOrigin(coordinates);
      await loadRoute(coordinates, 'driving-traffic');
      if (!active) return;
      watcherRef.current = await Location.watchPositionAsync(
        { accuracy: Location.Accuracy.High, distanceInterval: 10, timeInterval: 5000 },
        ({ coords, timestamp }) => {
          if (!validCoordinate([coords.longitude, coords.latitude])) return;
          const locationTimestamp = Number(timestamp) || Date.now();
          if (Number(coords.accuracy) > 120 || locationTimestamp <= lastLocationTimestampRef.current) return;
          lastLocationTimestampRef.current = locationTimestamp;
          const nextOrigin = [coords.longitude, coords.latitude];
          setOrigin(nextOrigin);
          updateNavigationProgress(nextOrigin, coords.accuracy, coords.speed);
        },
      );
    })().catch(() => {
      if (active) {
        setError('No fue posible obtener tu ubicación.');
        setLoading(false);
      }
    });
    return () => {
      active = false;
      clearTimeout(profileTimerRef.current);
      requestRef.current.controller?.abort();
      watcherRef.current?.remove();
      voiceNavigation.resetRoute();
    };
  }, [destination, loadRoute, updateNavigationProgress]);

  useEffect(() => {
    const subscription = AppState.addEventListener('change', (state) => {
      if (state !== 'active') voiceNavigation.cancelObsolete();
    });
    return () => subscription.remove();
  }, []);

  useEffect(() => {
    if (mapReady && routeData && !following) fitRoute(routeData);
  }, [fitRoute, following, mapReady, routeData]);

  const selectProfile = (nextProfile) => {
    if (nextProfile === profile || !origin) return;
    setProfile(nextProfile);
    clearTimeout(profileTimerRef.current);
    requestRef.current.controller?.abort();
    profileTimerRef.current = setTimeout(() => void loadRoute(origin, nextProfile), 250);
  };

  const startNavigation = () => {
    if (!routeData) return;
    arrivedRef.current = false;
    setArrived(false);
    setActiveNavigation(true);
    setFollowing(true);
    voiceNavigation.resetRoute();
    voiceNavigation.enqueue({ id: `start:${routeData.id}`, text: `Iniciando ruta hacia ${point?.nombre || 'tu destino'}. ${primaryBanner(routeData.steps?.[0])}` }, { interrupt: true });
  };

  const toggleMute = () => { if (voiceAvailable) setMuted(voiceNavigation.setMuted(!muted)); };
  const useAlternative = () => {
    const alternative = alternatives[0];
    if (!alternative) return;
    activateRoute({ ...alternative, alternatives: [routeData, ...alternatives.slice(1)] });
  };

  const distance = formatDistance(activeNavigation ? distanceRemaining : routeData?.distanceMeters);
  const eta = formatDuration(activeNavigation ? durationRemaining : routeData?.durationSeconds);
  const navigationPaused = activeNavigation && !following;
  const cameraCenter = validCoordinate(destination) ? destination : QUERETARO_CENTER;
  const instruction = arrived ? `Llegaste a ${point?.nombre || 'tu destino'}` : primaryBanner(currentStep);

  return (
    <View className="flex-1 bg-emerald-50">
      <StatusBar style={lightPreset === 'night' ? 'light' : 'dark'} translucent backgroundColor="transparent" />
      <Mapbox.MapView
        key={mapKey}
        style={{ flex: 1 }}
        styleURL={usingFallbackStyle ? MAP_FALLBACK_STYLE_URL : MAP_STYLE_URL}
        onDidFinishLoadingStyle={() => { setMapReady(true); setMapError(''); }}
        onDidFinishLoadingMap={() => { setMapReady(true); setMapError(''); }}
        onMapLoadingError={() => setMapError('No fue posible cargar el mapa de la ruta.')}
        onTouchStart={() => { if (activeNavigation) setFollowing(false); }}
      >
        {!usingFallbackStyle ? <Mapbox.StyleImport id="basemap" existing config={mapConfig} /> : null}
        <Mapbox.Camera
          ref={cameraRef}
          defaultSettings={{ ...MAP_DEFAULT_CAMERA, centerCoordinate: cameraCenter }}
          pitch={following ? (threeDimensional ? 60 : 0) : undefined}
          followUserLocation={following}
          followUserMode={following ? 'course' : 'normal'}
          followZoomLevel={followZoom}
          followPitch={threeDimensional ? 60 : 0}
          animationMode="easeTo"
          animationDuration={650}
        />
        {origin ? <Mapbox.LocationPuck puckBearingEnabled puckBearing="heading" /> : null}
        {profile === 'driving-traffic' ? <Mapbox.VectorSource id="live-traffic" url="mapbox://mapbox.mapbox-traffic-v1"><Mapbox.LineLayer id="live-traffic-lines" sourceLayerID="traffic" slot={usingFallbackStyle ? undefined : 'middle'} minZoomLevel={7} style={{ lineColor: ['match', ['get', 'congestion'], 'low', '#22C55E', 'moderate', '#F59E0B', 'heavy', '#F97316', 'severe', '#DC2626', '#94A3B8'], lineOpacity: 0.88, lineWidth: ['interpolate', ['linear'], ['zoom'], 7, 1, 12, 2.5, 17, 6], lineCap: 'round', lineJoin: 'round' }} /></Mapbox.VectorSource> : null}
        {alternatives.map((alternative) => <Mapbox.ShapeSource key={alternative.id} id={`alternative-${alternative.id}`} shape={alternative.geometry}><Mapbox.LineLayer id={`alternative-line-${alternative.id}`} slot={usingFallbackStyle ? undefined : 'middle'} style={{ lineColor: '#64748B', lineOpacity: 0.55, lineWidth: 5, lineCap: 'round', lineJoin: 'round' }} /></Mapbox.ShapeSource>)}
        {routeData ? <Mapbox.ShapeSource id="active-route" shape={routeData.geometry}><Mapbox.LineLayer id="active-route-casing" slot={usingFallbackStyle ? undefined : 'middle'} style={{ lineColor: lightPreset === 'night' ? '#0F172A' : '#FFFFFF', lineWidth: 11, lineCap: 'round', lineJoin: 'round' }} /><Mapbox.LineLayer id="active-route-line" slot={usingFallbackStyle ? undefined : 'middle'} style={{ lineColor: '#10B981', lineWidth: 6.5, lineCap: 'round', lineJoin: 'round', lineEmissiveStrength: lightPreset === 'night' ? 0.55 : 0.1 }} /></Mapbox.ShapeSource> : null}
        {validCoordinate(destination) ? <Mapbox.PointAnnotation id={`route-destination-${point?.id || 'point'}`} coordinate={destination}><View className="h-11 w-11 items-center justify-center rounded-full border-4 border-white bg-emerald-700"><MapPin color="white" size={20} /></View></Mapbox.PointAnnotation> : null}
      </Mapbox.MapView>

      {!mapReady && !mapError ? <View pointerEvents="none" className="absolute left-5 right-5 flex-row items-center rounded-2xl bg-white/90 p-4" style={{ top: Math.max(insets.top + 70, 88) }}><ActivityIndicator color="#059669" /><Text className="ml-3 font-bold text-slate-700">Preparando mapa 3D…</Text></View> : null}
      {mapError ? <View className="absolute left-5 right-5 rounded-2xl border border-red-200 bg-red-50 p-4" style={{ top: Math.max(insets.top + 70, 88) }}><Text className="text-center font-bold text-red-700">{mapError}</Text><Pressable onPress={() => { setUsingFallbackStyle(true); setMapReady(false); setMapError(''); setMapKey((value) => value + 1); }} className="mt-3 min-h-11 items-center justify-center rounded-xl bg-emerald-700"><Text className="font-black text-white">{usingFallbackStyle ? 'Reintentar mapa' : 'Usar mapa compatible'}</Text></Pressable></View> : null}

      <View pointerEvents="box-none" className="absolute inset-0 justify-between" style={{ paddingTop: Math.max(insets.top + 8, 16), paddingBottom: Math.max(insets.bottom + 8, 16) }}>
        <View className="px-4 pt-2">
          {activeNavigation ? <View className="rounded-[24px] bg-emerald-800 p-4 shadow-lg"><View className="flex-row items-start"><Navigation color="white" size={25} /><View className="ml-3 flex-1"><Text className="text-2xl font-black leading-7 text-white">{instruction}</Text><Text className="mt-1 font-semibold text-emerald-100">{currentStep?.name || point?.nombre || 'Ruta ZeroWaste'} · {distance}</Text></View><Pressable onPress={() => navigation.goBack()} className="h-10 w-10 items-center justify-center rounded-full bg-white/15" accessibilityLabel="Cerrar navegación"><X color="white" size={20} /></Pressable></View></View> : <Pressable onPress={() => navigation.goBack()} className="h-12 w-12 items-center justify-center rounded-full bg-white shadow-md" accessibilityLabel="Volver"><ArrowLeft color="#0F172A" size={21} /></Pressable>}
          {rerouting ? <View className="mt-2 self-center flex-row items-center rounded-full bg-amber-500 px-4 py-2"><RefreshCw color="#fff" size={15} /><Text className="ml-2 text-xs font-black text-white">Recalculando ruta…</Text></View> : offRoute ? <View className="mt-2 self-center rounded-full bg-amber-500 px-4 py-2"><Text className="text-xs font-black text-white">Fuera de ruta</Text></View> : navigationPaused ? <View className="mt-2 self-center rounded-full bg-slate-800 px-4 py-2"><Text className="text-xs font-black text-white">Seguimiento pausado</Text></View> : null}
        </View>

        <View className="items-end px-3">
          {activeNavigation ? <View className="mb-2 gap-2"><MapControl icon={LocateFixed} label="Recentrar" onPress={() => setFollowing(true)} /><MapControl icon={muted ? VolumeX : Volume2} label={!voiceAvailable ? 'Voz pendiente de nueva build' : muted ? 'Activar voz' : 'Silenciar voz'} onPress={toggleMute} /><MapControl icon={RotateCcw} label="Repetir instrucción" onPress={() => voiceNavigation.repeat()} /><MapControl icon={threeDimensional ? MapIcon : Route} label={threeDimensional ? 'Vista superior' : 'Vista 3D'} onPress={() => { setThreeDimensional((value) => !value); setFollowing(true); }} /></View> : null}
          <View className="mb-2 w-full rounded-[26px] border border-slate-100 bg-white p-4 shadow-xl">
            <View className="flex-row items-center"><View className="h-10 w-10 items-center justify-center rounded-full bg-emerald-50"><Route color="#047857" size={20} /></View><View className="ml-3 flex-1"><Text className="text-xs font-bold uppercase tracking-wider text-slate-400">{activeNavigation ? `${Math.round(routeProgress * 100)}% de la ruta` : 'Ruta hacia'}</Text><Text className="font-black text-slate-950" numberOfLines={1}>{point?.nombre || 'Punto ZeroWaste'}</Text></View></View>
            {!activeNavigation ? <View className="mt-3 flex-row gap-2">{PROFILES.map((item) => <Pressable key={item.id} onPress={() => selectProfile(item.id)} className={`min-h-10 flex-1 items-center justify-center rounded-xl ${profile === item.id ? 'bg-emerald-700' : 'bg-slate-100'}`} accessibilityState={{ selected: profile === item.id }}><Text className={`text-[11px] font-black ${profile === item.id ? 'text-white' : 'text-slate-600'}`}>{item.label}</Text></Pressable>)}</View> : null}
            {loading ? <View className="mt-4 flex-row items-center"><ActivityIndicator color="#059669" /><Text className="ml-3 font-bold text-slate-600">Calculando ruta…</Text></View> : error && !routeData ? <View className="mt-4"><Text className="font-bold leading-5 text-red-700">{error}</Text><Pressable onPress={() => loadRoute(origin, profile)} className="mt-3 min-h-11 items-center justify-center rounded-xl bg-emerald-700"><Text className="font-black text-white">Reintentar</Text></Pressable></View> : <><View className="mt-4 flex-row"><Metric label="DISTANCIA" value={distance} /><Metric label="TIEMPO" value={eta} /><Metric label="LLEGADA" value={routeData ? new Date(Date.now() + (activeNavigation ? durationRemaining : routeData.durationSeconds) * 1000).toLocaleTimeString('es-MX', { hour: 'numeric', minute: '2-digit' }) : '—'} /></View><View className="mt-3 flex-row items-start rounded-2xl bg-emerald-50 p-3"><AudioLines color="#047857" size={18} /><Text className="ml-3 flex-1 font-semibold leading-5 text-emerald-950">{instruction}</Text></View>{error ? <Text className="mt-2 text-xs font-bold text-amber-800">{error} Se conserva la última ruta.</Text> : null}</>}
            {!activeNavigation && alternatives.length ? <Pressable onPress={useAlternative} className="mt-3 min-h-11 items-center justify-center rounded-xl bg-slate-100"><Text className="font-black text-slate-700">Usar ruta alternativa ({alternatives.length})</Text></Pressable> : null}
            <View className="mt-3 flex-row gap-2">{activeNavigation ? <><Pressable onPress={() => setFollowing(true)} className="min-h-12 flex-1 flex-row items-center justify-center rounded-2xl border border-emerald-700"><LocateFixed color="#047857" size={18} /><Text className="ml-2 font-black text-emerald-800">Recentrar</Text></Pressable><Pressable onPress={() => navigation.goBack()} className="min-h-12 flex-1 items-center justify-center rounded-2xl bg-red-50"><Text className="font-black text-red-700">Salir</Text></Pressable></> : <><Pressable onPress={() => routeData && fitRoute(routeData, true)} disabled={!routeData} className="min-h-12 flex-1 flex-row items-center justify-center rounded-2xl border border-emerald-700 disabled:opacity-40"><MapIcon color="#047857" size={18} /><Text className="ml-2 font-black text-emerald-800">Vista previa</Text></Pressable><Pressable onPress={startNavigation} disabled={!routeData} className="min-h-12 flex-1 flex-row items-center justify-center rounded-2xl bg-emerald-700 disabled:opacity-40"><Play color="white" size={17} /><Text className="ml-2 font-black text-white">Iniciar</Text></Pressable></>}</View>
          </View>
        </View>
      </View>
    </View>
  );
}

function Metric({ label, value }) {
  return <View className="flex-1"><Text className="text-[10px] font-bold text-slate-400">{label}</Text><Text className="mt-1 text-lg font-black text-slate-900" numberOfLines={1}>{value}</Text></View>;
}

function MapControl({ icon: Icon, label, onPress }) {
  return <Pressable onPress={onPress} className="h-12 w-12 items-center justify-center rounded-full border border-white bg-white shadow-md" accessibilityLabel={label}><Icon color="#047857" size={20} /></Pressable>;
}
