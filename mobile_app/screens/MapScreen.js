import React, { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import {
  AccessibilityInfo,
  ActivityIndicator,
  FlatList,
  Linking,
  StyleSheet,
  Text,
  TextInput,
  TouchableOpacity,
  View,
} from 'react-native';
import { LocateFixed, MapPin, Navigation, QrCode, Search, Truck, X } from 'lucide-react-native';
import { useNavigation } from '@react-navigation/native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import * as Location from 'expo-location';
import { StatusBar } from 'expo-status-bar';

import { api } from '../api/axios';
import RemoteImage from '../components/ui/RemoteImage';
import { useZeroWasteDialog } from '../components/ui/ZeroWasteDialog';
import useMapAppearance from '../hooks/useMapAppearance';
import { useAuth } from '../store/useAuth';
import {
  HAS_VALID_MAPBOX_TOKEN,
  initializeMapbox,
  MAP_DEFAULT_CAMERA,
  Mapbox,
} from '../utils/mapbox';
import { normalizeMediaUrl } from '../utils/media';
import { isValidCoordinate as validCoordinate } from '../utils/coordinates';

const MAP_LOAD_TIMEOUT_MS = 15_000;
const SEARCH_DEBOUNCE_MS = 300;
const CARD_WIDTH = 304;
const CARD_SNAP = CARD_WIDTH + 12;
const SAFE_MAP_LOAD_ERROR = 'No fue posible cargar el mapa. Revisa tu conexión e inténtalo nuevamente.';
const MAP_OVERVIEW_CAMERA = Object.freeze({ ...MAP_DEFAULT_CAMERA, pitch: 0, heading: 0 });

const normalizePoint = (point) => {
  const latitude = Number(point?.latitud ?? point?.latitude);
  const longitude = Number(point?.longitud ?? point?.longitude);
  if (!validCoordinate([longitude, latitude])) return null;
  return {
    ...point,
    id: String(point.id),
    latitud: latitude,
    longitud: longitude,
  };
};

const searchableText = (point) => [
  point.nombre,
  point.direccion,
  point.colonia,
  point.municipio,
  point.materiales,
  point.etiquetas,
  point.horario,
  point.tipo,
].flatMap((value) => (Array.isArray(value) ? value : [value]))
  .filter(Boolean)
  .join(' ')
  .normalize('NFD')
  .replace(/[\u0300-\u036f]/g, '')
  .toLocaleLowerCase('es-MX');

const distanceMeters = (origin, destination) => {
  if (!validCoordinate(origin) || !validCoordinate(destination)) return null;
  const [longitude1, latitude1] = origin.map((value) => Number(value) * Math.PI / 180);
  const [longitude2, latitude2] = destination.map((value) => Number(value) * Math.PI / 180);
  const deltaLatitude = latitude2 - latitude1;
  const deltaLongitude = longitude2 - longitude1;
  const a = Math.sin(deltaLatitude / 2) ** 2
    + Math.cos(latitude1) * Math.cos(latitude2) * Math.sin(deltaLongitude / 2) ** 2;
  return 6_371_000 * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
};

const formatDistance = (meters) => {
  if (!Number.isFinite(meters)) return '';
  if (meters < 1000) return `${Math.max(1, Math.round(meters / 10) * 10)} m`;
  return `${(meters / 1000).toFixed(meters < 10_000 ? 1 : 0)} km`;
};

const pointCollection = (points) => ({
  type: 'FeatureCollection',
  features: points.map((point) => ({
    type: 'Feature',
    id: point.id,
    properties: { id: point.id, nombre: point.nombre || 'Punto ZeroWaste' },
    geometry: { type: 'Point', coordinates: [point.longitud, point.latitud] },
  })),
});

export default function MapScreen() {
  const navigation = useNavigation();
  const insets = useSafeAreaInsets();
  const { user } = useAuth();
  const { showDialog } = useZeroWasteDialog();
  const { lightPreset } = useMapAppearance();
  const cameraRef = useRef(null);
  const pointSourceRef = useRef(null);
  const cardListRef = useRef(null);
  const mapReadyRef = useRef(false);
  const locationRequestRef = useRef(null);
  const searchTimerRef = useRef(null);
  const searchGenerationRef = useRef(0);

  const tokenReady = HAS_VALID_MAPBOX_TOKEN;
  const [mapMounted, setMapMounted] = useState(false);
  const [styleLoading, setStyleLoading] = useState(tokenReady);
  const [mapReady, setMapReady] = useState(false);
  const [mapKey, setMapKey] = useState(0);
  const [usingFallbackStyle, setUsingFallbackStyle] = useState(false);
  const [mapError, setMapError] = useState(tokenReady ? '' : 'El mapa interactivo no está disponible en esta compilación. Puedes consultar todos los puntos en el modo listado.');
  const [points, setPoints] = useState([]);
  const [pointsLoading, setPointsLoading] = useState(true);
  const [pointsReady, setPointsReady] = useState(false);
  const [pointsError, setPointsError] = useState('');
  const [permissionState, setPermissionState] = useState('unknown');
  const [locationError, setLocationError] = useState('');
  const [userLocation, setUserLocation] = useState(null);
  const [query, setQuery] = useState('');
  const [debouncedQuery, setDebouncedQuery] = useState('');
  const [searching, setSearching] = useState(false);
  const [results, setResults] = useState([]);
  const [selectedResult, setSelectedResult] = useState(null);
  const [searchError, setSearchError] = useState('');
  const [emptySearch, setEmptySearch] = useState(false);
  const [reduceMotion, setReduceMotion] = useState(false);

  const isCollector = user?.rol === 'recolector' || user?.is_admin;
  const collectorOnly = user?.rol === 'recolector' && !user?.is_admin;
  const mapPoints = debouncedQuery ? results : points;
  const geojson = useMemo(() => pointCollection(mapPoints), [mapPoints]);
  const pointById = useMemo(() => new Map(points.map((point) => [String(point.id), point])), [points]);
  const selectedFeature = useMemo(
    () => selectedResult ? pointCollection([selectedResult]) : pointCollection([]),
    [selectedResult],
  );

  const animationDuration = reduceMotion ? 0 : 700;
  const fitPoints = useCallback((items) => {
    const coordinates = items.map((point) => [point.longitud, point.latitud]).filter(validCoordinate);
    if (!coordinates.length) return;
    if (coordinates.length === 1) {
      cameraRef.current?.setCamera({
        centerCoordinate: coordinates[0],
        zoomLevel: 15.5,
        pitch: 0,
        heading: 0,
        animationDuration,
      });
      return;
    }
    const longitudes = coordinates.map(([longitude]) => longitude);
    const latitudes = coordinates.map(([, latitude]) => latitude);
    const northEast = [Math.max(...longitudes), Math.max(...latitudes)];
    const southWest = [Math.min(...longitudes), Math.min(...latitudes)];
    if (!validCoordinate(northEast) || !validCoordinate(southWest)) return;
    cameraRef.current?.fitBounds(northEast, southWest, [150, 48, 270, 48], animationDuration);
  }, [animationDuration]);

  const selectPoint = useCallback((point, { moveCamera = true, scrollCard = true } = {}) => {
    if (!point) return;
    setSelectedResult(point);
    if (moveCamera) fitPoints([point]);
    if (scrollCard) {
      const index = mapPoints.findIndex((candidate) => candidate.id === point.id);
      if (index >= 0) cardListRef.current?.scrollToIndex({ index, animated: !reduceMotion, viewPosition: 0.5 });
    }
  }, [fitPoints, mapPoints, reduceMotion]);

  const handleMapReady = useCallback(() => {
    mapReadyRef.current = true;
    setMapReady(true);
    setStyleLoading(false);
    setMapError('');
  }, []);

  const handleMapLoadingError = useCallback((event) => {
    const status = Number(event?.error?.status || event?.status || 0);
    if (typeof __DEV__ !== 'undefined' && __DEV__) {
      console.warn(`[map] Mapbox reportó un error de carga${status ? ` (HTTP ${status})` : ''}.`);
    }
    setStyleLoading(false);
    setMapError(SAFE_MAP_LOAD_ERROR);
  }, []);

  const retryMap = useCallback(() => {
    if (!tokenReady) return;
    setUsingFallbackStyle(true);
    mapReadyRef.current = false;
    setMapMounted(false);
    setMapReady(false);
    setMapError('');
    setStyleLoading(true);
    setMapKey((value) => value + 1);
  }, [tokenReady]);

  const fetchPoints = useCallback(async ({ preserve = false } = {}) => {
    setPointsLoading(true);
    setPointsError('');
    try {
      const response = await api.get('/mapa/puntos');
      const seen = new Set();
      const validPoints = (Array.isArray(response.data) ? response.data : [])
        .map(normalizePoint)
        .filter((point) => point && point.activo !== false && !seen.has(point.id) && seen.add(point.id));
      setPoints(validPoints);
      setPointsReady(true);
      setSelectedResult((current) => validPoints.find((point) => point.id === current?.id) || validPoints[0] || null);
    } catch (error) {
      if (!preserve) setPoints([]);
      setPointsError(error.userMessage || 'No se pudieron cargar los puntos de reciclaje.');
    } finally {
      setPointsLoading(false);
    }
  }, []);

  const requestLocation = useCallback(async () => {
    if (locationRequestRef.current) return locationRequestRef.current;
    const request = (async () => {
      setPermissionState('requesting');
      setLocationError('');
      try {
        const permission = await Location.requestForegroundPermissionsAsync();
        if (permission.status !== 'granted') {
          setPermissionState('denied');
          return null;
        }
        const location = await Location.getCurrentPositionAsync({ accuracy: Location.Accuracy.Balanced });
        const coordinates = [location.coords.longitude, location.coords.latitude];
        if (!validCoordinate(coordinates)) throw new Error('invalid-location');
        setUserLocation(coordinates);
        setPermissionState('granted');
        return coordinates;
      } catch {
        setPermissionState('unavailable');
        setLocationError('No fue posible obtener tu ubicación. El mapa y los puntos siguen disponibles.');
        return null;
      }
    })();
    locationRequestRef.current = request;
    try {
      return await request;
    } finally {
      locationRequestRef.current = null;
    }
  }, []);

  const centerUser = useCallback(async () => {
    const coordinates = userLocation || await requestLocation();
    if (!coordinates) return;
    cameraRef.current?.setCamera({
      centerCoordinate: coordinates,
      zoomLevel: 15.5,
      pitch: 0,
      heading: 0,
      animationDuration,
    });
  }, [animationDuration, requestLocation, userLocation]);

  const clearSearch = useCallback(() => {
    searchGenerationRef.current += 1;
    clearTimeout(searchTimerRef.current);
    setQuery('');
    setDebouncedQuery('');
    setSearching(false);
    setSearchError('');
    setEmptySearch(false);
    setResults([]);
    setSelectedResult(points[0] || null);
    if (points.length) fitPoints(points);
  }, [fitPoints, points]);

  useEffect(() => {
    void fetchPoints();
    void requestLocation();
  }, []);

  useEffect(() => {
    let active = true;
    if (tokenReady) {
      initializeMapbox().catch(() => {
        if (active) {
          setStyleLoading(false);
          setMapError('No fue posible inicializar Mapbox en esta compilación.');
        }
      });
    }
    return () => { active = false; };
  }, [mapKey, tokenReady]);

  useEffect(() => {
    if (!tokenReady || mapReadyRef.current) return undefined;
    const timeout = setTimeout(() => {
      if (!mapReadyRef.current) {
        setStyleLoading(false);
        setMapError(SAFE_MAP_LOAD_ERROR);
      }
    }, MAP_LOAD_TIMEOUT_MS);
    return () => clearTimeout(timeout);
  }, [mapKey, tokenReady]);

  useEffect(() => {
    AccessibilityInfo.isReduceMotionEnabled().then(setReduceMotion);
    const subscription = AccessibilityInfo.addEventListener('reduceMotionChanged', setReduceMotion);
    return () => subscription.remove();
  }, []);

  useEffect(() => {
    const generation = searchGenerationRef.current + 1;
    searchGenerationRef.current = generation;
    clearTimeout(searchTimerRef.current);
    const trimmed = query.trim();
    if (!trimmed) {
      setDebouncedQuery('');
      setResults([]);
      setSearching(false);
      setSearchError('');
      setEmptySearch(false);
      return undefined;
    }
    setSearching(true);
    setSearchError('');
    searchTimerRef.current = setTimeout(() => {
      if (generation !== searchGenerationRef.current) return;
      try {
        const needle = trimmed.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLocaleLowerCase('es-MX');
        const nextResults = points.filter((point) => searchableText(point).includes(needle));
        setDebouncedQuery(trimmed);
        setResults(nextResults);
        setEmptySearch(nextResults.length === 0);
        setSelectedResult(nextResults[0] || null);
        if (nextResults.length) fitPoints(nextResults);
      } catch {
        setSearchError('No fue posible completar la búsqueda.');
      } finally {
        setSearching(false);
      }
    }, SEARCH_DEBOUNCE_MS);
    return () => clearTimeout(searchTimerRef.current);
  }, [fitPoints, points, query]);

  useEffect(() => () => {
    searchGenerationRef.current += 1;
    clearTimeout(searchTimerRef.current);
  }, []);

  const handleSourcePress = useCallback(async (event) => {
    const feature = event?.features?.[0];
    if (!feature) return;
    const coordinates = feature.geometry?.coordinates;
    if (feature.properties?.cluster) {
      try {
        const zoomLevel = await pointSourceRef.current?.getClusterExpansionZoom(feature);
        if (validCoordinate(coordinates)) cameraRef.current?.setCamera({ centerCoordinate: coordinates, zoomLevel: Math.min(Number(zoomLevel) || 16, 18), pitch: 0, heading: 0, animationDuration });
      } catch {
        if (validCoordinate(coordinates)) cameraRef.current?.setCamera({ centerCoordinate: coordinates, zoomLevel: 16, animationDuration });
      }
      return;
    }
    const point = pointById.get(String(feature.properties?.id ?? feature.id));
    if (point) selectPoint(point);
  }, [animationDuration, pointById, selectPoint]);

  const topSafeArea = Math.max(insets.top + 14, 44);
  const bottomSafeArea = Math.max(insets.bottom + 14, 24);
  const showPermissionNotice = mapReady && ['denied', 'unavailable'].includes(permissionState) && !pointsError;

  return (
    <View className="flex-1 bg-emerald-50">
      <StatusBar style="dark" translucent={false} backgroundColor="#ECFDF5" />
      {tokenReady ? (
        <Mapbox.MapView
          key={mapKey}
          style={styles.map}
          styleURL={usingFallbackStyle ? Mapbox.StyleURL.Street : lightPreset === 'night' ? Mapbox.StyleURL.Dark : Mapbox.StyleURL.Street}
          compassEnabled={false}
          scaleBarEnabled={false}
          onLayout={() => setMapMounted(true)}
          onWillStartLoadingMap={() => setStyleLoading(true)}
          onDidFinishLoadingStyle={handleMapReady}
          onDidFinishLoadingMap={handleMapReady}
          onMapLoadingError={handleMapLoadingError}
        >
          <Mapbox.Camera ref={cameraRef} defaultSettings={MAP_OVERVIEW_CAMERA} />
          {permissionState === 'granted' ? <Mapbox.LocationPuck puckBearingEnabled puckBearing="heading" /> : null}
          <Mapbox.ShapeSource
            ref={pointSourceRef}
            id="zerowaste-points"
            shape={geojson}
            cluster
            clusterRadius={48}
            clusterMaxZoomLevel={14}
            onPress={handleSourcePress}
          >
            <Mapbox.CircleLayer
              id="zerowaste-clusters"
              filter={['has', 'point_count']}
              style={{ circleColor: '#047857', circleRadius: ['step', ['get', 'point_count'], 19, 10, 23, 40, 28], circleStrokeColor: '#ECFDF5', circleStrokeWidth: 4, circleOpacity: 0.96 }}
            />
            <Mapbox.SymbolLayer
              id="zerowaste-cluster-count"
              filter={['has', 'point_count']}
              style={{ textField: ['get', 'point_count_abbreviated'], textColor: '#FFFFFF', textSize: 13, textFont: ['DIN Offc Pro Bold', 'Arial Unicode MS Bold'] }}
            />
            <Mapbox.CircleLayer
              id="zerowaste-point-halo"
              filter={['!', ['has', 'point_count']]}
              style={{ circleColor: 'rgba(52,211,153,0.22)', circleRadius: 23, circleStrokeWidth: 0 }}
            />
            <Mapbox.CircleLayer
              id="zerowaste-points-visible"
              filter={['!', ['has', 'point_count']]}
              style={{ circleColor: '#064E3B', circleRadius: 18, circleStrokeColor: '#10B981', circleStrokeWidth: 3 }}
            />
            <Mapbox.SymbolLayer
              id="zerowaste-point-symbol"
              filter={['!', ['has', 'point_count']]}
              style={{ textField: '♻', textColor: '#FFFFFF', textSize: 17, textAllowOverlap: true }}
            />
          </Mapbox.ShapeSource>
          <Mapbox.ShapeSource id="zerowaste-selected-point" shape={selectedFeature}>
            <Mapbox.CircleLayer id="zerowaste-selected-halo" style={{ circleColor: 'rgba(16,185,129,0.28)', circleRadius: 24 }} />
            <Mapbox.CircleLayer id="zerowaste-selected-dot" style={{ circleColor: '#10B981', circleRadius: 12, circleStrokeColor: '#FFFFFF', circleStrokeWidth: 4 }} />
            <Mapbox.SymbolLayer id="zerowaste-selected-symbol" style={{ textField: '♻', textColor: '#064E3B', textSize: 13, textAllowOverlap: true }} />
          </Mapbox.ShapeSource>
        </Mapbox.MapView>
      ) : (
        <View style={styles.map} className="items-center justify-center bg-emerald-50 px-7">
          <View className="absolute inset-0 opacity-30" style={styles.fallbackGrid} />
          <View className="h-20 w-20 items-center justify-center rounded-full border-4 border-white bg-emerald-700 shadow-lg elevation-5"><MapPin color="white" size={34} /></View>
          <Text className="mt-5 text-center text-2xl font-black text-emerald-950">Puntos ZeroWaste</Text>
          <Text className="mt-2 text-center leading-6 text-emerald-900">Busca por nombre, colonia o material y consulta las tarjetas disponibles.</Text>
        </View>
      )}

      {(!mapReady && (styleLoading || !mapMounted)) || mapError ? (
        <View
          pointerEvents={mapError ? 'auto' : 'none'}
          className={`absolute left-5 right-5 z-30 rounded-2xl border px-5 py-4 ${mapError ? 'border-emerald-200 bg-white' : 'border-emerald-100 bg-white/90'}`}
          style={{ top: topSafeArea + 68 }}
        >
          <View className="flex-row items-center justify-center">
            {!mapError ? <ActivityIndicator color="#047857" /> : null}
            <Text className={`text-center font-black ${mapError ? 'text-emerald-950' : 'ml-3 text-slate-800'}`}>{mapError || 'Preparando mapa…'}</Text>
          </View>
          {mapError && tokenReady ? <TouchableOpacity onPress={retryMap} className="mt-3 min-h-11 items-center justify-center rounded-xl bg-emerald-700"><Text className="font-black text-white">{usingFallbackStyle ? 'Reintentar mapa' : 'Usar mapa compatible'}</Text></TouchableOpacity> : null}
        </View>
      ) : null}

      <View className="absolute left-4 right-4 z-20" style={{ top: topSafeArea }}>
        <View className="h-14 flex-row items-center rounded-full border border-white/70 bg-white px-4 shadow-lg shadow-black/10 elevation-5">
          <Search color="#047857" size={20} />
          <TextInput
            value={query}
            onChangeText={setQuery}
            placeholder="Nombre, dirección o material"
            placeholderTextColor="#64748B"
            className="ml-3 h-14 flex-1 text-base font-semibold text-slate-900"
            returnKeyType="search"
            accessibilityLabel="Buscar puntos ZeroWaste"
          />
          {searching ? <ActivityIndicator color="#047857" size="small" /> : query ? <TouchableOpacity onPress={clearSearch} className="h-10 w-10 items-center justify-center" accessibilityLabel="Limpiar búsqueda"><X color="#475569" size={19} /></TouchableOpacity> : null}
        </View>
        {debouncedQuery && !emptySearch && !searchError ? <View pointerEvents="none" className="mt-2 self-start rounded-full bg-emerald-950/90 px-4 py-2"><Text className="text-xs font-black text-white">{results.length} {results.length === 1 ? 'punto' : 'puntos'}</Text></View> : null}
      </View>

      <View className="absolute right-4 z-20 gap-2" style={{ top: topSafeArea + 70 }}>
        <TouchableOpacity onPress={centerUser} className="h-12 w-12 items-center justify-center rounded-full border border-white bg-emerald-800 shadow-md elevation-4" accessibilityLabel="Recentrar en mi ubicación"><LocateFixed color="white" size={21} /></TouchableOpacity>
      </View>

      {pointsError ? <Notice tone="error" top={topSafeArea + 132} message={pointsError} action="Reintentar" onPress={() => fetchPoints({ preserve: points.length > 0 })} /> : null}
      {!pointsError && showPermissionNotice ? <Notice tone="warning" top={topSafeArea + 132} message={locationError || 'La ubicación está desactivada. Puedes explorar todos los puntos.'} action={permissionState === 'denied' ? 'Abrir ajustes' : 'Reintentar'} onPress={permissionState === 'denied' ? Linking.openSettings : requestLocation} /> : null}
      {searchError ? <Notice tone="error" top={topSafeArea + 132} message={searchError} action="Limpiar" onPress={clearSearch} /> : null}

      {emptySearch ? (
        <View className="absolute left-4 right-4 z-20 rounded-3xl border border-slate-200 bg-white p-5 shadow-lg" style={{ bottom: bottomSafeArea + 86 }}>
          <Text className="text-lg font-black text-slate-950">No encontramos puntos con esa búsqueda.</Text>
          <Text className="mt-1 text-sm leading-5 text-slate-500">Prueba con un material, colonia o nombre diferente.</Text>
          <View className="mt-4 flex-row gap-2"><TouchableOpacity onPress={clearSearch} className="min-h-11 flex-1 items-center justify-center rounded-xl bg-emerald-700"><Text className="font-black text-white">Limpiar búsqueda</Text></TouchableOpacity><TouchableOpacity onPress={clearSearch} className="min-h-11 flex-1 items-center justify-center rounded-xl border border-emerald-700"><Text className="font-black text-emerald-800">Ver todos</Text></TouchableOpacity></View>
        </View>
      ) : mapPoints.length ? (
        <FlatList
          ref={cardListRef}
          data={mapPoints}
          horizontal
          keyExtractor={(item) => String(item.id)}
          showsHorizontalScrollIndicator={false}
          snapToInterval={CARD_SNAP}
          decelerationRate="fast"
          contentContainerStyle={{ paddingHorizontal: 16 }}
          style={[styles.cards, { bottom: bottomSafeArea + 72 }]}
          getItemLayout={(_, index) => ({ length: CARD_SNAP, offset: CARD_SNAP * index, index })}
          onMomentumScrollEnd={(event) => {
            const index = Math.max(0, Math.min(mapPoints.length - 1, Math.round(event.nativeEvent.contentOffset.x / CARD_SNAP)));
            selectPoint(mapPoints[index], { moveCamera: true, scrollCard: false });
          }}
          onScrollToIndexFailed={({ index }) => setTimeout(() => cardListRef.current?.scrollToOffset({ offset: index * CARD_SNAP, animated: false }), 50)}
          renderItem={({ item }) => (
            <PointCard
              point={item}
              selected={selectedResult?.id === item.id}
              distance={formatDistance(distanceMeters(userLocation, [item.longitud, item.latitud]))}
              onSelect={() => selectPoint(item, { moveCamera: true, scrollCard: false })}
              onDetail={() => navigation.navigate('PointDetail', { point: item })}
              onRoute={() => navigation.navigate('RouteNavigation', { point: item })}
            />
          )}
        />
      ) : pointsReady && !pointsLoading && !pointsError ? <Notice tone="neutral" top={topSafeArea + 132} message="No hay puntos de reciclaje disponibles." action="Actualizar" onPress={() => fetchPoints()} /> : null}

      <View className="absolute right-4 z-20 flex-row gap-2" style={{ bottom: bottomSafeArea + (mapPoints.length && !emptySearch ? 266 : 90) }}>
        {isCollector ? <TouchableOpacity onPress={() => navigation.navigate('Scanner')} className="h-12 flex-row items-center rounded-full border-2 border-white bg-emerald-800 px-4 shadow-lg elevation-5" accessibilityLabel="Abrir QR de recolector"><QrCode color="white" size={19} /><Text className="ml-2 text-xs font-black text-white">QR recolector</Text></TouchableOpacity> : null}
        {!collectorOnly ? <TouchableOpacity
          onPress={async () => {
            const coordinates = userLocation || await requestLocation();
            if (!coordinates) {
              showDialog({ type: 'permission', title: 'Ubicación requerida', message: 'Activa la ubicación para solicitar una recolección.' });
              return;
            }
            navigation.navigate('CreateCollection', { coordinates });
          }}
          className="h-12 flex-row items-center rounded-full border-2 border-white bg-emerald-700 px-4 shadow-lg elevation-5"
        >
          <Truck color="white" size={19} /><Text className="ml-2 text-xs font-black text-white">Recolección</Text>
        </TouchableOpacity> : null}
      </View>
    </View>
  );
}

function Notice({ tone, top, message, action, onPress }) {
  const classes = tone === 'error' ? 'border-red-200 bg-red-50 text-red-800' : tone === 'warning' ? 'border-amber-200 bg-amber-50 text-amber-950' : 'border-slate-200 bg-white text-slate-800';
  return <View className={`absolute left-4 right-20 z-20 rounded-2xl border p-4 ${classes}`} style={{ top }}><Text className={`font-bold leading-5 ${classes.split(' ').at(-1)}`}>{message}</Text>{action ? <TouchableOpacity onPress={onPress} className="mt-2 self-start"><Text className={`font-black ${classes.split(' ').at(-1)}`}>{action}</Text></TouchableOpacity> : null}</View>;
}

function PointCard({ point, selected, distance, onSelect, onDetail, onRoute }) {
  const image = normalizeMediaUrl(point.image_url ?? point.imagen_url ?? point.imagen, 'puntos');
  const materials = Array.isArray(point.materiales) ? point.materiales.join(', ') : point.materiales;
  const rating = Number(point.promedio ?? point.valoracion);
  return (
    <TouchableOpacity
      activeOpacity={0.94}
      onPress={onSelect}
      className={`mr-3 overflow-hidden rounded-[26px] border bg-white shadow-xl elevation-6 ${selected ? 'border-emerald-500' : 'border-slate-100'}`}
      style={{ width: CARD_WIDTH }}
      accessibilityRole="button"
      accessibilityState={{ selected }}
      accessibilityLabel={`Punto ${point.nombre || 'ZeroWaste'}${distance ? `, a ${distance}` : ''}`}
    >
      <View className="flex-row p-3">
        <RemoteImage uri={image} className="h-[82px] w-[88px] rounded-2xl" aspectRatio={1.07} accessibilityLabel={`Imagen de ${point.nombre || 'punto ZeroWaste'}`} />
        <View className="ml-3 flex-1">
          <Text className="text-base font-black text-slate-950" numberOfLines={1}>{point.nombre || 'Punto ZeroWaste'}</Text>
          <Text className="mt-1 text-xs font-semibold text-emerald-700" numberOfLines={1}>{materials || 'Materiales por consultar'}</Text>
          <View className="mt-2 flex-row items-center"><MapPin color="#64748B" size={13} /><Text className="ml-1 flex-1 text-xs text-slate-500" numberOfLines={1}>{point.direccion || 'Dirección no especificada'}</Text></View>
          <View className="mt-1 flex-row items-center">{distance ? <Text className="text-xs font-black text-slate-700">{distance}</Text> : null}{Number.isFinite(rating) && rating > 0 ? <Text className="ml-2 text-xs font-black text-amber-600">★ {rating.toFixed(1)}</Text> : null}{point.horario ? <Text className="ml-2 flex-1 text-xs text-slate-500" numberOfLines={1}>{point.horario}</Text> : null}</View>
        </View>
      </View>
      <View className="flex-row gap-2 px-3 pb-3"><TouchableOpacity onPress={onDetail} className="min-h-11 flex-1 items-center justify-center rounded-xl border border-emerald-700"><Text className="font-black text-emerald-800">Ver detalle</Text></TouchableOpacity><TouchableOpacity onPress={onRoute} className="min-h-11 flex-1 flex-row items-center justify-center rounded-xl bg-emerald-700"><Navigation color="white" size={16} /><Text className="ml-2 font-black text-white">Ir ahora</Text></TouchableOpacity></View>
    </TouchableOpacity>
  );
}

const styles = StyleSheet.create({
  map: { ...StyleSheet.absoluteFillObject },
  fallbackGrid: {
    backgroundColor: '#D1FAE5',
    borderColor: '#A7F3D0',
    borderWidth: 1,
  },
  cards: { position: 'absolute', left: 0, right: 0, zIndex: 15, flexGrow: 0 },
});
