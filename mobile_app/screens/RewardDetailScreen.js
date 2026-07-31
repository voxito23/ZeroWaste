import React, { useState } from 'react';
import { ActivityIndicator, Alert, ScrollView, Text, TouchableOpacity, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { ArrowLeft } from 'lucide-react-native';
import { useNavigation, useRoute } from '@react-navigation/native';
import { api } from '../api/axios';
import RemoteImage from '../components/ui/RemoteImage';
import { rewardImageData } from './RewardsStoreScreen';

export default function RewardDetailScreen() {
  const navigation = useNavigation(); const reward = useRoute().params?.reward; const [submitting, setSubmitting] = useState(false);
  const redeem = () => Alert.alert('Confirmar canje', `Se utilizarán ${reward.costo_puntos} puntos.`, [{ text: 'Cancelar', style: 'cancel' }, { text: 'Canjear', onPress: async () => { setSubmitting(true); try { await api.post('/impacto/canjes', { recompensa_id: reward.id, cantidad: 1 }); Alert.alert('Canje solicitado', 'Puedes consultar su estado en Mis canjes.', [{ text: 'Ver mis canjes', onPress: () => navigation.replace('MyRedemptions') }]); } catch (e) { Alert.alert('No se pudo canjear', e.userMessage); } finally { setSubmitting(false); } } }]);
  const image = rewardImageData(reward);
  return <SafeAreaView className="flex-1 bg-white"><View className="px-5 py-4"><TouchableOpacity onPress={() => navigation.goBack()} className="h-10 w-10 items-center justify-center rounded-full bg-gray-100"><ArrowLeft color="#111827" size={20}/></TouchableOpacity></View><ScrollView contentContainerStyle={{ padding: 20, paddingBottom: 40 }}><RemoteImage uri={image.uri} fallbackSource={image.fallbackSource} className="h-72 w-full rounded-3xl bg-gray-50" resizeMode="contain" accessibilityLabel={`Imagen de ${reward?.nombre || 'la recompensa'}`}/><Text className="mt-6 text-3xl font-black">{reward?.nombre}</Text><Text className="mt-3 text-base leading-6 text-gray-600">{reward?.descripcion}</Text><Text className="mt-6 text-2xl font-black text-emerald-700">{reward?.costo_puntos} puntos</Text><Text className="mt-1 text-gray-500">Stock disponible: {reward?.stock}</Text><TouchableOpacity disabled={submitting || !reward?.stock} onPress={redeem} className={`mt-8 items-center rounded-2xl py-4 ${reward?.stock ? 'bg-emerald-700' : 'bg-gray-300'}`}>{submitting ? <ActivityIndicator color="white"/> : <Text className="font-black text-white">{reward?.stock ? 'Canjear recompensa' : 'Sin stock'}</Text>}</TouchableOpacity></ScrollView></SafeAreaView>;
}
