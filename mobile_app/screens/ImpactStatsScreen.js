import React, { useCallback, useEffect, useRef, useState } from 'react';
import { FlatList, RefreshControl, Text, TouchableOpacity, View } from 'react-native';
import { useFocusEffect, useNavigation } from '@react-navigation/native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { ArrowLeft, Trophy } from 'lucide-react-native';

import { api } from '../api/axios';
import UserAvatar from '../components/ui/UserAvatar';
import Skeleton from '../components/ui/Skeleton';

const memoryCache = { summary: null, ranking: [], updatedAt: 0 };

export default function ImpactStatsScreen() {
  const navigation = useNavigation();
  const mountedRef = useRef(true);
  const loadedRef = useRef(false);
  const requestRef = useRef(0);
  const [summary, setSummary] = useState(memoryCache.summary);
  const [ranking, setRanking] = useState(memoryCache.ranking);
  const [initialLoading, setInitialLoading] = useState(!memoryCache.summary && !memoryCache.ranking.length);
  const [refreshing, setRefreshing] = useState(false);
  const [slow, setSlow] = useState(false);
  const [summaryError, setSummaryError] = useState('');
  const [rankingError, setRankingError] = useState('');

  const load = useCallback(async ({ manual = false } = {}) => {
    const requestId = ++requestRef.current;
    if (manual) setRefreshing(true);
    else if (!memoryCache.summary && !memoryCache.ranking.length) setInitialLoading(true);
    setSlow(false);
    setSummaryError('');
    setRankingError('');
    const slowTimer = setTimeout(() => {
      if (mountedRef.current && requestId === requestRef.current) setSlow(true);
    }, 7000);
    try {
      const [mine, board] = await Promise.allSettled([
        api.get('/impacto/me'),
        api.get('/impacto/ranking'),
      ]);
      if (!mountedRef.current || requestId !== requestRef.current) return;
      if (mine.status === 'fulfilled') {
        setSummary(mine.value.data);
        memoryCache.summary = mine.value.data;
      } else {
        setSummaryError(mine.reason?.userMessage || 'No se pudo actualizar tu resumen.');
      }
      if (board.status === 'fulfilled') {
        const rows = Array.isArray(board.value.data) ? board.value.data : [];
        setRanking(rows);
        memoryCache.ranking = rows;
      } else {
        setRankingError(board.reason?.userMessage || 'No se pudo actualizar el ranking.');
      }
      memoryCache.updatedAt = Date.now();
    } finally {
      clearTimeout(slowTimer);
      if (mountedRef.current && requestId === requestRef.current) {
        loadedRef.current = true;
        setInitialLoading(false);
        setRefreshing(false);
        setSlow(false);
      }
    }
  }, []);

  useEffect(() => () => {
    mountedRef.current = false;
    requestRef.current += 1;
  }, []);

  useFocusEffect(useCallback(() => {
    mountedRef.current = true;
    if (!loadedRef.current) void load();
  }, [load]));

  const warning = rankingError || summaryError;
  return (
    <SafeAreaView className="flex-1 bg-slate-50">
      <View className="flex-row items-center border-b border-slate-100 bg-white px-5 py-4">
        <TouchableOpacity onPress={() => navigation.goBack()} className="h-10 w-10 items-center justify-center rounded-full bg-slate-100" accessibilityLabel="Volver"><ArrowLeft color="#111827" size={20} /></TouchableOpacity>
        <View className="ml-4"><Text className="text-xl font-black text-slate-950">Ranking de impacto</Text><Text className="text-xs font-semibold text-slate-500">Resultados reales de la comunidad</Text></View>
      </View>
      {summary ? <View className="m-5 mb-2 rounded-3xl bg-emerald-900 p-6">
        <Text className="font-bold text-emerald-300">Tu posición #{summary.posicion}</Text>
        <Text className="mt-2 text-4xl font-black text-white">{summary.impacto_historico} impacto</Text>
        <Text className="mt-2 font-bold text-emerald-100">Nivel {summary.nivel}</Text>
        <View className="mt-4 h-2 overflow-hidden rounded-full bg-white/20"><View className="h-full bg-emerald-400" style={{ width: `${Math.min(100, summary.siguiente_nivel ? (summary.progreso_nivel / summary.siguiente_nivel) * 100 : 0)}%` }} /></View>
      </View> : null}
      {warning ? <View className="mx-5 mt-3 rounded-2xl border border-amber-200 bg-amber-50 p-4"><Text className="text-center font-bold text-amber-900">{warning}</Text>{(summary || ranking.length) ? <Text className="mt-1 text-center text-xs text-amber-800">Se conservan los últimos datos disponibles.</Text> : null}<TouchableOpacity onPress={() => load({ manual: true })}><Text className="mt-2 text-center font-black text-amber-900">Reintentar</Text></TouchableOpacity></View> : null}
      {slow ? <Text className="mx-5 mt-3 text-center text-xs font-bold text-slate-500">La consulta está tardando más de lo habitual…</Text> : null}
      {initialLoading ? <View className="px-5 pt-5"><Skeleton className="h-36 rounded-3xl" /><Skeleton className="mt-4 h-20 rounded-2xl" /><Skeleton className="mt-3 h-20 rounded-2xl" /><Skeleton className="mt-3 h-20 rounded-2xl" /></View> : (
        <FlatList
          data={ranking}
          keyExtractor={(item) => `ranking:${item.usuario_id}`}
          refreshControl={<RefreshControl refreshing={refreshing} onRefresh={() => load({ manual: true })} tintColor="#047857" />}
          contentContainerStyle={{ padding: 20, paddingBottom: 40, flexGrow: ranking.length ? 0 : 1 }}
          ListEmptyComponent={!warning ? <View className="flex-1 items-center justify-center px-8"><Trophy color="#94A3B8" size={42} /><Text className="mt-4 text-center font-black text-slate-700">El ranking todavía está vacío</Text><Text className="mt-1 text-center text-sm text-slate-500">Cuando existan movimientos de impacto aparecerán aquí.</Text><TouchableOpacity onPress={() => load({ manual: true })} className="mt-5 rounded-full border border-emerald-700 px-6 py-3"><Text className="font-black text-emerald-800">Actualizar</Text></TouchableOpacity></View> : null}
          renderItem={({ item }) => {
            const avatar = item.avatar_url ?? item.avatar ?? item.foto_perfil;
            return <View className={`mb-3 flex-row items-center rounded-2xl border p-4 ${item.usuario_id === summary?.usuario_id ? 'border-emerald-400 bg-emerald-50' : 'border-slate-100 bg-white'}`}><View className="h-10 w-10 items-center justify-center rounded-full bg-emerald-100"><Text className="font-black text-emerald-800">#{item.posicion}</Text></View><UserAvatar uri={avatar} name={item.nombre} size={44} style={{ marginLeft: 12 }} accessibilityLabel={`Avatar de ${item.nombre}`} /><Text className="ml-3 flex-1 font-black text-slate-900">{item.nombre}</Text><View className="flex-row items-center"><Trophy color="#059669" size={16} /><Text className="ml-1 font-black text-emerald-700">{item.impacto_historico}</Text></View></View>;
          }}
        />
      )}
    </SafeAreaView>
  );
}
