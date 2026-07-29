import React, { useState, useEffect } from 'react';
import { View, Text, ScrollView, Alert, Image, TouchableOpacity, ActivityIndicator } from 'react-native';
import CustomButton from '../components/ui/CustomButton';
import { useAuth } from '../store/useAuth';
import { SafeAreaView } from 'react-native-safe-area-context';
import { useScrollContext } from '../context/ScrollContext';
import { Settings, HelpCircle, MapPin, Edit3, ChevronRight, Leaf, Shield, LogOut } from 'lucide-react-native';
import { LinearGradient } from 'expo-linear-gradient';
import { StatusBar } from 'expo-status-bar';

export default function ProfileScreen() {
  const { user, logout } = useAuth();
  const { handleScroll } = useScrollContext();
  const [stats, setStats] = useState({ kilos: 15, puntos: 12, eventos: 5 });
  const [loading, setLoading] = useState(false);

  // En un caso real, aquí haríamos un fetch a la API para obtener las stats reales del usuario
  // useEffect(() => {
  //   fetchStats();
  // }, []);

  const handleLogout = () => {
    Alert.alert(
      "Cerrar Sesión",
      "¿Estás seguro que deseas cerrar sesión?",
      [
        { text: "Cancelar", style: "cancel" },
        { 
          text: "Sí, salir", 
          style: "destructive",
          onPress: async () => {
            await logout();
          }
        }
      ]
    );
  };

  const getProfileImage = () => {
    if (user?.foto_perfil && user.foto_perfil !== 'perfil_default.png') {
      return { uri: user.foto_perfil.startsWith('http') ? user.foto_perfil : `https://zerowaste-qro.com/static/img/perfiles/${user.foto_perfil}` };
    }
    // Fallback avatar based on name
    const name = user?.nombre || 'Usuario';
    return { uri: `https://api.dicebear.com/7.x/identicon/png?seed=${encodeURIComponent(name)}&backgroundColor=064E3B` };
  };

  return (
    <SafeAreaView className="flex-1 bg-[#FAFAFA]" edges={['top']}>
      <StatusBar style="dark" />
      {/* Header Premium */}
      <View className="px-6 pt-4 pb-2 flex-row justify-between items-center">
        <Text className="text-3xl font-black text-gray-900 tracking-tight">Mi Perfil</Text>
        <TouchableOpacity className="w-10 h-10 rounded-full bg-white items-center justify-center border border-gray-100 shadow-sm">
          <Settings color="#374151" size={20} />
        </TouchableOpacity>
      </View>
      
      <ScrollView 
        showsVerticalScrollIndicator={false} 
        contentContainerStyle={{ paddingBottom: 140 }}
        onScroll={handleScroll}
        scrollEventThrottle={16}
      >
        
        {/* User Card */}
        <View className="px-6 mt-6 mb-8">
          <View className="bg-white rounded-[32px] p-6 shadow-xl shadow-emerald-900/5 border border-emerald-50 relative overflow-hidden">
            {/* Background decoration */}
            <View className="absolute top-0 right-0 w-32 h-32 bg-emerald-50 rounded-bl-full opacity-50" />
            
            <View className="flex-row items-center gap-5">
              <View className="relative">
                <View className="w-24 h-24 rounded-full border-4 border-emerald-50 shadow-sm overflow-hidden bg-white">
                  <Image source={getProfileImage()} className="w-full h-full" resizeMode="cover" />
                </View>
                <TouchableOpacity className="absolute bottom-0 right-0 bg-primary p-2 rounded-full border-2 border-white shadow-sm">
                  <Edit3 color="white" size={14} />
                </TouchableOpacity>
              </View>
              
              <View className="flex-1">
                <Text className="text-2xl font-black text-gray-900 leading-tight">{user?.nombre || 'Eco Usuario'}</Text>
                <Text className="text-gray-500 text-sm mt-0.5 mb-2">{user?.email || 'eco@zerowaste.com'}</Text>
                
                <View className="self-start flex-row items-center gap-1.5 px-3 py-1.5 bg-emerald-500 rounded-full">
                  <Shield color="white" size={12} fill="white" />
                  <Text className="text-white font-black text-[11px] tracking-wide uppercase">Nivel 4: Héroe Verde</Text>
                </View>
              </View>
            </View>
          </View>
        </View>

        {/* Estadísticas con Gradiente */}
        <View className="px-6 mb-8">
          <Text className="text-lg font-black text-gray-900 mb-4 px-2">Mi Impacto Global</Text>
          <LinearGradient
            colors={['#064E3B', '#047857']}
            start={{ x: 0, y: 0 }}
            end={{ x: 1, y: 1 }}
            className="rounded-[28px] p-1 shadow-lg shadow-emerald-900/20"
          >
            <View className="bg-[#064E3B] rounded-[24px] flex-row p-6 items-center justify-between overflow-hidden relative">
              {/* Shine effect */}
              <View className="absolute -top-10 -right-10 w-32 h-32 bg-white/10 rounded-full" />
              
              <View className="items-center flex-1">
                <Text className="text-4xl font-black text-emerald-300 tracking-tighter">{stats.kilos}</Text>
                <Text className="text-emerald-100/70 text-[11px] font-bold uppercase tracking-wider text-center mt-1">Kilos{'\n'}Reciclados</Text>
              </View>
              <View className="w-px h-12 bg-emerald-700/50" />
              <View className="items-center flex-1">
                <Text className="text-4xl font-black text-emerald-300 tracking-tighter">{stats.puntos}</Text>
                <Text className="text-emerald-100/70 text-[11px] font-bold uppercase tracking-wider text-center mt-1">Puntos{'\n'}Visitados</Text>
              </View>
              <View className="w-px h-12 bg-emerald-700/50" />
              <View className="items-center flex-1">
                <Text className="text-4xl font-black text-emerald-300 tracking-tighter">{stats.eventos}</Text>
                <Text className="text-emerald-100/70 text-[11px] font-bold uppercase tracking-wider text-center mt-1">Eventos{'\n'}Asistidos</Text>
              </View>
            </View>
          </LinearGradient>
        </View>

        {/* Opciones */}
        <View className="px-6 mb-8">
          <Text className="text-lg font-black text-gray-900 mb-4 px-2">Opciones</Text>
          <View className="bg-white rounded-[28px] overflow-hidden shadow-xl shadow-black/5 border border-gray-100">
            
            <TouchableOpacity className="flex-row items-center justify-between p-5 border-b border-gray-50 active:bg-gray-50">
              <View className="flex-row items-center gap-4">
                <View className="w-10 h-10 rounded-full bg-emerald-50 items-center justify-center">
                  <Edit3 color="#059669" size={20} />
                </View>
                <Text className="text-gray-700 font-bold text-base">Editar Perfil</Text>
              </View>
              <ChevronRight color="#D1D5DB" size={20} />
            </TouchableOpacity>

            <TouchableOpacity className="flex-row items-center justify-between p-5 border-b border-gray-50 active:bg-gray-50">
              <View className="flex-row items-center gap-4">
                <View className="w-10 h-10 rounded-full bg-emerald-50 items-center justify-center">
                  <MapPin color="#059669" size={20} />
                </View>
                <Text className="text-gray-700 font-bold text-base">Mis Puntos de Acopio</Text>
              </View>
              <ChevronRight color="#D1D5DB" size={20} />
            </TouchableOpacity>

            <TouchableOpacity className="flex-row items-center justify-between p-5 active:bg-gray-50">
              <View className="flex-row items-center gap-4">
                <View className="w-10 h-10 rounded-full bg-emerald-50 items-center justify-center">
                  <HelpCircle color="#059669" size={20} />
                </View>
                <Text className="text-gray-700 font-bold text-base">Centro de Ayuda</Text>
              </View>
              <ChevronRight color="#D1D5DB" size={20} />
            </TouchableOpacity>

          </View>
        </View>

        {/* Logout */}
        <View className="px-6 pb-8">
          <TouchableOpacity 
            onPress={handleLogout}
            className="flex-row items-center justify-center gap-2 py-4 bg-red-50 rounded-2xl border border-red-100 active:bg-red-100"
          >
            <LogOut color="#EF4444" size={20} />
            <Text className="text-red-600 font-black text-base">Cerrar Sesión</Text>
          </TouchableOpacity>
        </View>
        
      </ScrollView>
    </SafeAreaView>
  );
}