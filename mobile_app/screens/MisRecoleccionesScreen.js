import React, { useEffect, useState } from 'react';
import { View, Text, FlatList, TouchableOpacity, Modal, TextInput, Alert, ActivityIndicator } from 'react-native';
import { useAuth } from '../store/useAuth';
import { api } from '../api/axios';
import { useNavigation } from '@react-navigation/native';
import { SafeAreaView } from 'react-native-safe-area-context';
import CustomButton from '../components/ui/CustomButton';
import { ArrowLeft, Star } from 'lucide-react-native';
import { StatusBar } from 'expo-status-bar';
import QRCode from 'react-native-qrcode-svg';

export default function MisRecoleccionesScreen() {
  const { user } = useAuth();
  const navigation = useNavigation();
  const [recolecciones, setRecolecciones] = useState([]);
  const [loading, setLoading] = useState(true);

  // Modal Calificacion
  const [calificacionModalVisible, setCalificacionModalVisible] = useState(false);
  const [selectedRecoleccion, setSelectedRecoleccion] = useState(null);
  const [calificacion, setCalificacion] = useState(5);
  const [comentario, setComentario] = useState('');

  // Modal QR
  const [qrModalVisible, setQrModalVisible] = useState(false);
  const [qrData, setQrData] = useState(null);
  const [qrLoading, setQrLoading] = useState(false);
  const [error, setError] = useState('');

  useEffect(() => {
    fetchRecolecciones();
  }, []);

  const fetchRecolecciones = async () => {
    try {
      const { data } = await api.get('/recolecciones');
      setRecolecciones(data);
    } catch (e) {
      setError(e.userMessage || 'No se pudieron cargar las recolecciones.');
    } finally {
      setLoading(false);
    }
  };

  const openSecureQr = async (collection) => {
    setQrLoading(true);
    setError('');
    try {
      const { data } = await api.post(`/recolecciones/${collection.id}/qr`);
      setQrData({ collection, content: data.content, expiresAt: data.expires_at });
      setQrModalVisible(true);
    } catch (requestError) {
      Alert.alert('No se pudo generar el QR', requestError.userMessage);
    } finally {
      setQrLoading(false);
    }
  };

  const submitCalificacion = async () => {
    if (!selectedRecoleccion) return;
    try {
      await api.post(`/recolecciones/${selectedRecoleccion.id}/calificar`, {
        calificacion,
        comentario
      });
      Alert.alert('Éxito', '¡Gracias por calificar a tu recolector!');
      setCalificacionModalVisible(false);
      fetchRecolecciones();
    } catch (error) {
      Alert.alert('Error', 'No se pudo enviar la calificación');
    }
  };

  return (
    <SafeAreaView className="flex-1 bg-background px-6" edges={['top']}>
      <StatusBar style="dark" />
      <View className="flex-row items-center mb-6">
        <TouchableOpacity onPress={() => navigation.goBack()} className="mr-4">
          <ArrowLeft color="#064E3B" size={24} />
        </TouchableOpacity>
        <Text className="text-2xl font-black text-text">Mis Recolecciones</Text>
      </View>
      {error ? <TouchableOpacity onPress={fetchRecolecciones} className="mb-4 rounded-xl bg-red-50 p-3"><Text className="text-center font-bold text-red-700">{error}{'\n'}Reintentar</Text></TouchableOpacity> : null}

      {loading ? (
        <ActivityIndicator size="large" color="#064E3B" className="mt-20" />
      ) : (
        <FlatList
          data={recolecciones}
          keyExtractor={(item) => item.id.toString()}
          contentContainerStyle={{ paddingBottom: 100 }}
          ListEmptyComponent={
            <View className="items-center mt-12">
              <Text className="text-subtext text-center text-lg">No has solicitado recolecciones aún.</Text>
            </View>
          }
          renderItem={({ item }) => (
            <View className="bg-surface rounded-2xl p-4 mb-4 shadow-lg shadow-black/5 elevation-3 border border-gray-100">
              <View className="flex-row justify-between mb-2">
                <Text className="font-bold text-text">Recolección #{item.id}</Text>
                <View className={`px-2 py-1 rounded-md ${
                  item.estado === 'pendiente' ? 'bg-amber-100' : 'bg-emerald-100'
                }`}>
                  <Text className={`text-xs font-bold ${
                    item.estado === 'pendiente' ? 'text-amber-700' : 'text-emerald-700'
                  }`}>
                    {item.estado.toUpperCase()}
                  </Text>
                </View>
              </View>
              <Text className="text-subtext text-sm mb-1">Dirección: {item.direccion}</Text>
              <Text className="text-subtext text-sm mb-3">Materiales: {item.materiales}</Text>
              
              {item.estado === 'pendiente' && (
                <CustomButton 
                  title="Generar QR de Seguridad" 
                  variant="outline"
                  onPress={() => openSecureQr(item)}
                  disabled={qrLoading}
                  className="mt-2 border-emerald-500"
                />
              )}

              {item.estado === 'completada' && !item.calificacion_recolector && (
                <CustomButton 
                  title="Calificar Recolector" 
                  onPress={() => {
                    setSelectedRecoleccion(item);
                    setCalificacion(5);
                    setComentario('');
                    setCalificacionModalVisible(true);
                  }}
                  className="mt-2"
                />
              )}
              {item.calificacion_recolector && (
                <View className="flex-row items-center mt-2 bg-gray-50 p-2 rounded-lg">
                  <Star color="#F59E0B" fill="#F59E0B" size={16} />
                  <Text className="ml-2 font-bold text-text">Calificaste con {item.calificacion_recolector} estrellas</Text>
                </View>
              )}
            </View>
          )}
        />
      )}

      {/* Modal de Calificación */}
      <Modal visible={calificacionModalVisible} transparent animationType="slide">
        <View className="flex-1 justify-center bg-black/50 px-6">
          <View className="bg-white rounded-3xl p-6 shadow-2xl">
            <Text className="text-xl font-bold text-text text-center mb-4">Califica el Servicio</Text>
            <Text className="text-center text-subtext mb-6">
              ¿Cómo fue tu experiencia con el recolector?
            </Text>
            
            <View className="flex-row justify-center gap-2 mb-6">
              {[1, 2, 3, 4, 5].map((star) => (
                <TouchableOpacity key={star} onPress={() => setCalificacion(star)}>
                  <Star 
                    size={40} 
                    color={star <= calificacion ? "#F59E0B" : "#D1D5DB"} 
                    fill={star <= calificacion ? "#F59E0B" : "transparent"} 
                  />
                </TouchableOpacity>
              ))}
            </View>

            <TextInput
              placeholder="Deja un comentario (opcional)"
              value={comentario}
              onChangeText={setComentario}
              className="bg-gray-50 border border-gray-200 rounded-xl p-4 text-text mb-6"
              multiline
              numberOfLines={3}
            />

            <View className="flex-row gap-3">
              <View className="flex-1">
                <CustomButton 
                  title="Cancelar" 
                  variant="outline" 
                  onPress={() => setCalificacionModalVisible(false)} 
                />
              </View>
              <View className="flex-1">
                <CustomButton 
                  title="Enviar" 
                  onPress={submitCalificacion} 
                />
              </View>
            </View>
          </View>
        </View>
      </Modal>

      {/* Modal QR de Seguridad */}
      <Modal visible={qrModalVisible} transparent animationType="fade">
        <View className="flex-1 justify-center items-center bg-black/70 px-6">
          <View className="bg-white rounded-3xl p-8 shadow-2xl items-center w-full max-w-sm">
            <Text className="text-xl font-black text-text text-center mb-2">QR de Seguridad</Text>
            <Text className="text-center text-subtext mb-6 text-sm">
              Muestra este código a tu recolector al momento de entregar tus materiales para validar la recolección.
            </Text>
            
            <View className="bg-gray-50 p-6 rounded-2xl border border-gray-200 items-center justify-center mb-6 w-full aspect-square">
              {qrData?.content ? <QRCode value={qrData.content} size={180} color="#064E3B" backgroundColor="#F9FAFB" /> : <ActivityIndicator color="#064E3B" />}
            </View>

            <View className="bg-emerald-50 px-4 py-2 rounded-xl border border-emerald-100 mb-8 w-full">
              <Text className="text-center text-xs text-emerald-600 font-bold uppercase tracking-widest mb-1">RECOLECCIÓN</Text>
              <Text className="text-center font-black text-xl tracking-widest text-[#064E3B]">#{qrData?.collection?.id}</Text>
              <Text className="mt-1 text-center text-xs text-emerald-700">Expira en 10 minutos y solo puede usarse una vez.</Text>
            </View>

            <View className="w-full">
              <CustomButton 
                title="Cerrar" 
                onPress={() => setQrModalVisible(false)} 
              />
            </View>
          </View>
        </View>
      </Modal>
    </SafeAreaView>
  );
}
