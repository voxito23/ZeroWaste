import React, { useRef, useState } from 'react';
import { ActivityIndicator, ScrollView, Text, TouchableOpacity, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { ArrowLeft } from 'lucide-react-native';
import { useNavigation, useRoute } from '@react-navigation/native';
import { api } from '../api/axios';
import RemoteImage from '../components/ui/RemoteImage';
import { useZeroWasteDialog } from '../components/ui/ZeroWasteDialog';
import { rewardImageData } from './RewardsStoreScreen';

export default function RewardDetailScreen() {
  const navigation = useNavigation();
  const reward = useRoute().params?.reward;
  const [submitting, setSubmitting] = useState(false);
  const requestLock = useRef(false);
  const { showDialog } = useZeroWasteDialog();

  const performRedeem = async () => {
    if (requestLock.current || !reward) return;
    requestLock.current = true;
    setSubmitting(true);
    const idempotencyKey = `redeem-${Date.now()}-${Math.random().toString(36).slice(2)}`;
    try {
      await api.post('/impacto/canjes', { recompensa_id: reward.id, cantidad: 1, idempotency_key: idempotencyKey });
      showDialog({
        type: 'success',
        title: 'Canje solicitado',
        message: 'Puedes consultar su estado en Mis canjes.',
        primaryLabel: 'Ver mis canjes',
        onPrimary: () => navigation.replace('MyRedemptions'),
      });
    } catch (error) {
      showDialog({ type: 'error', title: 'No se pudo canjear', message: error.userMessage || 'Revisa tus puntos, el stock e inténtalo nuevamente.' });
    } finally {
      requestLock.current = false;
      setSubmitting(false);
    }
  };

  const redeem = () => showDialog({
    type: 'confirmation',
    title: 'Confirmar canje',
    message: `Se utilizarán ${reward?.costo_puntos || 0} puntos.`,
    primaryLabel: 'Canjear',
    onPrimary: performRedeem,
    secondaryLabel: 'Cancelar',
  });

  const image = rewardImageData(reward);
  return (
    <SafeAreaView className="flex-1 bg-white">
      <View className="px-5 py-4"><TouchableOpacity onPress={() => navigation.goBack()} className="h-10 w-10 items-center justify-center rounded-full bg-gray-100"><ArrowLeft color="#111827" size={20} /></TouchableOpacity></View>
      <ScrollView contentContainerStyle={{ padding: 20, paddingBottom: 40 }}>
        <RemoteImage uri={image.uri} fallbackSource={image.fallbackSource} className="h-72 w-full rounded-3xl bg-gray-50" resizeMode="contain" accessibilityLabel={`Imagen de ${reward?.nombre || 'la recompensa'}`} />
        <Text className="mt-6 text-3xl font-black">{reward?.nombre}</Text>
        <Text className="mt-3 text-base leading-6 text-gray-600">{reward?.descripcion}</Text>
        <Text className="mt-6 text-2xl font-black text-emerald-700">{reward?.costo_puntos} puntos</Text>
        <Text className="mt-1 text-gray-500">Stock disponible: {reward?.stock}</Text>
        <TouchableOpacity disabled={submitting || !reward?.stock} onPress={redeem} className={`mt-8 min-h-12 items-center justify-center rounded-2xl py-4 ${reward?.stock ? 'bg-emerald-700' : 'bg-gray-300'}`}>
          {submitting ? <ActivityIndicator color="white" /> : <Text className="font-black text-white">{reward?.stock ? 'Canjear recompensa' : 'Sin stock'}</Text>}
        </TouchableOpacity>
      </ScrollView>
    </SafeAreaView>
  );
}
