import React, { useState } from 'react';
import { View, Text, TouchableOpacity, Modal, TextInput, Alert, StyleSheet, Platform } from 'react-native';
import { SafeAreaView, useSafeAreaInsets } from 'react-native-safe-area-context';
import CustomButton from '../components/ui/CustomButton';
import { Star, X, CheckCircle2, Truck } from 'lucide-react-native';
import { useNavigation } from '@react-navigation/native';
import { CameraView, useCameraPermissions } from 'expo-camera';
import { api } from '../api/axios';
import { StatusBar } from 'expo-status-bar';
import { useAuth } from '../store/useAuth';

export default function ScannerScreen() {
  const navigation = useNavigation();
  const insets = useSafeAreaInsets();
  const { user } = useAuth();
  const [permission, requestPermission] = useCameraPermissions();
  const [scanned, setScanned] = useState(false);
  
  // Modal state
  const [modalVisible, setModalVisible] = useState(false);
  const [calificacion, setCalificacion] = useState(5);
  const [comentario, setComentario] = useState('');
  const [contenedorId, setContenedorId] = useState(null);
  const [submitting, setSubmitting] = useState(false);

  const isRecolector = user?.rol === 'recolector' || user?.is_admin;

  if (!permission) {
    return <View className="flex-1 bg-black justify-center items-center" />;
  }

  if (!permission.granted) {
    return (
      <SafeAreaView className="flex-1 bg-black justify-center items-center px-8">
        <Text className="text-white text-center text-lg mb-6 font-semibold">
          Necesitamos permiso para usar la cámara y escanear los códigos QR en ZeroWaste.
        </Text>
        <CustomButton onPress={requestPermission} title="Otorgar Permiso" />
      </SafeAreaView>
    );
  }

  const parseId = (raw) => {
    if (!raw) return 1;
    const clean = String(raw).replace(/[^0-9]/g, '');
    const num = parseInt(clean, 10);
    return isNaN(num) ? 1 : num;
  };

  const handleBarcodeScanned = ({ type, data }) => {
    setScanned(true);
    setContenedorId(data);
    setModalVisible(true);
  };

  const handleCompletarRecoleccion = async () => {
    setSubmitting(true);
    const solId = parseId(contenedorId);
    try {
      const res = await api.post(`/recolecciones/${solId}/completar-qr`);
      Alert.alert('Recolección Completada ✅', res.data?.message || 'QR validado. Recolección marcada como completada.');
      setModalVisible(false);
      navigation.navigate('Home');
    } catch (error) {
      console.error(error);
      const msg = error.response?.data?.detail || 'No se pudo validar el QR para esta recolección.';
      Alert.alert('Error al Validar', msg);
    } finally {
      setSubmitting(false);
      setTimeout(() => setScanned(false), 2000);
    }
  };

  const handleCalificar = async () => {
    setSubmitting(true);
    const solId = parseId(contenedorId);
    try {
      await api.post(`/recolecciones/${solId}/calificar`, {
        calificacion: calificacion,
        comentario: comentario || 'Sin comentarios adicionales'
      });
      Alert.alert('Éxito', `Has calificado la recolección con ${calificacion} estrellas.\n¡Gracias por contribuir a ZeroWaste!`);
      setModalVisible(false);
      navigation.navigate('Home');
    } catch (error) {
      console.error(error);
      const msg = error.response?.data?.detail || 'Hubo un problema al enviar la calificación.';
      Alert.alert('Aviso', msg);
    } finally {
      setSubmitting(false);
      setTimeout(() => setScanned(false), 2000);
    }
  };

  const handleCloseModal = () => {
    setModalVisible(false);
    setCalificacion(5);
    setComentario('');
    setTimeout(() => setScanned(false), 1500);
  };

  return (
    <View className="flex-1 bg-black relative">
      <StatusBar style="light" />
      <CameraView 
        style={StyleSheet.absoluteFillObject} 
        facing="back"
        onBarcodeScanned={scanned ? undefined : handleBarcodeScanned}
        barcodeScannerSettings={{
          barcodeTypes: ["qr"],
        }}
      />
      
      {/* Overlay UI */}
      <SafeAreaView className="flex-1 justify-between" pointerEvents="box-none">
        <View className="items-center pt-8" pointerEvents="box-none">
          <View className="bg-black/70 px-6 py-3 rounded-full flex-row items-center gap-2 border border-white/10">
            {isRecolector ? (
              <Truck color="#10B981" size={20} />
            ) : (
              <Star color="#10B981" size={20} />
            )}
            <Text className="text-white text-lg font-black tracking-wide">
              {isRecolector ? 'Escanear QR Recolección' : 'Escanear Código QR'}
            </Text>
          </View>
        </View>

        <View className="items-center justify-center" pointerEvents="none">
          <View className="w-64 h-64 border-2 border-emerald-500 rounded-3xl relative">
            {/* Corners */}
            <View className="absolute top-0 left-0 w-8 h-8 border-t-4 border-l-4 border-emerald-400 rounded-tl-3xl -ml-0.5 -mt-0.5" />
            <View className="absolute top-0 right-0 w-8 h-8 border-t-4 border-r-4 border-emerald-400 rounded-tr-3xl -mr-0.5 -mt-0.5" />
            <View className="absolute bottom-0 left-0 w-8 h-8 border-b-4 border-l-4 border-emerald-400 rounded-bl-3xl -ml-0.5 -mb-0.5" />
            <View className="absolute bottom-0 right-0 w-8 h-8 border-b-4 border-r-4 border-emerald-400 rounded-br-3xl -mr-0.5 -mb-0.5" />
          </View>
        </View>

        <View className="items-center px-8" pointerEvents="box-none" style={{ paddingBottom: Math.max(insets.bottom + 16, 32) }}>
          <Text className="text-white text-sm text-center font-medium bg-black/70 px-4 py-3 rounded-2xl overflow-hidden mb-4 border border-white/10">
            {isRecolector 
              ? 'Apunta al código QR generado en la solicitud para marcarla como completada al instante.' 
              : 'Apunta la cámara al código QR de la recolección para evaluarla.'}
          </Text>
          
          <CustomButton 
            title={scanned ? "Procesando..." : "Simular Escaneo QR #1"} 
            variant="outline"
            className="w-full bg-black/80 border-emerald-500"
            onPress={() => {
              setContenedorId('ZW-SOL-1');
              setScanned(true);
              setModalVisible(true);
            }}
          />
        </View>
      </SafeAreaView>

      {/* Modal diferenciado para Recolector vs Ciudadano */}
      <Modal visible={modalVisible} transparent animationType="slide" onRequestClose={handleCloseModal}>
        <View className="flex-1 justify-end bg-black/60">
          <View className="bg-white rounded-t-3xl p-6 shadow-2xl" style={{ paddingBottom: Math.max(insets.bottom + 20, 24) }}>
            <View className="flex-row justify-between items-center mb-4">
              <Text className="text-2xl font-black text-text">
                {isRecolector ? 'Validar Recolección' : 'Evaluar Recolección'}
              </Text>
              <TouchableOpacity onPress={handleCloseModal} className="p-2 bg-gray-100 rounded-full">
                <X color="#374151" size={20} />
              </TouchableOpacity>
            </View>
            
            {contenedorId && (
              <View className="bg-emerald-50 self-start px-3 py-1.5 rounded-xl border border-emerald-200 mb-4 flex-row items-center gap-1.5">
                <Text className="text-emerald-700 font-extrabold text-sm">
                  Código QR: {String(contenedorId)}
                </Text>
              </View>
            )}

            {isRecolector ? (
              <>
                <View className="bg-gray-50 p-4 rounded-2xl border border-gray-100 mb-6 flex-row items-center gap-3">
                  <View className="w-12 h-12 bg-emerald-100 rounded-full items-center justify-center">
                    <CheckCircle2 color="#059669" size={28} />
                  </View>
                  <View className="flex-1">
                    <Text className="font-extrabold text-gray-800 text-base">Confirmar Entrega</Text>
                    <Text className="text-gray-500 text-xs">
                      Al completar esta acción, se notificará al ciudadano y la solicitud pasará a estado completada.
                    </Text>
                  </View>
                </View>

                <View className="flex-row gap-3">
                  <View className="flex-1">
                    <CustomButton 
                      title="Cancelar" 
                      variant="outline" 
                      onPress={handleCloseModal} 
                    />
                  </View>
                  <View className="flex-1">
                    <CustomButton 
                      title={submitting ? "Validando..." : "Completar"} 
                      onPress={handleCompletarRecoleccion} 
                      disabled={submitting}
                    />
                  </View>
                </View>
              </>
            ) : (
              <>
                <Text className="text-subtext mb-5 font-medium text-base">
                  ¿Cómo calificarías el servicio del recolector en esta entrega?
                </Text>
                
                <View className="flex-row justify-center gap-3 mb-6">
                  {[1, 2, 3, 4, 5].map((star) => (
                    <TouchableOpacity key={star} onPress={() => setCalificacion(star)} className="p-1">
                      <Star 
                        size={44} 
                        color={star <= calificacion ? "#F59E0B" : "#D1D5DB"} 
                        fill={star <= calificacion ? "#F59E0B" : "transparent"} 
                      />
                    </TouchableOpacity>
                  ))}
                </View>

                <TextInput
                  placeholder="Escribe un comentario opcional sobre el servicio..."
                  value={comentario}
                  onChangeText={setComentario}
                  className="bg-gray-50 border border-gray-200 rounded-xl p-4 text-text mb-6 min-h-[100px] font-medium"
                  multiline
                  textAlignVertical="top"
                  placeholderTextColor="#9CA3AF"
                />

                <View className="flex-row gap-3">
                  <View className="flex-1">
                    <CustomButton 
                      title="Omitir" 
                      variant="outline" 
                      onPress={() => {
                        setModalVisible(false);
                        navigation.navigate('Home');
                      }} 
                    />
                  </View>
                  <View className="flex-1">
                    <CustomButton 
                      title={submitting ? "Enviando..." : "Calificar"} 
                      onPress={handleCalificar} 
                      disabled={submitting}
                    />
                  </View>
                </View>
              </>
            )}
          </View>
        </View>
      </Modal>
    </View>
  );
}