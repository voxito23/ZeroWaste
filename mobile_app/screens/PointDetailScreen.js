import React, { useCallback, useEffect, useState } from 'react';
import { ScrollView, Share, Text, TouchableOpacity, View } from 'react-native';
import { ArrowLeft, CheckCircle2, Clock3, MapPin, Navigation, Recycle, Share2, Star } from 'lucide-react-native';
import { useNavigation, useRoute } from '@react-navigation/native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { StatusBar } from 'expo-status-bar';

import RemoteImage from '../components/ui/RemoteImage';
import { normalizeMediaUrl } from '../utils/media';
import { api } from '../api/axios';
import { mobileShareUrl } from '../navigation/linking';
import Skeleton from '../components/ui/Skeleton';

export default function PointDetailScreen() {
  const navigation = useNavigation();
  const route = useRoute();
  const [point, setPoint] = useState(route.params?.point || null);
  const [loading, setLoading] = useState(!route.params?.point);
  const [error, setError] = useState('');
  const pointId = route.params?.id || route.params?.point?.id;

  const loadPoint = useCallback(async () => {
    if (!pointId) { setError('No se indicó el punto que deseas consultar.'); setLoading(false); return; }
    setLoading(true); setError('');
    try { setPoint((await api.get(`/mapa/puntos/${encodeURIComponent(pointId)}`)).data); }
    catch (requestError) { setError(requestError.userMessage || 'No fue posible cargar el punto.'); }
    finally { setLoading(false); }
  }, [pointId]);

  useEffect(() => { if (!point) void loadPoint(); }, [loadPoint, point]);

  const openDirections = () => navigation.navigate('RouteNavigation', { point });

  if (loading && !point) return <SafeAreaView className="flex-1 bg-slate-50" edges={['top', 'bottom']}><StatusBar style="dark" /><View className="flex-row items-center border-b border-slate-100 bg-white px-4 py-3"><Skeleton className="h-11 w-11 rounded-full" /><Skeleton className="ml-4 h-6 w-44 rounded-full" /></View><View className="p-4"><Skeleton className="w-full rounded-[28px]" style={{ aspectRatio: 16 / 10 }} /><View className="mt-4 rounded-[28px] border border-slate-100 bg-white p-5"><Skeleton className="h-8 w-4/5 rounded-full" /><Skeleton className="mt-5 h-20 w-full rounded-2xl" /><Skeleton className="mt-3 h-20 w-full rounded-2xl" /><Skeleton className="mt-3 h-20 w-full rounded-2xl" /><Skeleton className="mt-6 h-14 w-full rounded-2xl" /></View></View></SafeAreaView>;
  if (!point) return <SafeAreaView className="flex-1 items-center justify-center bg-slate-50 px-8"><Text className="text-center font-bold text-slate-900">{error || 'No se encontró el punto de reciclaje.'}</Text><TouchableOpacity onPress={loadPoint} className="mt-5 rounded-full bg-emerald-700 px-7 py-3"><Text className="font-black text-white">Reintentar</Text></TouchableOpacity></SafeAreaView>;

  const materials = Array.isArray(point.materiales) ? point.materiales.join(', ') : point.materiales;
  const rating = Number(point.promedio ?? point.valoracion);
  const reviewCount = Number(point.total_reviews ?? point.total_resenas);
  const reviewSummary = Number.isFinite(rating) && rating > 0
    ? `${rating.toFixed(1)} de 5${Number.isFinite(reviewCount) && reviewCount > 0 ? ` · ${reviewCount} ${reviewCount === 1 ? 'reseña' : 'reseñas'}` : ''}`
    : 'Aún no hay reseñas disponibles';
  return (
    <SafeAreaView className="flex-1 bg-slate-50" edges={['top', 'bottom']}>
      <StatusBar style="dark" />
      <View className="flex-row items-center border-b border-slate-100 bg-white px-4 py-3">
        <TouchableOpacity onPress={() => navigation.goBack()} className="h-11 w-11 items-center justify-center rounded-full bg-slate-100" accessibilityLabel="Volver"><ArrowLeft color="#0F172A" size={21} /></TouchableOpacity>
        <Text className="ml-4 flex-1 text-lg font-black text-slate-900">Punto de reciclaje</Text><TouchableOpacity onPress={() => Share.share({ title: point.nombre, message: `Te comparto este punto de ZeroWaste: ${point.nombre}\n${mobileShareUrl('points', point.id)}` })} className="h-11 w-11 items-center justify-center rounded-full bg-emerald-50" accessibilityLabel="Compartir punto"><Share2 color="#047857" size={19} /></TouchableOpacity>
      </View>
      <ScrollView contentContainerStyle={{ padding: 16, paddingBottom: 36 }} showsVerticalScrollIndicator={false}>
        <View className="overflow-hidden rounded-[28px] border border-slate-100 bg-white"><RemoteImage uri={normalizeMediaUrl(point.imagen_url || point.image_url, 'puntos')} className="w-full" backgroundClassName="bg-white" loadingClassName="bg-white" aspectRatio={16 / 10} accessibilityLabel={`Imagen de ${point.nombre}`} /></View>
        <View className="mt-4 rounded-[28px] border border-slate-100 bg-white p-5">
          <View className="flex-row items-start justify-between"><Text className="mr-3 flex-1 text-[27px] font-black leading-8 text-emerald-950">{point.nombre}</Text><View className="flex-row items-center rounded-full bg-emerald-100 px-3 py-1.5"><CheckCircle2 color="#047857" size={14} /><Text className="ml-1.5 text-xs font-black text-emerald-800">Activo</Text></View></View>
          {point.descripcion ? <Text className="mt-3 text-[15px] leading-6 text-slate-600" style={{ textAlign: 'justify' }}>{point.descripcion}</Text> : null}
          <Info icon={MapPin} label="Dirección" value={point.direccion || 'Dirección no especificada'} />
          <Info icon={Clock3} label="Horario" value={point.horario || 'Consulta el horario antes de acudir'} />
          <Info icon={Recycle} label="Materiales" value={materials || 'Consulta los materiales aceptados al llegar'} />
          <Info icon={Star} label="Reseñas" value={reviewSummary} />
          <TouchableOpacity onPress={openDirections} className="mt-6 min-h-14 flex-row items-center justify-center rounded-[20px] bg-emerald-700 px-5" accessibilityRole="button"><Navigation color="#fff" size={20} /><Text className="ml-2 text-base font-black text-white">Iniciar ruta</Text></TouchableOpacity>
        </View>
      </ScrollView>
    </SafeAreaView>
  );
}

function Info({ icon: Icon, label, value }) {
  return <View className="mt-3 flex-row items-start rounded-[20px] border border-slate-100 bg-slate-50 p-4"><View className="h-10 w-10 items-center justify-center rounded-full bg-emerald-100"><Icon color="#047857" size={19} /></View><View className="ml-3 flex-1"><Text className="text-xs font-black uppercase tracking-wider text-slate-400">{label}</Text><Text className="mt-1 text-[15px] leading-6 text-slate-700">{value}</Text></View></View>;
}
