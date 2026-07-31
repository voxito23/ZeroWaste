import React, { useCallback, useState } from 'react';
import { ActivityIndicator, FlatList, RefreshControl, Text, TouchableOpacity, View } from 'react-native';
import { useFocusEffect, useNavigation } from '@react-navigation/native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { ArrowLeft, Gift } from 'lucide-react-native';
import { api } from '../api/axios';
import RemoteImage from '../components/ui/RemoteImage';
import { normalizeMediaUrl } from '../utils/media';

const localImages = {
  'termo_reutilizable.png': require('../assets/recompensas/termo_reutilizable.png'),
  'bolsa_reutilizable.png': require('../assets/recompensas/bolsa_reutilizable.png'),
  'kit_botes_separacion.png': require('../assets/recompensas/kit_botes_separacion.png'),
  'kit_cubiertos_reutilizables.png': require('../assets/recompensas/kit_cubiertos_reutilizables.png'),
  'compostera_domestica.png': require('../assets/recompensas/compostera_domestica.png'),
};

export const rewardImageData = (reward) => {
  const value = reward?.image_url ?? reward?.imagen_url ?? reward?.imagen;
  const cleanValue = typeof value === 'string' ? value.split(/[?#]/)[0].replace(/\\/g, '/') : '';
  const filename = cleanValue.split('/').pop();
  return {
    uri: normalizeMediaUrl(value, 'recompensas'),
    fallbackSource: localImages[filename] || require('../assets/images/logo.png'),
  };
};

export default function RewardsStoreScreen() {
  const navigation = useNavigation();
  const [items, setItems] = useState([]);
  const [points, setPoints] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  const load = useCallback(async () => {
    setLoading(true);
    setError('');
    try {
      const [catalog, mine] = await Promise.all([api.get('/impacto/recompensas'), api.get('/impacto/me')]);
      setItems(Array.isArray(catalog.data) ? catalog.data : []);
      setPoints(mine.data?.puntos_disponibles);
    } catch (requestError) {
      setError(requestError.userMessage || 'No se pudo cargar la tienda.');
    } finally {
      setLoading(false);
    }
  }, []);

  useFocusEffect(useCallback(() => { load(); }, [load]));

  return (
    <SafeAreaView className="flex-1 bg-gray-50">
      <View className="flex-row items-center bg-white px-5 py-4">
        <TouchableOpacity onPress={() => navigation.goBack()} className="h-10 w-10 items-center justify-center rounded-full bg-gray-100">
          <ArrowLeft color="#111827" size={20} />
        </TouchableOpacity>
        <View className="ml-4 flex-1">
          <Text className="text-xl font-black">Tienda de recompensas</Text>
          <Text className="font-bold text-emerald-700">{points ?? '—'} puntos disponibles</Text>
        </View>
      </View>
      {error ? (
        <TouchableOpacity onPress={load} className="m-5 rounded-2xl bg-red-50 p-4">
          <Text className="text-center font-bold text-red-700">{error}{'\n'}Reintentar</Text>
        </TouchableOpacity>
      ) : null}
      {loading && !items.length ? <ActivityIndicator className="mt-10" color="#047857" /> : null}
      <FlatList
        data={items}
        numColumns={2}
        keyExtractor={(item) => String(item.id)}
        refreshControl={<RefreshControl refreshing={loading} onRefresh={load} />}
        columnWrapperStyle={{ gap: 12 }}
        contentContainerStyle={{ padding: 16, gap: 12, paddingBottom: 40 }}
        renderItem={({ item }) => {
          const image = rewardImageData(item);
          return (
            <TouchableOpacity onPress={() => navigation.navigate('RewardDetail', { reward: item })} className="flex-1 overflow-hidden rounded-3xl border border-gray-100 bg-white">
              <RemoteImage uri={image.uri} fallbackSource={image.fallbackSource} className="h-36 w-full bg-gray-100" resizeMode="contain" accessibilityLabel={`Imagen de ${item.nombre}`} />
              <View className="p-4">
                <Text className="font-black text-gray-900" numberOfLines={2}>{item.nombre}</Text>
                <Text className="mt-2 font-black text-emerald-700">{item.costo_puntos} pts</Text>
                <Text className="mt-1 text-xs text-gray-500">Stock: {item.stock}</Text>
              </View>
            </TouchableOpacity>
          );
        }}
        ListEmptyComponent={!loading && !error ? (
          <View className="items-center pt-16">
            <Gift color="#9CA3AF" size={42} />
            <Text className="mt-3 text-gray-500">No hay recompensas activas.</Text>
          </View>
        ) : null}
      />
    </SafeAreaView>
  );
}
