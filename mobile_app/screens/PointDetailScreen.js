import React from 'react';
import { Alert, ScrollView, Text, TouchableOpacity, View } from 'react-native';
import { ArrowLeft, Clock3, MapPin, Navigation, Recycle, Star } from 'lucide-react-native';
import { useNavigation, useRoute } from '@react-navigation/native';
import { SafeAreaView } from 'react-native-safe-area-context';

import RemoteImage from '../components/ui/RemoteImage';
import { normalizeMediaUrl } from '../utils/media';
import { openDirections as openNativeDirections } from '../utils/directions';

export default function PointDetailScreen() {
  const navigation = useNavigation();
  const point = useRoute().params?.point;

  const openDirections = async () => {
    try {
      await openNativeDirections(point);
    } catch (error) {
      Alert.alert('No fue posible abrir el mapa', error.message || 'Revisa que tengas una aplicación de navegación disponible.');
    }
  };

  if (!point) return <SafeAreaView className="flex-1 items-center justify-center bg-slate-50 px-8"><Text className="font-bold text-slate-900">No se encontró el punto de reciclaje.</Text></SafeAreaView>;

  const materials = Array.isArray(point.materiales) ? point.materiales.join(', ') : point.materiales;
  return (
    <SafeAreaView className="flex-1 bg-slate-50" edges={['top', 'bottom']}>
      <View className="flex-row items-center border-b border-slate-100 bg-white px-4 py-3">
        <TouchableOpacity onPress={() => navigation.goBack()} className="h-11 w-11 items-center justify-center rounded-full bg-slate-100" accessibilityLabel="Volver"><ArrowLeft color="#0F172A" size={21} /></TouchableOpacity>
        <Text className="ml-4 text-lg font-black text-slate-900">Punto de reciclaje</Text>
      </View>
      <ScrollView contentContainerStyle={{ paddingBottom: 32 }}>
        <RemoteImage uri={normalizeMediaUrl(point.imagen_url || point.image_url, 'puntos')} className="w-full" aspectRatio={16 / 10} accessibilityLabel={`Imagen de ${point.nombre}`} />
        <View className="px-5 py-6">
          <View className="flex-row items-center justify-between"><Text className="flex-1 text-[28px] font-black leading-8 text-emerald-950">{point.nombre}</Text><View className="rounded-full bg-emerald-100 px-3 py-1"><Text className="text-xs font-black text-emerald-800">Activo</Text></View></View>
          <Info icon={MapPin} label="Dirección" value={point.direccion || 'Dirección no especificada'} />
          <Info icon={Clock3} label="Horario" value={point.horario || 'Consulta el horario antes de acudir'} />
          <Info icon={Recycle} label="Materiales" value={materials || 'Consulta los materiales aceptados al llegar'} />
          <Info icon={Star} label="Reseñas" value={point.resenas_resumen || 'Aún no hay reseñas disponibles'} />
          <TouchableOpacity onPress={openDirections} className="mt-6 flex-row items-center justify-center rounded-2xl bg-emerald-700 py-4" accessibilityRole="button"><Navigation color="#fff" size={20} /><Text className="ml-2 text-base font-black text-white">Ir ahora</Text></TouchableOpacity>
        </View>
      </ScrollView>
    </SafeAreaView>
  );
}

function Info({ icon: Icon, label, value }) {
  return <View className="mt-3 flex-row items-start rounded-2xl bg-white p-4"><Icon color="#059669" size={20} /><View className="ml-3 flex-1"><Text className="text-xs font-black uppercase tracking-wider text-slate-400">{label}</Text><Text className="mt-1 text-[15px] leading-6 text-slate-700">{value}</Text></View></View>;
}
