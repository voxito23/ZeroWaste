import React, { useCallback, useRef, useState } from 'react';
import { ActivityIndicator, FlatList, RefreshControl, Text, TouchableOpacity, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { ArrowLeft, CalendarDays, CheckCircle2, CircleX, Clock3, Coins, PackageCheck, RefreshCw, ShoppingBag } from 'lucide-react-native';
import { useFocusEffect, useNavigation } from '@react-navigation/native';

import { api } from '../api/axios';
import RemoteImage from '../components/ui/RemoteImage';
import { formatRelativeDate } from '../utils/date';

const STATUS_META = {
  SOLICITADA: { label: 'Solicitada', Icon: Clock3, background: 'bg-sky-50', border: 'border-sky-200', text: 'text-sky-700', color: '#0369A1' },
  APROBADA: { label: 'Aprobada', Icon: CheckCircle2, background: 'bg-emerald-50', border: 'border-emerald-200', text: 'text-emerald-700', color: '#047857' },
  EN_PREPARACION: { label: 'En preparación', Icon: ShoppingBag, background: 'bg-amber-50', border: 'border-amber-200', text: 'text-amber-700', color: '#B45309' },
  LISTA_PARA_ENTREGAR: { label: 'Lista para entregar', Icon: PackageCheck, background: 'bg-violet-50', border: 'border-violet-200', text: 'text-violet-700', color: '#6D28D9' },
  ENTREGADA: { label: 'Entregada', Icon: PackageCheck, background: 'bg-emerald-50', border: 'border-emerald-200', text: 'text-emerald-700', color: '#047857' },
  RECHAZADA: { label: 'Rechazada', Icon: CircleX, background: 'bg-red-50', border: 'border-red-200', text: 'text-red-700', color: '#B91C1C' },
  CANCELADA: { label: 'Cancelada', Icon: CircleX, background: 'bg-slate-100', border: 'border-slate-200', text: 'text-slate-600', color: '#475569' },
};

const redemptionStatus = (value) => {
  const code = String(value || '').trim().toUpperCase();
  return STATUS_META[code] || {
    label: code.split('_').filter(Boolean).map((part) => part.charAt(0) + part.slice(1).toLowerCase()).join(' ') || 'En revisión',
    Icon: Clock3,
    background: 'bg-slate-50',
    border: 'border-slate-200',
    text: 'text-slate-600',
    color: '#475569',
  };
};

export default function MyRedemptionsScreen() {
  const navigation = useNavigation();
  const requestRef = useRef(0);
  const [rows, setRows] = useState([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [error, setError] = useState('');

  const load = useCallback(async ({ refresh = false } = {}) => {
    const requestId = ++requestRef.current;
    if (refresh) setRefreshing(true); else setLoading(true);
    setError('');
    try {
      const { data } = await api.get('/impacto/canjes');
      if (requestId === requestRef.current) setRows(Array.isArray(data) ? data : []);
    } catch (requestError) {
      if (requestId === requestRef.current) setError(requestError.userMessage || 'No fue posible cargar tus canjes.');
    } finally {
      if (requestId === requestRef.current) { setLoading(false); setRefreshing(false); }
    }
  }, []);

  useFocusEffect(useCallback(() => {
    void load();
    return () => { requestRef.current += 1; };
  }, [load]));

  return (
    <SafeAreaView className="flex-1 bg-slate-50" edges={['top']}>
      <View className="flex-row items-center border-b border-slate-100 bg-white px-5 py-4">
        <TouchableOpacity onPress={() => navigation.goBack()} className="h-11 w-11 items-center justify-center rounded-full bg-slate-100" accessibilityLabel="Volver"><ArrowLeft color="#0F172A" size={20} /></TouchableOpacity>
        <View className="ml-4 flex-1"><Text className="text-xl font-black text-slate-950">Mis canjes</Text><Text className="mt-0.5 text-xs font-semibold text-slate-500">Seguimiento de tus recompensas</Text></View>
        <TouchableOpacity onPress={() => load({ refresh: true })} disabled={refreshing} className="h-11 w-11 items-center justify-center rounded-full bg-emerald-50" accessibilityLabel="Actualizar canjes">{refreshing ? <ActivityIndicator color="#047857" /> : <RefreshCw color="#047857" size={19} />}</TouchableOpacity>
      </View>
      {error ? <TouchableOpacity onPress={() => load({ refresh: true })} className="mx-5 mt-4 flex-row items-center rounded-2xl border border-red-200 bg-red-50 p-4"><RefreshCw color="#B91C1C" size={18} /><Text className="ml-3 flex-1 font-bold text-red-700">{error} Toca para reintentar.</Text></TouchableOpacity> : null}
      {loading && !rows.length ? <View className="items-center pt-16"><ActivityIndicator size="large" color="#047857" /><Text className="mt-4 font-bold text-slate-500">Consultando tus canjes…</Text></View> : (
        <FlatList
          data={rows}
          keyExtractor={(item) => String(item.id)}
          refreshControl={<RefreshControl refreshing={refreshing} onRefresh={() => load({ refresh: true })} tintColor="#047857" colors={['#047857']} progressBackgroundColor="#FFFFFF" />}
          contentContainerStyle={{ padding: 20, paddingBottom: 36 }}
          ItemSeparatorComponent={() => <View className="h-3" />}
          ListEmptyComponent={!error ? <View className="items-center px-8 pt-20"><View className="h-20 w-20 items-center justify-center rounded-full bg-emerald-50"><ShoppingBag color="#059669" size={34} /></View><Text className="mt-5 text-lg font-black text-slate-900">Aún no tienes canjes</Text><Text className="mt-2 text-center leading-5 text-slate-500">Cuando elijas una recompensa podrás consultar aquí cada etapa de entrega.</Text></View> : null}
          renderItem={({ item }) => {
            const status = redemptionStatus(item.estado);
            const StatusIcon = status.Icon;
            return <View className="overflow-hidden rounded-[26px] border border-slate-100 bg-white shadow-sm shadow-slate-200"><View className="flex-row p-4"><View className="h-20 w-20 overflow-hidden rounded-2xl bg-slate-50"><RemoteImage uri={item.imagen_url} className="h-full w-full" resizeMode="contain" backgroundClassName="bg-white" loadingClassName="bg-white" accessibilityLabel={`Imagen de ${item.recompensa}`} /></View><View className="ml-4 min-w-0 flex-1"><Text className="text-[17px] font-black leading-5 text-slate-950" numberOfLines={2}>{item.recompensa}</Text><View className="mt-2 flex-row items-center"><Coins color="#047857" size={16} /><Text className="ml-1.5 font-black text-emerald-700">{Number(item.puntos_utilizados || 0).toLocaleString('es-MX')} puntos</Text></View><Text className="mt-1 text-xs font-semibold text-slate-400">Cantidad: {item.cantidad || 1}</Text></View></View><View className="mx-4 mb-4 flex-row items-center justify-between border-t border-slate-100 pt-3"><View className={`flex-row items-center rounded-full border px-3 py-2 ${status.background} ${status.border}`}><StatusIcon color={status.color} size={16} /><Text className={`ml-2 text-xs font-black ${status.text}`}>{status.label}</Text></View><View className="flex-row items-center"><CalendarDays color="#94A3B8" size={14} /><Text className="ml-1.5 text-xs font-semibold text-slate-400">{formatRelativeDate(item.created_at)}</Text></View></View></View>;
          }}
        />
      )}
    </SafeAreaView>
  );
}
