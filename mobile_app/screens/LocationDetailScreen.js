import React from 'react';
import { Alert, Linking, ScrollView, Text, TouchableOpacity, View } from 'react-native';
import { ArrowLeft, MapPin, Navigation, Recycle } from 'lucide-react-native';
import { useNavigation, useRoute } from '@react-navigation/native';
import { SafeAreaView } from 'react-native-safe-area-context';

import RemoteImage from '../components/ui/RemoteImage';
import { normalizeMediaUrl } from '../utils/media';


export const openPointDirections = async (point) => {
  const latitude = Number(point?.latitud);
  const longitude = Number(point?.longitud);
  if (!Number.isFinite(latitude) || !Number.isFinite(longitude)) {
    Alert.alert('Ubicación no disponible', 'Este punto no tiene coordenadas válidas.');
    return;
  }
  const destination = `${latitude},${longitude}`;
  const url = `https://www.google.com/maps/dir/?api=1&destination=${encodeURIComponent(destination)}`;
  try {
    await Linking.openURL(url);
  } catch {
    Alert.alert('No fue posible abrir el mapa', 'Revisa que tengas una aplicación de navegación disponible.');
  }
};

export default function LocationDetailScreen() {
  const navigation = useNavigation();
  const route = useRoute();
  const point = route.params?.point;
  const imageUrl = normalizeMediaUrl(point?.image_url ?? point?.imagen, 'puntos');

  if (!point) {
    return (
      <SafeAreaView className="flex-1 items-center justify-center bg-slate-50 px-8">
        <Text className="text-center font-black text-slate-900">No se encontró el punto de reciclaje.</Text>
        <TouchableOpacity onPress={() => navigation.goBack()} className="mt-5 rounded-full bg-emerald-700 px-7 py-3"><Text className="font-black text-white">Volver</Text></TouchableOpacity>
      </SafeAreaView>
    );
  }

  return (
    <SafeAreaView className="flex-1 bg-slate-50" edges={['top', 'bottom']}>
      <View className="flex-row items-center border-b border-slate-100 bg-white px-4 py-3">
        <TouchableOpacity onPress={() => navigation.goBack()} className="h-11 w-11 items-center justify-center rounded-full bg-slate-100" accessibilityLabel="Volver">
          <ArrowLeft color="#0F172A" size={21} />
        </TouchableOpacity>
        <Text className="ml-4 text-lg font-black text-slate-900">Punto de reciclaje</Text>
      </View>
      <ScrollView contentContainerStyle={{ paddingBottom: 28 }}>
        <RemoteImage uri={imageUrl} className="w-full" aspectRatio={16 / 10} accessibilityLabel={`Imagen de ${point.nombre}`} />
        <View className="px-5 py-6">
          <Text className="text-[28px] font-black leading-8 text-emerald-950">{point.nombre}</Text>
          <View className="mt-5 flex-row items-start rounded-2xl bg-white p-4">
            <MapPin color="#059669" size={20} />
            <View className="ml-3 flex-1"><Text className="text-xs font-black uppercase tracking-wider text-slate-400">Dirección</Text><Text className="mt-1 text-[15px] leading-6 text-slate-700">{point.direccion || 'Dirección no especificada'}</Text></View>
          </View>
          <View className="mt-3 flex-row items-start rounded-2xl bg-white p-4">
            <Recycle color="#059669" size={20} />
            <View className="ml-3 flex-1"><Text className="text-xs font-black uppercase tracking-wider text-slate-400">Materiales</Text><Text className="mt-1 text-[15px] leading-6 text-slate-700">{point.materiales || 'Consulta los materiales aceptados al llegar.'}</Text></View>
          </View>
          <View className="mt-3 rounded-2xl bg-white p-4"><Text className="text-xs font-black uppercase tracking-wider text-slate-400">Horario</Text><Text className="mt-1 text-[15px] leading-6 text-slate-700">{point.horario || 'Consulta el horario antes de acudir.'}</Text></View>
          <TouchableOpacity onPress={() => openPointDirections(point)} className="mt-6 flex-row items-center justify-center rounded-2xl bg-emerald-700 py-4" accessibilityRole="button" accessibilityLabel="Abrir navegación externa">
            <Navigation color="#fff" size={20} />
            <Text className="ml-2 text-base font-black text-white">Ir ahora</Text>
          </TouchableOpacity>
        </View>
      </ScrollView>
    </SafeAreaView>
  );
}
