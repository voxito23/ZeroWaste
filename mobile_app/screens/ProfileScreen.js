import React, { useCallback, useEffect, useRef, useState } from 'react';
import { ActivityIndicator, ScrollView, Text, TouchableOpacity, View } from 'react-native';
import { useFocusEffect, useNavigation } from '@react-navigation/native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { Award, ChevronRight, Gift, History, LogOut, MapPin, Settings, Shield, UserRoundPen } from 'lucide-react-native';
import { StatusBar } from 'expo-status-bar';

import { api } from '../api/axios';
import UserAvatar from '../components/ui/UserAvatar';
import { useScrollContext } from '../context/ScrollContext';
import { useAuth } from '../store/useAuth';
import ZeroWasteDialog from '../components/ui/ZeroWasteDialog';

export default function ProfileScreen() {
  const navigation = useNavigation();
  const { handleScroll } = useScrollContext();
  const { user, logout, updateUser } = useAuth();
  const [profile, setProfile] = useState(user);
  const [loading, setLoading] = useState(!user);
  const [error, setError] = useState('');
  const [logoutVisible, setLogoutVisible] = useState(false);
  const [impact, setImpact] = useState(null);
  const hasProfileRef = useRef(Boolean(user));

  const fetchProfile = useCallback(async ({ silent = false } = {}) => {
    if (!silent) setLoading(true);
    setError('');
    try {
      const [{ data }, impactResult] = await Promise.all([api.get('/usuarios/me'), api.get('/impacto/me').catch(() => ({ data: null }))]);
      setProfile(data); setImpact(impactResult.data); hasProfileRef.current = true;
      await updateUser(data);
    } catch (requestError) {
      setError(requestError.userMessage || 'No se pudo actualizar tu perfil.');
    } finally {
      if (!silent) setLoading(false);
    }
  }, [updateUser]);

  useFocusEffect(useCallback(() => {
    fetchProfile({ silent: hasProfileRef.current });
  }, [fetchProfile]));

  useEffect(() => { if (user) setProfile(user); }, [user]);

  const avatarUrl = profile?.avatar_url ?? profile?.foto_perfil;

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
            <UserAvatar uri={avatarUrl} name={profile?.nombre} size={80} accessibilityLabel="Avatar del perfil" />
            <View className="ml-4 flex-1">
              <Text className="text-2xl font-black text-gray-900">{profile?.nombre || 'Usuario'}</Text>
              <Text className="text-gray-500 mt-1">{profile?.email || ''}</Text>
              <View className="mt-3 self-start flex-row items-center rounded-full bg-emerald-700 px-3 py-1.5">
                <Shield color="white" size={12} />
                <Text className="ml-1.5 text-white text-[11px] font-black uppercase">{profile?.rol || (profile?.is_admin ? 'administrador' : 'usuario')}</Text>
              </View>
            </View>
          </View>
          {profile?.titulo_perfil ? <Text className="mt-5 text-base font-black text-emerald-900">{profile.titulo_perfil}</Text> : null}
          {profile?.biografia ? <Text className="mt-2 text-[15px] leading-6 text-slate-600" style={{ textAlign: 'justify' }}>{profile.biografia}</Text> : null}
          {profile?.ubicacion ? <View className="mt-4 flex-row items-center"><MapPin color="#059669" size={16} /><Text className="ml-2 font-semibold text-slate-600">{profile.ubicacion}</Text></View> : null}
          {impact ? <View className="mt-5 flex-row rounded-2xl bg-emerald-50 p-4"><ProfileStat label="Impacto" value={impact.impacto_historico} /><ProfileStat label="Disponibles" value={impact.puntos_disponibles} /><ProfileStat label="Posición" value={impact.posicion ? `#${impact.posicion}` : '—'} /></View> : null}
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
          <Text className="text-gray-500 text-sm leading-5" style={{ textAlign: 'justify' }}>Tu impacto comenzará a crecer cuando completes recolecciones, participes en actividades y recibas puntos. Aquí verás tu avance real.</Text>
          <TouchableOpacity onPress={() => setLogoutVisible(true)} className="mt-6 items-center rounded-2xl border border-red-100 bg-red-50 py-4">
            <View className="flex-row items-center"><LogOut color="#EF4444" size={20} /><Text className="ml-2 text-red-600 font-black">Cerrar sesión</Text></View>
          </TouchableOpacity>
        </View>
      </ScrollView>
      <ZeroWasteDialog visible={logoutVisible} type="warning" title="Cerrar sesión" message="Tu información permanecerá segura y podrás volver a iniciar sesión cuando quieras." primaryLabel="Cerrar sesión" onPrimary={() => { setLogoutVisible(false); void logout(); }} secondaryLabel="Cancelar" onSecondary={() => setLogoutVisible(false)} />
    </SafeAreaView>
  );
}

function ProfileStat({ label, value }) { return <View className="flex-1 items-center"><Text className="text-lg font-black text-emerald-900">{typeof value === 'number' ? value.toLocaleString('es-MX') : value ?? '—'}</Text><Text className="mt-1 text-[10px] font-black uppercase text-emerald-700">{label}</Text></View>; }
