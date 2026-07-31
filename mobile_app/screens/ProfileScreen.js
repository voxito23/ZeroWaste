import React, { useCallback, useState } from 'react';
import { ActivityIndicator, Alert, Image, ScrollView, Text, TouchableOpacity, View } from 'react-native';
import { useFocusEffect, useNavigation } from '@react-navigation/native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { Award, ChevronRight, Gift, History, LogOut, MapPin, Settings, Shield, UserRoundPen } from 'lucide-react-native';
import { StatusBar } from 'expo-status-bar';

import { api } from '../api/axios';
import { useScrollContext } from '../context/ScrollContext';
import { useAuth } from '../store/useAuth';

export default function ProfileScreen() {
  const navigation = useNavigation();
  const { handleScroll } = useScrollContext();
  const { user, logout, updateUser } = useAuth();
  const [profile, setProfile] = useState(user);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  const fetchProfile = useCallback(async () => {
    setLoading(true);
    setError('');
    try {
      const { data } = await api.get('/usuarios/me');
      setProfile(data);
      await updateUser(data);
    } catch (requestError) {
      setError(requestError.userMessage || 'No se pudo actualizar tu perfil.');
    } finally {
      setLoading(false);
    }
  }, [updateUser]);

  useFocusEffect(useCallback(() => {
    fetchProfile();
  }, [fetchProfile]));

  const confirmLogout = () => {
    Alert.alert('Cerrar sesión', '¿Deseas cerrar tu sesión?', [
      { text: 'Cancelar', style: 'cancel' },
      { text: 'Salir', style: 'destructive', onPress: logout },
    ]);
  };

  const imageSource = profile?.foto_perfil && !['perfil_default.png', 'default.png'].includes(profile.foto_perfil)
    ? { uri: profile.foto_perfil.startsWith('http') ? profile.foto_perfil : `https://www.zerowaste-qro.com/static/img/perfiles/${profile.foto_perfil}` }
    : require('../assets/images/logo.png');

  return (
    <SafeAreaView className="flex-1 bg-[#FAFAFA]" edges={['top']}>
      <StatusBar style="dark" />
      <ScrollView onScroll={handleScroll} scrollEventThrottle={16} contentContainerStyle={{ paddingBottom: 130 }}>
        <View className="px-6 pt-5 pb-6">
          <Text className="text-3xl font-black text-gray-900">Mi perfil</Text>
          <Text className="text-gray-500 mt-1">Información sincronizada con tu cuenta.</Text>
        </View>

        {error ? (
          <View className="mx-6 mb-4 rounded-2xl border border-red-200 bg-red-50 p-4">
            <Text className="text-red-700 text-center font-bold">{error}</Text>
            <TouchableOpacity onPress={fetchProfile} className="mt-3 self-center"><Text className="text-red-700 font-black">Reintentar</Text></TouchableOpacity>
          </View>
        ) : null}
        {loading ? <ActivityIndicator color="#047857" /> : null}

        <View className="mx-6 rounded-[28px] bg-white border border-gray-100 p-6">
          <View className="flex-row items-center">
            <Image source={imageSource} className="w-20 h-20 rounded-full bg-emerald-50" resizeMode="cover" />
            <View className="ml-4 flex-1">
              <Text className="text-2xl font-black text-gray-900">{profile?.nombre || 'Usuario'}</Text>
              <Text className="text-gray-500 mt-1">{profile?.email || ''}</Text>
              <View className="mt-3 self-start flex-row items-center rounded-full bg-emerald-700 px-3 py-1.5">
                <Shield color="white" size={12} />
                <Text className="ml-1.5 text-white text-[11px] font-black uppercase">{profile?.rol || (profile?.is_admin ? 'administrador' : 'usuario')}</Text>
              </View>
            </View>
          </View>
          <View className="mt-5 border-t border-gray-100 pt-4">
            <Text className="text-gray-500 text-xs font-bold uppercase">Fecha de registro</Text>
            <Text className="text-gray-900 font-bold mt-1">{profile?.created_at ? new Date(profile.created_at).toLocaleDateString('es-MX') : 'No disponible'}</Text>
          </View>
        </View>

        <View className="mx-6 mt-6 rounded-[24px] bg-white border border-gray-100 overflow-hidden">
          <TouchableOpacity onPress={() => navigation.navigate('EditProfile')} className="flex-row items-center justify-between p-5">
            <View className="flex-row items-center"><View className="w-10 h-10 rounded-full bg-emerald-50 items-center justify-center"><UserRoundPen color="#059669" size={20} /></View><Text className="ml-4 text-gray-800 font-bold text-base">Editar perfil</Text></View><ChevronRight color="#9CA3AF" size={20} />
          </TouchableOpacity>
          <TouchableOpacity onPress={() => navigation.navigate('MisRecolecciones')} className="flex-row items-center justify-between p-5">
            <View className="flex-row items-center">
              <View className="w-10 h-10 rounded-full bg-emerald-50 items-center justify-center"><MapPin color="#059669" size={20} /></View>
              <Text className="ml-4 text-gray-800 font-bold text-base">Mis recolecciones</Text>
            </View>
            <ChevronRight color="#9CA3AF" size={20} />
          </TouchableOpacity>
          <TouchableOpacity onPress={() => navigation.navigate('ImpactStats')} className="flex-row items-center justify-between border-t border-gray-100 p-5">
            <View className="flex-row items-center"><View className="w-10 h-10 rounded-full bg-emerald-50 items-center justify-center"><Award color="#059669" size={20} /></View><Text className="ml-4 text-gray-800 font-bold text-base">Ranking e impacto</Text></View><ChevronRight color="#9CA3AF" size={20} />
          </TouchableOpacity>
          <TouchableOpacity onPress={() => navigation.navigate('RewardsStore')} className="flex-row items-center justify-between border-t border-gray-100 p-5">
            <View className="flex-row items-center"><View className="w-10 h-10 rounded-full bg-emerald-50 items-center justify-center"><Gift color="#059669" size={20} /></View><Text className="ml-4 text-gray-800 font-bold text-base">Tienda de recompensas</Text></View><ChevronRight color="#9CA3AF" size={20} />
          </TouchableOpacity>
          <TouchableOpacity onPress={() => navigation.navigate('MyRedemptions')} className="flex-row items-center justify-between border-t border-gray-100 p-5">
            <View className="flex-row items-center"><View className="w-10 h-10 rounded-full bg-emerald-50 items-center justify-center"><History color="#059669" size={20} /></View><Text className="ml-4 text-gray-800 font-bold text-base">Mis canjes</Text></View><ChevronRight color="#9CA3AF" size={20} />
          </TouchableOpacity>
          <TouchableOpacity onPress={() => navigation.navigate('PointsHistory')} className="flex-row items-center justify-between border-t border-gray-100 p-5">
            <View className="flex-row items-center"><View className="w-10 h-10 rounded-full bg-emerald-50 items-center justify-center"><History color="#059669" size={20} /></View><Text className="ml-4 text-gray-800 font-bold text-base">Historial de puntos</Text></View><ChevronRight color="#9CA3AF" size={20} />
          </TouchableOpacity>
          <TouchableOpacity onPress={() => navigation.navigate('Settings')} className="flex-row items-center justify-between border-t border-gray-100 p-5">
            <View className="flex-row items-center"><View className="w-10 h-10 rounded-full bg-emerald-50 items-center justify-center"><Settings color="#059669" size={20} /></View><Text className="ml-4 text-gray-800 font-bold text-base">Configuración</Text></View><ChevronRight color="#9CA3AF" size={20} />
          </TouchableOpacity>
        </View>

        <View className="px-6 mt-6">
          <Text className="text-gray-500 text-sm leading-5">Las estadísticas de impacto y el saldo de puntos aparecerán cuando exista un historial verificable en FastAPI. No se muestran valores simulados.</Text>
          <TouchableOpacity onPress={confirmLogout} className="mt-6 items-center rounded-2xl border border-red-100 bg-red-50 py-4">
            <View className="flex-row items-center"><LogOut color="#EF4444" size={20} /><Text className="ml-2 text-red-600 font-black">Cerrar sesión</Text></View>
          </TouchableOpacity>
        </View>
      </ScrollView>
    </SafeAreaView>
  );
}
