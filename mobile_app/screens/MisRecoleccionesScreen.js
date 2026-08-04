import React, { useEffect, useRef, useState } from 'react';
import { View, Text, FlatList, TouchableOpacity, Modal, TextInput, ActivityIndicator, Keyboard, KeyboardAvoidingView, Platform, ScrollView, TouchableWithoutFeedback } from 'react-native';
import { useAuth } from '../store/useAuth';
import { api } from '../api/axios';
import { useNavigation } from '@react-navigation/native';
import { SafeAreaView, useSafeAreaInsets } from 'react-native-safe-area-context';
import CustomButton from '../components/ui/CustomButton';
import { ArrowLeft, Star } from 'lucide-react-native';
import { StatusBar } from 'expo-status-bar';
import QRCode from 'react-native-qrcode-svg';
import { useZeroWasteDialog } from '../components/ui/ZeroWasteDialog';

export default function MisRecoleccionesScreen() {
  const { user } = useAuth();
  const accountId = String(user?.id ?? '');
  const activeAccountRef = useRef(accountId);
  const ratingScrollRef = useRef(null);
  activeAccountRef.current = accountId;
  const collectorOnly = user?.rol === 'recolector' && !user?.is_admin;
  const navigation = useNavigation();
  const insets = useSafeAreaInsets();
  const { showDialog } = useZeroWasteDialog();
  const [recolecciones, setRecolecciones] = useState([]);
  const [loadedAccountId, setLoadedAccountId] = useState('');
  const [loading, setLoading] = useState(true);

  // Modal Calificacion
  const [calificacionModalVisible, setCalificacionModalVisible] = useState(false);
  const [selectedRecoleccion, setSelectedRecoleccion] = useState(null);
  const [calificacion, setCalificacion] = useState(5);
  const [comentario, setComentario] = useState('');
  const [submittingCalificacion, setSubmittingCalificacion] = useState(false);

  // Modal QR
  const [qrModalVisible, setQrModalVisible] = useState(false);
  const [qrData, setQrData] = useState(null);
  const [qrLoading, setQrLoading] = useState(false);
  const [error, setError] = useState('');

  useEffect(() => {
    setRecolecciones([]);
    setLoadedAccountId('');
    setError('');
    setLoading(true);
    void fetchRecolecciones();
  }, [user?.id]);

  useEffect(() => {
    if (!calificacionModalVisible) return undefined;
    let scrollTimer;
    const eventName = Platform.OS === 'ios' ? 'keyboardWillShow' : 'keyboardDidShow';
    const subscription = Keyboard.addListener(eventName, () => {
      clearTimeout(scrollTimer);
      scrollTimer = setTimeout(() => ratingScrollRef.current?.scrollToEnd({ animated: true }), 80);
    });
    return () => {
      clearTimeout(scrollTimer);
      subscription.remove();
    };
  }, [calificacionModalVisible]);

  const fetchRecolecciones = async () => {
    const requestedAccountId = activeAccountRef.current;
    if (!requestedAccountId) {
      setLoading(false);
      return;
    }
    setError('');
    try {
      const { data } = await api.get('/recolecciones');
      if (requestedAccountId !== activeAccountRef.current) return;
      setRecolecciones(Array.isArray(data) ? data : []);
      setLoadedAccountId(requestedAccountId);
    } catch (e) {
      if (requestedAccountId === activeAccountRef.current) {
        setLoadedAccountId(requestedAccountId);
        setError(e.userMessage || 'No se pudieron cargar las recolecciones.');
      }
    } finally {
      if (requestedAccountId === activeAccountRef.current) setLoading(false);
    }
  };

  const visibleCollections = loadedAccountId === accountId ? recolecciones : [];
  const accountLoading = loading || loadedAccountId !== accountId;

  const openSecureQr = async (collection) => {
    setQrLoading(true);
    setError('');
    try {
      const { data } = await api.post(`/recolecciones/${collection.id}/qr`);
      setQrData({ collection, content: data.content, expiresAt: data.expires_at });
      setQrModalVisible(true);
    } catch (requestError) {
      showDialog({ type: 'error', title: 'No se pudo generar el QR', message: requestError.userMessage || 'Revisa tu conexión e inténtalo nuevamente.' });
    } finally {
      setQrLoading(false);
    }
  };

  const submitCalificacion = async () => {
    if (!selectedRecoleccion || submittingCalificacion) return;
    Keyboard.dismiss();
    setSubmittingCalificacion(true);
    try {
      await api.post(`/recolecciones/${selectedRecoleccion.id}/calificar`, {
        calificacion,
        comentario: comentario.trim() || null,
      });
      setCalificacionModalVisible(false);
      setSelectedRecoleccion(null);
      void fetchRecolecciones();
      showDialog({ type: 'success', title: 'Calificación enviada', message: 'Gracias por calificar a tu recolector.' });
    } catch (requestError) {
      showDialog({ type: 'error', title: 'No se pudo enviar', message: requestError.userMessage || requestError.response?.data?.detail || 'No fue posible enviar la calificación.' });
    } finally {
      setSubmittingCalificacion(false);
    }
  };

  const closeCalificacion = () => {
    if (submittingCalificacion) return;
    Keyboard.dismiss();
    setCalificacionModalVisible(false);
    setSelectedRecoleccion(null);
  };

  return (
    <SafeAreaView className="flex-1 bg-background px-6" edges={['top']}>
      <StatusBar style="dark" />
      <View className="flex-row items-center mb-6">
        <TouchableOpacity onPress={() => navigation.goBack()} className="mr-4">
          <ArrowLeft color="#064E3B" size={24} />
        </TouchableOpacity>
        <Text className="text-2xl font-black text-text">{collectorOnly ? 'Recolecciones disponibles' : 'Mis Recolecciones'}</Text>
      </View>
      {error ? <TouchableOpacity onPress={fetchRecolecciones} className="mb-4 rounded-xl bg-red-50 p-3"><Text className="text-center font-bold text-red-700">{error}{'\n'}Reintentar</Text></TouchableOpacity> : null}

      {accountLoading ? (
        <ActivityIndicator size="large" color="#064E3B" className="mt-20" />
      ) : (
        <FlatList
          data={visibleCollections}
          keyExtractor={(item) => item.id.toString()}
          contentContainerStyle={{ paddingBottom: 100 }}
          ListEmptyComponent={
            <View className="items-center mt-12">
              <Text className="text-subtext text-center text-lg">{collectorOnly ? 'No hay solicitudes disponibles o asignadas a tu cuenta.' : 'No has solicitado recolecciones aún.'}</Text>
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
              
              {item.estado === 'pendiente' && String(item.usuario_id) === String(user?.id) && user?.rol !== 'recolector' && (
                <CustomButton 
                  title="Generar QR de Seguridad" 
                  variant="outline"
                  onPress={() => openSecureQr(item)}
                  disabled={qrLoading}
                  className="mt-2 border-emerald-500"
                />
              )}

              {item.estado === 'completada' && !item.calificacion_recolector && String(item.usuario_id) === String(user?.id) && user?.rol !== 'recolector' && (
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
      <Modal visible={calificacionModalVisible} transparent animationType="slide" statusBarTranslucent navigationBarTranslucent={false} onRequestClose={closeCalificacion}>
        <KeyboardAvoidingView behavior={Platform.OS === 'ios' ? 'padding' : 'height'} style={{ flex: 1 }}>
          <TouchableWithoutFeedback onPress={Keyboard.dismiss} accessible={false}>
            <View className="flex-1 bg-black/50 px-6">
              <ScrollView ref={ratingScrollRef} contentContainerStyle={{ flexGrow: 1, justifyContent: 'center', paddingVertical: Math.max(insets.bottom, 24) }} keyboardShouldPersistTaps="handled" keyboardDismissMode={Platform.OS === 'ios' ? 'interactive' : 'on-drag'} showsVerticalScrollIndicator={false}>
                <View className="rounded-3xl bg-white p-6 shadow-2xl">
                  <Text className="mb-4 text-center text-xl font-bold text-text">Califica el Servicio</Text>
                  <Text className="mb-6 text-center text-subtext">¿Cómo fue tu experiencia con el recolector?</Text>
                  <View className="mb-6 flex-row justify-center gap-2">
                    {[1, 2, 3, 4, 5].map((star) => (
                      <TouchableOpacity key={star} onPress={() => setCalificacion(star)} accessibilityRole="button" accessibilityLabel={`${star} ${star === 1 ? 'estrella' : 'estrellas'}`} accessibilityState={{ selected: star === calificacion }}>
                        <Star size={40} color={star <= calificacion ? '#F59E0B' : '#D1D5DB'} fill={star <= calificacion ? '#F59E0B' : 'transparent'} />
                      </TouchableOpacity>
                    ))}
                  </View>
                  <TextInput placeholder="Deja un comentario (opcional)" placeholderTextColor="#94A3B8" value={comentario} onChangeText={setComentario} className="mb-2 min-h-28 rounded-xl border border-gray-200 bg-gray-50 p-4" style={{ color: '#0F172A', fontSize: 16 }} multiline numberOfLines={3} maxLength={500} textAlignVertical="top" blurOnSubmit={false} />
                  <Text className="mb-5 text-right text-xs font-bold text-slate-400">{comentario.length}/500</Text>
                  <View className="flex-row gap-3">
                    <View className="flex-1"><CustomButton title="Cancelar" variant="outline" onPress={closeCalificacion} disabled={submittingCalificacion} /></View>
                    <View className="flex-1"><CustomButton title="Enviar" onPress={submitCalificacion} loading={submittingCalificacion} /></View>
                  </View>
                </View>
              </ScrollView>
            </View>
          </TouchableWithoutFeedback>
        </KeyboardAvoidingView>
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
