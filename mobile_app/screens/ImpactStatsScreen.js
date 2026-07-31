import React, { useCallback, useState } from 'react';
import { ActivityIndicator, FlatList, RefreshControl, Text, TouchableOpacity, View } from 'react-native';
import { useFocusEffect, useNavigation } from '@react-navigation/native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { ArrowLeft, Trophy } from 'lucide-react-native';
import { api } from '../api/axios';
import RemoteImage from '../components/ui/RemoteImage';
import { normalizeMediaUrl } from '../utils/media';

export default function ImpactStatsScreen() {
  const navigation = useNavigation();
  const [summary, setSummary] = useState(null);
  const [ranking, setRanking] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const load = useCallback(async () => {
    setLoading(true); setError('');
    try {
      const [mine, board] = await Promise.all([api.get('/impacto/me'), api.get('/impacto/ranking')]);
      setSummary(mine.data); setRanking(Array.isArray(board.data) ? board.data : []);
    } catch (requestError) { setError(requestError.userMessage || 'No se pudo cargar tu impacto.'); }
    finally { setLoading(false); }
  }, []);
  useFocusEffect(useCallback(() => { load(); }, [load]));

  return <SafeAreaView className="flex-1 bg-gray-50">
    <View className="flex-row items-center border-b border-gray-100 bg-white px-5 py-4">
      <TouchableOpacity onPress={() => navigation.goBack()} className="h-10 w-10 items-center justify-center rounded-full bg-gray-100"><ArrowLeft color="#111827" size={20} /></TouchableOpacity>
      <Text className="ml-4 text-xl font-black text-gray-900">Ranking de impacto</Text>
    </View>
    {summary ? <View className="m-5 rounded-3xl bg-emerald-900 p-6">
      <Text className="font-bold text-emerald-300">Tu posición #{summary.posicion}</Text>
      <Text className="mt-2 text-4xl font-black text-white">{summary.impacto_historico} impacto</Text>
      <Text className="mt-2 font-bold text-emerald-100">{summary.puntos_disponibles} puntos disponibles · Nivel {summary.nivel}</Text>
      <View className="mt-4 h-2 overflow-hidden rounded-full bg-white/20"><View className="h-full bg-emerald-400" style={{ width: `${Math.min(100, (summary.progreso_nivel / summary.siguiente_nivel) * 100)}%` }} /></View>
    </View> : null}
    {error ? <View className="mx-5 rounded-2xl bg-red-50 p-4"><Text className="text-center font-bold text-red-700">{error}</Text><TouchableOpacity onPress={load}><Text className="mt-2 text-center font-black text-red-700">Reintentar</Text></TouchableOpacity></View> : null}
    {loading && !summary ? <ActivityIndicator className="mt-10" color="#047857" /> : null}
    <FlatList data={ranking} keyExtractor={(item) => String(item.usuario_id)} refreshControl={<RefreshControl refreshing={loading} onRefresh={load} />} contentContainerStyle={{ padding: 20, paddingBottom: 40 }} ListEmptyComponent={!loading && !error ? <Text className="text-center text-gray-500">Todavía no hay movimientos de impacto.</Text> : null} renderItem={({ item }) => {
      const avatar = normalizeMediaUrl(item.avatar_url ?? item.avatar ?? item.foto_perfil, 'perfiles');
      return <View className={`mb-3 flex-row items-center rounded-2xl border p-4 ${item.usuario_id === summary?.usuario_id ? 'border-emerald-400 bg-emerald-50' : 'border-gray-100 bg-white'}`}>
        <View className="h-10 w-10 items-center justify-center rounded-full bg-emerald-100"><Text className="font-black text-emerald-800">#{item.posicion}</Text></View>
        <RemoteImage uri={avatar} fallbackSource={require('../assets/images/logo.png')} className="ml-3 h-11 w-11 rounded-full" aspectRatio={1} accessibilityLabel={`Avatar de ${item.nombre}`} />
        <Text className="ml-3 flex-1 font-black text-gray-900">{item.nombre}</Text>
        <View className="flex-row items-center"><Trophy color="#059669" size={16} /><Text className="ml-1 font-black text-emerald-700">{item.impacto_historico}</Text></View>
      </View>;
    }} />
  </SafeAreaView>;
}
