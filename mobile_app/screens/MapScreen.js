import React, { useEffect, useState, useRef } from 'react';
import { View, StyleSheet, ActivityIndicator, Text, ScrollView, Modal, TextInput, Alert, TouchableOpacity, KeyboardAvoidingView, Platform, Linking } from 'react-native';
import Mapbox from '@rnmapbox/maps';
import { api } from '../api/axios';
import CustomButton from '../components/ui/CustomButton';
import { Truck, Navigation, X, MapPin, QrCode, Leaf } from 'lucide-react-native';
import { useNavigation } from '@react-navigation/native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import * as Location from 'expo-location';
import { StatusBar } from 'expo-status-bar';
import { useAuth } from '../store/useAuth';

const MAPBOX_TOKEN = process.env.EXPO_PUBLIC_MAPBOX_TOKEN?.trim() || '';
const HAS_VALID_MAPBOX_TOKEN = MAPBOX_TOKEN.startsWith('pk.') && !MAPBOX_TOKEN.includes('YOUR_');
const MAP_LOAD_TIMEOUT_MS = 15000;
const SAFE_MAP_LOAD_ERROR = 'No fue posible cargar el mapa. Revisa tu conexión e inténtalo nuevamente.';
if (HAS_VALID_MAPBOX_TOKEN) Mapbox.setAccessToken(MAPBOX_TOKEN);

const normalizePoint = (point) => {
  const latitude = Number(point?.latitud);
  const longitude = Number(point?.longitud);
  if (!Number.isFinite(latitude) || !Number.isFinite(longitude)) return null;
  if (latitude < -90 || latitude > 90 || longitude < -180 || longitude > 180) return null;
  return { ...point, latitud: latitude, longitud: longitude };
};

