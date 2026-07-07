import React, { useEffect, useState, useRef } from 'react';
import { View, StyleSheet, ActivityIndicator, Text, ScrollView, Modal, TextInput, Alert, TouchableOpacity, KeyboardAvoidingView, Platform, Dimensions } from 'react-native';
import Mapbox from '@rnmapbox/maps';
import { api } from '../api/axios';
import CustomButton from '../components/ui/CustomButton';
import { Truck, Navigation, X, MapPin } from 'lucide-react-native';
import { useNavigation } from '@react-navigation/native';
import { SafeAreaView, useSafeAreaInsets } from 'react-native-safe-area-context';
import * as Location from 'expo-location';
import { StatusBar } from 'expo-status-bar';

const MAPBOX_TOKEN = process.env.EXPO_PUBLIC_MAPBOX_TOKEN || 'pk.YOUR_MAPBOX_TOKEN_HERE';
Mapbox.setAccessToken(MAPBOX_TOKEN);

export default function MapScreen() {
  const navigation = useNavigation();
  const insets = useSafeAreaInsets();
  const cameraRef = useRef(null);
  
  const [puntos, setPuntos] = useState([]);
  const [loading, setLoading] = useState(true);
  
  // Location and Navigation States
  const [userLocation, setUserLocation] = useState(null);
  const [isNavigating, setIsNavigating] = useState(false);
  const [routeData, setRouteData] = useState(null);
  const [etaInfo, setEtaInfo] = useState(null); // { duration: "15 min", distance: "4 km" }

  // Modal recoleccion a domicilio
  const [modalVisible, setModalVisible] = useState(false);
  const [direccion, setDireccion] = useState('');
  const [materiales, setMateriales] = useState('');
  const [submitting, setSubmitting] = useState(false);

  useEffect(() => {
    fetchPuntos();
    requestLocationPermission();
  }, []);

  const requestLocationPermission = async () => {
    const { status } = await Location.requestForegroundPermissionsAsync();
    if (status !== 'granted') {
      Alert.alert('Permiso denegado', 'No se puede trazar rutas sin tu ubicación.');
      return;
    }
    const loc = await Location.getCurrentPositionAsync({});
    setUserLocation([loc.coords.longitude, loc.coords.latitude]);
  };

  const fetchPuntos = async () => {
    try {
      const response = await api.get('/mapa/puntos');
      setPuntos(response.data);
    } catch (e) {
      console.error('Error fetching map points', e);
    } finally {
      setLoading(false);
    }
  };

  const startNavigation = async (punto) => {
    if (!userLocation) {
      Alert.alert('Error', 'No se ha detectado tu ubicación actual.');
      return;
    }
    
    setLoading(true);
    try {
      const destCoords = [parseFloat(punto.longitud), parseFloat(punto.latitud)];
      const url = `https://api.mapbox.com/directions/v5/mapbox/driving/${userLocation[0]},${userLocation[1]};${destCoords[0]},${destCoords[1]}?geometries=geojson&access_token=${MAPBOX_TOKEN}`;
      
      const response = await fetch(url);
      const data = await response.json();
      
      if (data.routes && data.routes.length > 0) {
        const route = data.routes[0];
        setRouteData(route.geometry);
        
        const durationMin = Math.round(route.duration / 60);
        const distKm = (route.distance / 1000).toFixed(1);
        setEtaInfo({ duration: `${durationMin} min`, distance: `${distKm} km`, destination: punto.nombre });
        setIsNavigating(true);
        
        // The camera will automatically update because we change isNavigating to true,
        // which triggers followUserLocation={true} and followUserMode={'course'} in the JSX.
      } else {
        Alert.alert('Error', 'No se pudo encontrar una ruta al destino.');
      }
    } catch (error) {
      console.error(error);
      Alert.alert('Error', 'Ocurrió un problema trazando la ruta.');
    } finally {
      setLoading(false);
    }
  };

  const stopNavigation = () => {
    setIsNavigating(false);
    setRouteData(null);
    setEtaInfo(null);
    
    // The camera will revert because isNavigating becomes false
  };

  const handleSolicitar = async () => {
    if (!direccion.trim() || !materiales.trim()) {
      Alert.alert('Error', 'Por favor ingresa la dirección y los materiales.');
      return;
    }
    setSubmitting(true);
    try {
      await api.post('/recolecciones', { direccion, materiales });
      Alert.alert('Éxito', 'Tu solicitud ha sido enviada. Pronto un recolector la atenderá.');
      setModalVisible(false);
      setDireccion('');
      setMateriales('');
    } catch (error) {
      Alert.alert('Error', 'No se pudo enviar la solicitud.');
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <View className="flex-1 bg-background relative">
      <StatusBar style="dark" translucent={true} backgroundColor="transparent" />
      <Mapbox.MapView 
        style={styles.map}
        styleURL={isNavigating ? Mapbox.StyleURL.TrafficDay : Mapbox.StyleURL.Street}
        logoEnabled={false}
        attributionEnabled={false}
      >
        <Mapbox.Camera
          ref={cameraRef}
          zoomLevel={isNavigating ? 17 : 13.5}
          pitch={isNavigating ? 70 : 0} // Más inclinado para efecto GPS
          centerCoordinate={!isNavigating ? [-100.3929, 20.5888] : undefined}
          followUserLocation={isNavigating}
          followUserMode={isNavigating ? 'course' : 'normal'}
          followZoomLevel={17}
          followPitch={70}
          animationMode="flyTo"
          animationDuration={2000}
        />
        
        {/* Capa de Edificios 3D para mayor inmersión */}
        <Mapbox.VectorSource id="composite" url="mapbox://mapbox.mapbox-streets-v8">
          <Mapbox.FillExtrusionLayer
            id="building3d"
            sourceLayerID="building"
            minZoomLevel={15}
            filter={['==', 'extrude', 'true']}
            style={{
              fillExtrusionOpacity: 0.8,
              fillExtrusionColor: '#e3e3e3',
              fillExtrusionHeight: ['get', 'height'],
              fillExtrusionBase: ['get', 'min_height'],
            }}
          />
        </Mapbox.VectorSource>
        
        <Mapbox.UserLocation 
          visible={true}
          showsUserHeadingIndicator={true}
          onUpdate={(location) => setUserLocation([location.coords.longitude, location.coords.latitude])}
        />

        {puntos.filter(p => p.longitud && p.latitud).map((p) => (
          <Mapbox.PointAnnotation
            key={p.id}
            id={`punto-${p.id}`}
            coordinate={[parseFloat(p.longitud), parseFloat(p.latitud)]}
          >
            <View className="w-10 h-10 bg-emerald-600 border-2 border-white rounded-full items-center justify-center shadow-lg elevation-4">
              <Text className="text-white text-xs font-bold">♻️</Text>
            </View>
          </Mapbox.PointAnnotation>
        ))}

        {routeData && (
          <Mapbox.ShapeSource id="routeSource" shape={routeData}>
            <Mapbox.LineLayer
              id="routeFill"
              style={{
                lineColor: '#3b82f6', // blue-500
                lineWidth: 6,
                lineCap: 'round',
                lineJoin: 'round',
              }}
            />
          </Mapbox.ShapeSource>
        )}
      </Mapbox.MapView>

      {/* Barra superior normal */}
      {!isNavigating && (
        <View className="absolute left-6 right-6 z-10 flex-row gap-2" style={{ top: Math.max(insets.top + 16, 48) }}>
          <View className="flex-1 bg-surface rounded-full py-4 px-6 shadow-lg shadow-black/10 elevation-5 border border-gray-100 flex-row items-center">
            <Text className="text-subtext text-base font-semibold">Buscar punto de acopio...</Text>
          </View>
          <TouchableOpacity 
            onPress={() => navigation.navigate('MisRecolecciones')}
            className="bg-primary w-14 h-14 rounded-full shadow-lg items-center justify-center elevation-5 border-2 border-surface"
          >
            <MapPin color="white" size={24} />
          </TouchableOpacity>
        </View>
      )}

      {/* Panel Superior de Navegación ETA */}
      {isNavigating && etaInfo && (
        <View className="absolute left-4 right-4 z-10 bg-white rounded-3xl p-5 shadow-2xl elevation-8 flex-row items-center justify-between" style={{ top: Math.max(insets.top + 16, 48) }}>
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
      )}

      {/* Carrusel de Puntos (Solo cuando NO navegamos) */}
      {!isNavigating && (
        <View className="absolute left-0 right-0 z-10" style={{ bottom: Math.max(insets.bottom + 90, 100) }}>
          <ScrollView 
            horizontal 
            showsHorizontalScrollIndicator={false}
            contentContainerStyle={{ paddingHorizontal: 24 }}
            snapToInterval={296} // 280 (width) + 16 (margin)
            decelerationRate="fast"
          >
            {puntos.map(p => (
              <View 
                key={p.id}
                className="bg-surface rounded-3xl p-4 w-[280px] mr-4 shadow-xl shadow-black/10 elevation-5 border border-gray-100"
              >
                <View className="flex-row items-center">
                  <View className="w-14 h-14 bg-emerald-50 rounded-2xl items-center justify-center mr-3 border border-emerald-100">
                    <Text className="text-2xl">♻️</Text>
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

      {/* Botón Flotante de Recolección (Solo cuando NO navegamos) */}
      {!isNavigating && (
        <View className="absolute right-6 z-20" style={{ bottom: Math.max(insets.bottom + 16, 24) }}>
          <TouchableOpacity 
            onPress={() => setModalVisible(true)}
            className="bg-primary flex-row items-center justify-center px-5 py-4 rounded-full shadow-lg elevation-6 border-2 border-surface"
          >
            <Truck color="white" size={24} className="mr-2" />
            <Text className="text-white font-black text-sm">Recolección</Text>
          </TouchableOpacity>
        </View>
      )}

      {loading && (
        <View className="absolute inset-0 items-center justify-center bg-black/20 z-50">
          <View className="bg-white p-4 rounded-2xl shadow-xl">
            <ActivityIndicator size="large" color="#064E3B" />
          </View>
        </View>
      )}

      {/* Modal for Solicitar Recoleccion */}
      <Modal visible={modalVisible} transparent animationType="slide" onRequestClose={() => setModalVisible(false)}>
        <KeyboardAvoidingView 
          behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
          className="flex-1 justify-end bg-black/40"
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
              className="w-full"
            />
          </View>
        </KeyboardAvoidingView>
      </Modal>
    </View>
  );
}

const styles = StyleSheet.create({
  map: {
    flex: 1,
  },
});