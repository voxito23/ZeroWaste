import React, { useState, useEffect } from 'react';
import { View, Text, TouchableOpacity, Modal, TextInput, Alert, StyleSheet, Platform } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import CustomButton from '../components/ui/CustomButton';
import { Star, X } from 'lucide-react-native';
import { useNavigation } from '@react-navigation/native';
import { CameraView, useCameraPermissions } from 'expo-camera';
import { api } from '../api/axios';
import { StatusBar } from 'expo-status-bar';

export default function ScannerScreen() {
  const navigation = useNavigation();
  const [permission, requestPermission] = useCameraPermissions();
  const [scanned, setScanned] = useState(false);
  
  // Modal state
  const [modalVisible, setModalVisible] = useState(false);
  const [calificacion, setCalificacion] = useState(5);
  const [comentario, setComentario] = useState('');
  const [contenedorId, setContenedorId] = useState(null);
  const [submitting, setSubmitting] = useState(false);

  if (!permission) {
    // Camera permissions are still loading.
    return <View className="flex-1 bg-black justify-center items-center" />;
  }

  if (!permission.granted) {
    // Camera permissions are not granted yet.
    return (
      <SafeAreaView className="flex-1 bg-black justify-center items-center px-8">
        <Text className="text-white text-center text-lg mb-6">Necesitamos permiso para usar la cámara para escanear los contenedores.</Text>
        <CustomButton onPress={requestPermission} title="Otorgar Permiso" />
      </SafeAreaView>
    );
  }

  const handleBarcodeScanned = ({ type, data }) => {
    setScanned(true);
    // Assuming QR contains the container ID (e.g., "ZW-CONT-123" or just an ID integer)
    setContenedorId(data);
    setModalVisible(true);
  };

  const handleCalificar = async () => {
    setSubmitting(true);
    try {
      // Usar la ruta correcta si existe en FastAPI o simular éxito
      // await api.post(`/recolecciones/${contenedorId}/calificar`, {
      //   estrellas: calificacion,
      //   comentario
      // });
      
      Alert.alert('Éxito', `Has calificado el contenedor con ${calificacion} estrellas.\n¡Gracias por contribuir!`);
      setModalVisible(false);
      navigation.navigate('Home');
    } catch (error) {
      console.error(error);
      Alert.alert('Error', 'Hubo un problema al enviar la calificación.');
    } finally {
      setSubmitting(false);
      // Permitir escanear de nuevo al cerrar
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
      <SafeAreaView className="flex-1 justify-between pointer-events-none">
        <View className="items-center pt-12">
          <Text className="text-white text-2xl font-black bg-black/50 px-6 py-2 rounded-full overflow-hidden">
            Escanear Código QR
          </Text>
        </View>

        {/* Viewfinder frame */}
        <View className="items-center justify-center">
          <View className="w-64 h-64 border-2 border-emerald-500 rounded-3xl relative">
            {/* Corners */}
            <View className="absolute top-0 left-0 w-8 h-8 border-t-4 border-l-4 border-emerald-400 rounded-tl-3xl -ml-0.5 -mt-0.5" />
            <View className="absolute top-0 right-0 w-8 h-8 border-t-4 border-r-4 border-emerald-400 rounded-tr-3xl -mr-0.5 -mt-0.5" />
            <View className="absolute bottom-0 left-0 w-8 h-8 border-b-4 border-l-4 border-emerald-400 rounded-bl-3xl -ml-0.5 -mb-0.5" />
            <View className="absolute bottom-0 right-0 w-8 h-8 border-b-4 border-r-4 border-emerald-400 rounded-br-3xl -mr-0.5 -mb-0.5" />
          </View>
        </View>

        <View className="items-center pb-24 px-8 pointer-events-auto">
          <Text className="text-white text-base text-center font-medium bg-black/60 px-4 py-3 rounded-2xl overflow-hidden mb-6">
            Apunta la cámara al código QR del contenedor para registrar tu reciclaje.
          </Text>
          
          <CustomButton 
            title={scanned ? "Procesando..." : "Ingresar código manualmente"} 
            variant="outline"
            className="w-full bg-black/80 border-emerald-500"
            onPress={() => {
              setContenedorId('MANUAL-001');
              setScanned(true);
              setModalVisible(true);
            }}
          />
        </View>
      </SafeAreaView>

      {/* Modal para calificar el contenedor */}
      <Modal visible={modalVisible} transparent animationType="slide" onRequestClose={handleCloseModal}>
        <View className="flex-1 justify-end bg-black/50">
          <View className="bg-white rounded-t-3xl p-6 shadow-2xl">
            <View className="flex-row justify-between items-center mb-4">
              <Text className="text-2xl font-black text-text">Contenedor Identificado</Text>
              <TouchableOpacity onPress={handleCloseModal} className="p-2 bg-gray-100 rounded-full">
                <X color="#374151" size={20} />
              </TouchableOpacity>
            </View>
            
            {contenedorId && (
              <Text className="text-emerald-600 font-bold mb-4 bg-emerald-50 self-start px-3 py-1 rounded-lg">
                ID: {contenedorId}
              </Text>
            )}

            <Text className="text-subtext mb-6 font-medium text-base">
              ¿En qué estado encontraste este contenedor? Tu calificación nos ayuda a mantenerlos limpios.
            </Text>
            
            <View className="flex-row justify-center gap-3 mb-6">
              {[1, 2, 3, 4, 5].map((star) => (
                <TouchableOpacity key={star} onPress={() => setCalificacion(star)} className="p-1">
                  <Star 
                    size={48} 
                    color={star <= calificacion ? "#F59E0B" : "#D1D5DB"} 
                    fill={star <= calificacion ? "#F59E0B" : "transparent"} 
                  />
                </TouchableOpacity>
              ))}
            </View>

            <TextInput
              placeholder="Reportar un problema o dejar comentario (Opcional)"
              value={comentario}
              onChangeText={setComentario}
              className="bg-gray-50 border border-gray-200 rounded-xl p-4 text-text mb-6 min-h-[100px]"
              multiline
              textAlignVertical="top"
            />

            <View className="flex-row gap-3 pb-8">
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
                  title={submitting ? "Enviando..." : "Enviar"} 
                  onPress={handleCalificar} 
                  disabled={submitting}
                />
              </View>
            </View>
          </View>
        </View>
      </Modal>
    </View>
  );
}