export default function MapScreen() {
  const navigation = useNavigation();
  const insets = useSafeAreaInsets();
  const { user } = useAuth();
  const cameraRef = useRef(null);
  const mapReadyRef = useRef(false);
  const locationRequestRef = useRef(null);
  const tokenReady = HAS_VALID_MAPBOX_TOKEN;
  
  const [puntos, setPuntos] = useState([]);
  const [loadingMap, setLoadingMap] = useState(HAS_VALID_MAPBOX_TOKEN);
  const [loadingPoints, setLoadingPoints] = useState(true);
  const [pointsReady, setPointsReady] = useState(false);
  const [mapMounted, setMapMounted] = useState(false);
  const [mapReady, setMapReady] = useState(false);
  const [mapKey, setMapKey] = useState(0);
  const [mapError, setMapError] = useState(HAS_VALID_MAPBOX_TOKEN ? '' : 'El token público de Mapbox no está configurado correctamente.');
  const [pointsError, setPointsError] = useState('');
  const [searchQuery, setSearchQuery] = useState('');
  const [locationPermission, setLocationPermission] = useState('unknown');
  const [locationError, setLocationError] = useState('');
  
  // Location and Navigation States
  const [userLocation, setUserLocation] = useState(null);
  const [isNavigating, setIsNavigating] = useState(false);
  const [routeData, setRouteData] = useState(null);
  const [etaInfo, setEtaInfo] = useState(null); // { duration: "15 min", distance: "4 km", trafficStatus, trafficColor, badgeBg }
  const [routeLoading, setRouteLoading] = useState(false);

  // Modal recoleccion a domicilio
  const [modalVisible, setModalVisible] = useState(false);
  const [direccion, setDireccion] = useState('');
  const [materiales, setMateriales] = useState('');
  const [submitting, setSubmitting] = useState(false);

  const isRecolector = user?.rol === 'recolector' || user?.is_admin;
  const visiblePoints = puntos.filter((point) => {
    const needle = searchQuery.trim().toLocaleLowerCase('es');
    return !needle || `${point.nombre || ''} ${point.direccion || ''} ${point.materiales || ''}`.toLocaleLowerCase('es').includes(needle);
  });

  const handleMapReady = () => {
    if (mapReadyRef.current) return;
    mapReadyRef.current = true;
    setMapReady(true);
    setLoadingMap(false);
    setMapError('');
  };

  const handleMapLoadingError = (event) => {
    const status = Number(event?.error?.status || event?.status || 0);
    if (typeof __DEV__ !== 'undefined' && __DEV__) {
      console.warn(`[map] Mapbox reportó un error de carga${status ? ` (HTTP ${status})` : ''}.`);
    }
    if (!mapReadyRef.current) {
      setLoadingMap(false);
      setMapError(SAFE_MAP_LOAD_ERROR);
    }
  };

  const retryMap = () => {
    if (!HAS_VALID_MAPBOX_TOKEN) return;
    mapReadyRef.current = false;
    setMapMounted(false);
    setMapReady(false);
    setMapError('');
    setLoadingMap(true);
    setMapKey((value) => value + 1);
  };

  const requestLocationPermission = async () => {
    if (locationRequestRef.current) return locationRequestRef.current;
    const request = (async () => {
      setLocationPermission('requesting');
      setLocationError('');
      try {
        const { status } = await Location.requestForegroundPermissionsAsync();
        if (status !== 'granted') {
          setLocationPermission('denied');
          return null;
        }
        const loc = await Location.getCurrentPositionAsync({ accuracy: Location.Accuracy.Balanced });
        const coordinates = [loc.coords.longitude, loc.coords.latitude];
        setUserLocation(coordinates);
        setLocationPermission('granted');
        return coordinates;
      } catch {
        setLocationPermission('unavailable');
        setLocationError('No fue posible obtener tu ubicación. El mapa seguirá disponible.');
        return null;
      }
    })();
    locationRequestRef.current = request;
    try {
      return await request;
    } finally {
      locationRequestRef.current = null;
    }
  };

  const centerUser = async () => {
    const coordinates = userLocation || await requestLocationPermission();
    if (!coordinates) return;
    cameraRef.current?.setCamera({ centerCoordinate: coordinates, zoomLevel: 15, animationDuration: 900 });
  };

  useEffect(() => {
    void fetchPuntos();
    void requestLocationPermission();
  }, []);

  useEffect(() => {
    if (!tokenReady || mapReadyRef.current) return undefined;
    const timeout = setTimeout(() => {
      if (mapReadyRef.current) return;
      setLoadingMap(false);
      setMapError(SAFE_MAP_LOAD_ERROR);
    }, MAP_LOAD_TIMEOUT_MS);
    return () => clearTimeout(timeout);
  }, [mapKey, tokenReady]);

  const fetchPuntos = async () => {
    setLoadingPoints(true);
    setPointsReady(false);
    setPointsError('');
    try {
      const response = await api.get('/mapa/puntos');
      const seen = new Set();
      const validPoints = (Array.isArray(response.data) ? response.data : [])
        .map(normalizePoint)
        .filter((point) => point && point.activo !== false && !seen.has(String(point.id)) && seen.add(String(point.id)));
      setPuntos(validPoints);
      setPointsReady(true);
    } catch (e) {
      setPuntos([]);
      setPointsError(e.userMessage || 'No se pudieron cargar los puntos de reciclaje.');
    } finally {
      setLoadingPoints(false);
    }
  };

  const reviewConnection = () => {
    retryMap();
    void fetchPuntos();
  };

  const startNavigation = async (punto) => {
    const currentLocation = userLocation || await requestLocationPermission();
    if (!currentLocation) {
      Alert.alert('Error', 'No se ha detectado tu ubicación actual.');
      return;
    }
    
    setRouteLoading(true);
    try {
      const destCoords = [punto.longitud, punto.latitud];
      // Using Mapbox driving-traffic profile for real-time traffic aware navigation
      const url = `https://api.mapbox.com/directions/v5/mapbox/driving-traffic/${currentLocation[0]},${currentLocation[1]};${destCoords[0]},${destCoords[1]}?geometries=geojson&annotations=congestion,duration,distance&access_token=${MAPBOX_TOKEN}`;
      
      const response = await fetch(url);
      if (!response.ok) throw new Error(`Mapbox Directions HTTP ${response.status}`);
      const data = await response.json();
      
      if (data.routes && data.routes.length > 0) {
        const route = data.routes[0];
        setRouteData(route.geometry);
        
        const durationMin = Math.round(route.duration / 60);
        const distKm = (route.distance / 1000).toFixed(1);

        // Compute traffic status from typical duration or congestion
        let trafficStatus = 'Fluido';
        let trafficColor = 'bg-emerald-500';
        let badgeBg = 'bg-emerald-50 border-emerald-200 text-emerald-800';
        if (route.duration_typical && route.duration > route.duration_typical * 1.25) {
          trafficStatus = 'Alto';
          trafficColor = 'bg-red-500';
          badgeBg = 'bg-red-50 border-red-200 text-red-800';
        } else if (route.duration_typical && route.duration > route.duration_typical * 1.1) {
          trafficStatus = 'Moderado';
          trafficColor = 'bg-amber-500';
          badgeBg = 'bg-amber-50 border-amber-200 text-amber-800';
        }

        setEtaInfo({ 
          duration: `${durationMin} min`, 
          distance: `${distKm} km`, 
          destination: punto.nombre,
          trafficStatus,
          trafficColor,
          badgeBg,
        });
        setIsNavigating(true);
      } else {
        Alert.alert('Error', 'No se pudo encontrar una ruta al destino.');
      }
    } catch (error) {
      Alert.alert('Error', 'Ocurrió un problema trazando la ruta de navegación.');
    } finally {
      setRouteLoading(false);
    }
  };

  const stopNavigation = () => {
    setIsNavigating(false);
    setRouteData(null);
    setEtaInfo(null);
  };

  const handleSolicitar = async () => {
    if (!direccion.trim() || !materiales.trim()) {
      Alert.alert('Error', 'Por favor ingresa la dirección y los materiales.');
      return;
    }
    setSubmitting(true);
    try {
      const currentLocation = userLocation || await requestLocationPermission();
      if (!currentLocation) {
        Alert.alert('Ubicación requerida', 'Activa la ubicación para enviar las coordenadas de la solicitud.');
        return;
      }
      await api.post('/recolecciones', {
        direccion,
        materiales,
        longitud: currentLocation[0],
        latitud: currentLocation[1],
      });
      Alert.alert('Éxito', 'Tu solicitud ha sido enviada. Un recolector la atenderá pronto.');
      setModalVisible(false);
      setDireccion('');
      setMateriales('');
    } catch (error) {
      Alert.alert('Error', 'No se pudo enviar la solicitud de recolección.');
    } finally {
      setSubmitting(false);
    }
  };

  const topSafeArea = Math.max(insets.top + 16, 48);
  const bottomSafeArea = Math.max(insets.bottom + 14, 24);

  return (
    <View className="flex-1 bg-background relative">
      <StatusBar style="dark" translucent={true} backgroundColor="transparent" />
      {tokenReady ? <Mapbox.MapView
        key={mapKey}
        style={styles.map}
        styleURL={isNavigating ? Mapbox.StyleURL.TrafficDay : Mapbox.StyleURL.Street}
        logoEnabled={false}
        attributionEnabled={false}
        onLayout={() => setMapMounted(true)}
        onWillStartLoadingMap={() => { if (!mapReadyRef.current) setLoadingMap(true); }}
        onDidFinishLoadingStyle={handleMapReady}
        onDidFinishLoadingMap={handleMapReady}
        onMapLoadingError={handleMapLoadingError}
      >
        <Mapbox.Camera
          ref={cameraRef}
          zoomLevel={isNavigating ? 17 : 13.5}
          pitch={isNavigating ? 70 : 0}
          centerCoordinate={!isNavigating ? [-100.3929, 20.5888] : undefined}
          followUserLocation={isNavigating && locationPermission === 'granted'}
          followUserMode={isNavigating ? 'course' : 'normal'}
          followZoomLevel={17}
          followPitch={70}
          animationMode="flyTo"
          animationDuration={2000}
        />

        {locationPermission === 'granted' ? (
          <Mapbox.LocationPuck puckBearingEnabled puckBearing="heading" />
        ) : null}
        
        {visiblePoints.map((p) => (
          <Mapbox.PointAnnotation
            key={p.id}
            id={`punto-${p.id}`}
            coordinate={[p.longitud, p.latitud]}
          >
            {/* Marcador idéntico a la imagen (hoja verde sobre círculo verde oscuro con borde verde neón) */}
            <View className="w-12 h-12 bg-[#064E3B] border-[3px] border-[#34D399] rounded-full items-center justify-center shadow-xl elevation-6">
              <Leaf color="#34D399" fill="#34D399" size={22} />
            </View>
          </Mapbox.PointAnnotation>
        ))}

        {routeData && (
          <Mapbox.ShapeSource id="routeSource" shape={routeData}>
            <Mapbox.LineLayer
              id="routeFill"
              style={{
                lineColor: '#10B981', // emerald-500
                lineWidth: 6,
                lineCap: 'round',
                lineJoin: 'round',
              }}
            />
          </Mapbox.ShapeSource>
        )}
      </Mapbox.MapView> : <View style={styles.map} />}

      {!mapReady && (loadingMap || mapError || !mapMounted) ? (
        <View className="absolute inset-0 z-40 items-center justify-center bg-white px-8">
          <Text className="text-lg font-black text-gray-900 text-center">{mapError || 'Cargando mapa…'}</Text>
          {mapError && tokenReady ? (
            <View className="mt-4 flex-row gap-3">
              <TouchableOpacity onPress={retryMap} className="rounded-xl bg-emerald-700 px-5 py-3">
                <Text className="text-white font-black">Reintentar</Text>
              </TouchableOpacity>
              <TouchableOpacity onPress={reviewConnection} className="rounded-xl border border-emerald-700 bg-white px-5 py-3">
                <Text className="text-emerald-800 font-black">Revisar conexión</Text>
              </TouchableOpacity>
            </View>
          ) : !mapError ? <ActivityIndicator className="mt-4" color="#047857" /> : null}
        </View>
      ) : null}

      {pointsError ? (
        <View className="absolute left-5 right-5 top-24 rounded-2xl bg-red-50 border border-red-200 p-4 z-30">
          <Text className="text-red-700 font-bold text-center">{pointsError}</Text>
          <TouchableOpacity onPress={fetchPuntos} className="mt-2 self-center"><Text className="text-red-700 font-black">Reintentar</Text></TouchableOpacity>
        </View>
      ) : null}

      {mapReady && pointsReady && !loadingPoints && !pointsError && puntos.length === 0 ? (
        <View className="absolute left-5 right-5 top-24 rounded-2xl bg-white border border-gray-200 p-4 z-30">
          <Text className="text-gray-700 font-bold text-center">No hay puntos de reciclaje disponibles.</Text>
          <TouchableOpacity onPress={fetchPuntos} className="mt-2 self-center"><Text className="text-emerald-700 font-black">Actualizar</Text></TouchableOpacity>
        </View>
      ) : null}

      {mapReady && ['denied', 'unavailable'].includes(locationPermission) && !pointsError ? (
        <View className="absolute left-5 right-5 top-24 rounded-2xl bg-amber-50 border border-amber-200 p-4 z-30">
          <Text className="text-amber-900 font-bold text-center">{locationError || 'La ubicación está desactivada. El mapa funciona, pero no puede centrarte ni crear rutas.'}</Text>
          {locationPermission === 'denied' ? (
            <TouchableOpacity onPress={() => Linking.openSettings()} className="mt-2 self-center"><Text className="text-amber-900 font-black">Abrir Ajustes</Text></TouchableOpacity>
          ) : null}
        </View>
      ) : null}

      {/* Barra superior normal con Safe Area Superior */}
      {!isNavigating && (
        <View className="absolute left-6 right-6 z-10 flex-row gap-2" style={{ top: topSafeArea }}>
          <View className="flex-1 bg-surface rounded-full px-6 shadow-lg shadow-black/10 elevation-5 border border-gray-100 flex-row items-center">
            <TextInput value={searchQuery} onChangeText={setSearchQuery} placeholder="Buscar punto de acopio..." className="h-14 flex-1 text-base font-semibold text-gray-800" placeholderTextColor="#6B7280" />
          </View>
          <TouchableOpacity 
            onPress={centerUser}
            accessibilityLabel="Centrar mi ubicación"
            className="bg-primary w-14 h-14 rounded-full shadow-lg items-center justify-center elevation-5 border-2 border-surface"
          >
            <MapPin color="white" size={24} />
          </TouchableOpacity>
        </View>
      )}

      {/* Panel Superior de Navegación ETA + Tráfico en Zona */}
      {isNavigating && etaInfo && (
        <View className="absolute left-4 right-4 z-10 bg-white rounded-3xl p-5 shadow-2xl elevation-8" style={{ top: topSafeArea }}>
          <View className="flex-row items-center justify-between">
            <View>
              <Text className="text-emerald-700 font-black text-3xl tracking-tight">{etaInfo.duration}</Text>
              <View className="flex-row items-center gap-2 mt-1">
                <Text className="text-gray-500 font-bold">{etaInfo.distance}</Text>
                <View className="w-1.5 h-1.5 rounded-full bg-gray-300" />
                <Text className="text-gray-400 font-medium" numberOfLines={1} style={{ maxWidth: 150 }}>{etaInfo.destination}</Text>
              </View>
            </View>
            <TouchableOpacity 
              onPress={stopNavigation}
              className="w-12 h-12 bg-red-100 rounded-full items-center justify-center border border-red-200"
            >
              <X color="#EF4444" size={24} />
            </TouchableOpacity>
          </View>

          {/* Indicador de Tráfico de Mapbox */}
          <View className={`flex-row items-center gap-2 mt-3 px-3 py-1.5 rounded-xl border self-start ${etaInfo.badgeBg || 'bg-emerald-50 border-emerald-200'}`}>
            <View className={`w-2.5 h-2.5 rounded-full ${etaInfo.trafficColor || 'bg-emerald-500'}`} />
            <Text className="text-xs font-bold text-gray-800">
              Tráfico en la zona: <Text className="font-extrabold">{etaInfo.trafficStatus || 'Fluido'}</Text>
            </Text>
          </View>
        </View>
      )}

      {/* Carrusel de Puntos (Solo cuando NO navegamos) respetando FloatingTabBar (bottomSafeArea + 75) */}
      {!isNavigating && (
        <View className="absolute left-0 right-0 z-10" style={{ bottom: bottomSafeArea + 75 }}>
          <ScrollView 
            horizontal 
            showsHorizontalScrollIndicator={false}
            contentContainerStyle={{ paddingHorizontal: 24 }}
            snapToInterval={296}
            decelerationRate="fast"
          >
            {visiblePoints.map(p => (
              <View 
                key={p.id}
                className="bg-surface rounded-3xl p-4 w-[280px] mr-4 shadow-xl shadow-black/10 elevation-5 border border-gray-100"
              >
                <View className="flex-row items-center">
                  <View className="w-14 h-14 bg-[#064E3B] border-2 border-[#34D399] rounded-2xl items-center justify-center mr-3 shadow-md">
                    <Leaf color="#34D399" fill="#34D399" size={26} />
                  </View>
                  <View className="flex-1">
                    <Text className="font-bold text-text text-base" numberOfLines={1}>{p.nombre}</Text>
                    <Text className="text-subtext text-xs mt-0.5" numberOfLines={1}>{p.materiales || 'Materiales reciclables'}</Text>
                    <View className="flex-row items-center mt-1">
                      <Text className="text-amber-500 text-xs font-bold mr-1">★ {p.promedio || '5.0'}</Text>
                      <Text className="text-subtext text-xs">({p.total_reviews || 0} reseñas)</Text>
                    </View>
                  </View>
                </View>
                <TouchableOpacity 
                  onPress={() => startNavigation(p)}
                  className="mt-3 bg-emerald-600 rounded-xl py-2 flex-row justify-center items-center gap-2"
                >
                  <Navigation color="white" size={16} />
                  <Text className="text-white font-bold text-sm">Ir ahora</Text>
                </TouchableOpacity>
              </View>
            ))}
          </ScrollView>
        </View>
      )}

      {/* Botón Flotante de Recolección O Modo Recolector sobre el Carrusel (bottomSafeArea + 230) */}
      {!isNavigating && (
        <View className="absolute right-6 z-20 flex-row gap-2" style={{ bottom: bottomSafeArea + 230 }}>
          {isRecolector && (
            <TouchableOpacity 
              onPress={() => navigation.navigate('Scanner')}
              className="bg-emerald-700 flex-row items-center justify-center px-4 py-3.5 rounded-full shadow-lg elevation-6 border-2 border-surface"
            >
              <QrCode color="white" size={20} className="mr-1.5" />
              <Text className="text-white font-black text-xs">QR Recolector</Text>
            </TouchableOpacity>
          )}
          <TouchableOpacity 
            onPress={() => setModalVisible(true)}
            className="bg-primary flex-row items-center justify-center px-5 py-3.5 rounded-full shadow-lg elevation-6 border-2 border-surface"
          >
            <Truck color="white" size={20} className="mr-1.5" />
            <Text className="text-white font-black text-xs">Recolección</Text>
          </TouchableOpacity>
        </View>
      )}

      {routeLoading && (
        <View className="absolute inset-0 items-center justify-center bg-black/20 z-50">
          <View className="bg-white p-4 rounded-2xl shadow-xl">
            <ActivityIndicator size="large" color="#064E3B" />
          </View>
        </View>
      )}

      {/* Modal para Solicitar Recoleccion */}
      <Modal visible={modalVisible} transparent animationType="slide" onRequestClose={() => setModalVisible(false)}>
        <KeyboardAvoidingView 
          behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
          className="flex-1 justify-end bg-black/40"
        >
          <ScrollView 
            contentContainerStyle={{ flexGrow: 1, justifyContent: 'flex-end' }}
            keyboardShouldPersistTaps="handled"
          >
            <View className="bg-white rounded-t-3xl p-6 pt-4 shadow-2xl" style={{ paddingBottom: Math.max(insets.bottom + 20, 24) }}>
              <View className="flex-row justify-between items-center mb-6">
                <View>
                  <Text className="text-2xl font-black text-text mb-1">Recolección a Domicilio</Text>
                  <Text className="text-subtext text-sm">Un recolector pasará por los materiales.</Text>
                </View>
                <TouchableOpacity onPress={() => setModalVisible(false)} className="p-2 bg-gray-100 rounded-full">
                  <X color="#374151" size={20} />
                </TouchableOpacity>
              </View>
              
              <View className="mb-4">
                <Text className="font-bold text-text mb-2 text-sm">Dirección de Recolección</Text>
                <TextInput
                  placeholder="Ej. Calle Primavera 123, Col. Centro"
                  value={direccion}
                  onChangeText={setDireccion}
                  className="bg-gray-50 border border-gray-200 rounded-xl p-4 text-text text-base"
                  placeholderTextColor="#9CA3AF"
                />
              </View>

              <View className="mb-6">
                <Text className="font-bold text-text mb-2 text-sm">¿Qué materiales entregarás?</Text>
                <TextInput
                  placeholder="Ej. 2kg PET, Cartón, Electrónicos"
                  value={materiales}
                  onChangeText={setMateriales}
                  className="bg-gray-50 border border-gray-200 rounded-xl p-4 text-text text-base min-h-[100px]"
                  multiline
                  textAlignVertical="top"
                  placeholderTextColor="#9CA3AF"
                />
              </View>

              <CustomButton 
                title={submitting ? "Enviando Solicitud..." : "Solicitar Recolección"} 
                onPress={handleSolicitar} 
                disabled={submitting}
              />
            </View>
          </ScrollView>
        </KeyboardAvoidingView>
      </Modal>
    </View>
  );
}

const styles = StyleSheet.create({
  map: {
    ...StyleSheet.absoluteFillObject,
  },
});
