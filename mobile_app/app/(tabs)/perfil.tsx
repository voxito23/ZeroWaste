import React from 'react';
import { View, Text, ScrollView, SafeAreaView, TouchableOpacity, Image, Switch } from 'react-native';
import { MaterialIcons, FontAwesome5 } from '@expo/vector-icons';
import { useRouter } from 'expo-router';
import { useAuth } from '../context/AuthContext';
import { useColorScheme } from 'nativewind';

const activities = [
  { id: 1, tipo: 'Comentario', descripcion: 'Respondió en "¿Cómo separar correctamente el cartón?"', fecha: '25 May, 2026' },
  { id: 2, tipo: 'Punto Agregado', descripcion: 'Añadió Centro de Acopio Bicentenario', fecha: '22 May, 2026' },
];

export default function Perfil() {
  const router = useRouter();
  const { user, logout } = useAuth();
  const { colorScheme, toggleColorScheme } = useColorScheme();

  const handleLogout = async () => {
    await logout();
    router.replace('/(auth)/login');
  };

  return (
    <SafeAreaView className="flex-1 bg-white dark:bg-[#062C25]">
      <ScrollView className="flex-1" showsVerticalScrollIndicator={false}>
        
        {/* Header Profile Section */}
        <View className="px-6 pt-10 pb-6 items-center bg-[#F0FDF4] dark:bg-[#022C22] rounded-b-[40px] shadow-sm mb-6 border-b border-emerald-100 dark:border-emerald-900">
          <View className="relative mb-4">
            <Image 
              source={user?.foto_perfil ? { uri: user.foto_perfil } : require('../../assets/images/default_avatar.png')} 
              className="w-32 h-32 rounded-full border-4 border-white dark:border-[#022C22] shadow-lg bg-gray-200" 
            />
            <TouchableOpacity className="absolute bottom-0 right-0 bg-[#10B981] p-3 rounded-full border-4 border-white dark:border-[#022C22] shadow-sm">
              <MaterialIcons name="photo-camera" size={20} color="white" />
            </TouchableOpacity>
          </View>
          
          <Text className="text-3xl font-extrabold text-[#064E3B] dark:text-white text-center mb-1">{user?.nombre || 'Usuario'}</Text>
          
          <View className="flex-row items-center mb-4">
            <MaterialIcons name="eco" size={16} color="#10B981" />
            <Text className="text-gray-500 dark:text-emerald-100/80 font-bold ml-1">Entusiasta Zero Waste</Text>
          </View>

          <Text className="text-center text-gray-600 dark:text-gray-300 font-medium px-4 mb-6 leading-relaxed">
            Comprometido con el cuidado del medio ambiente y la sostenibilidad.
          </Text>

          <TouchableOpacity className="flex-row items-center bg-[#10B981] px-6 py-3 rounded-full shadow-md">
            <MaterialIcons name="edit" size={18} color="white" />
            <Text className="text-white font-bold ml-2">Editar Perfil</Text>
          </TouchableOpacity>
        </View>

        {/* Información Personal */}
        <View className="px-6 mb-6">
          <View className="bg-white dark:bg-[#022C22] rounded-3xl p-6 shadow-sm border border-gray-100 dark:border-emerald-900">
            <View className="flex-row items-center mb-6">
              <View className="p-2 bg-emerald-50 dark:bg-emerald-900/30 rounded-lg mr-3">
                <MaterialIcons name="person" size={24} color="#064E3B" />
              </View>
              <Text className="text-xl font-bold text-[#064E3B] dark:text-white">Información Personal</Text>
            </View>

            <View className="mb-5 border-b border-gray-100 dark:border-emerald-800 pb-3">
              <Text className="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Correo Electrónico</Text>
              <View className="flex-row items-center">
                <MaterialIcons name="mail" size={20} color="#9CA3AF" />
                <Text className="text-[#064E3B] dark:text-gray-200 font-bold ml-3">{user?.email || 'N/A'}</Text>
              </View>
            </View>

            <View className="mb-5 border-b border-gray-100 dark:border-emerald-800 pb-3">
              <Text className="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Ciudad</Text>
              <View className="flex-row items-center">
                <MaterialIcons name="location-on" size={20} color="#9CA3AF" />
                <Text className="text-[#064E3B] dark:text-gray-200 font-bold ml-3">Querétaro, Qro</Text>
              </View>
            </View>

            <View>
              <Text className="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Intereses</Text>
              <View className="flex-row flex-wrap gap-2">
                {['Reciclaje', 'Composta', 'Energía Solar'].map((interes, idx) => (
                  <View key={idx} className="bg-emerald-50 dark:bg-emerald-900/40 border border-emerald-100 dark:border-emerald-800 px-3 py-1.5 rounded-xl flex-row items-center">
                    <Text className="text-[#064E3B] dark:text-emerald-100 text-sm font-bold">{interes}</Text>
                  </View>
                ))}
              </View>
            </View>
          </View>
        </View>

        {/* Mi Actividad */}
        <View className="px-6 mb-10">
          <View className="bg-white dark:bg-[#022C22] rounded-3xl p-6 shadow-sm border border-gray-100 dark:border-emerald-900">
            <View className="flex-row items-center justify-between mb-6">
              <View className="flex-row items-center">
                <View className="p-2 bg-orange-50 dark:bg-orange-900/30 rounded-lg mr-3">
                  <MaterialIcons name="history" size={24} color="#EA580C" />
                </View>
                <Text className="text-xl font-bold text-[#064E3B] dark:text-white">Mi Actividad</Text>
              </View>
              <TouchableOpacity>
                <Text className="text-[#10B981] font-bold text-sm">Ver todo</Text>
              </TouchableOpacity>
            </View>

            {activities.length > 0 ? (
              <View className="pl-4 border-l-2 border-emerald-100 dark:border-emerald-800 ml-3">
                {activities.map((act, index) => (
                  <View key={act.id} className="relative mb-6 pl-6">
                    <View className="absolute -left-[27px] top-1 w-4 h-4 rounded-full bg-white dark:bg-[#022C22] border-4 border-[#10B981]" />
                    <View className="bg-[#F0FDF4] dark:bg-[#062C25] p-4 rounded-2xl border border-emerald-50 dark:border-emerald-900/50">
                      <Text className="text-xs text-gray-500 font-bold mb-1">{act.fecha}</Text>
                      <Text className="font-bold text-[#064E3B] dark:text-white mb-1">{act.tipo}</Text>
                      <Text className="text-sm text-gray-500 dark:text-gray-400">{act.descripcion}</Text>
                    </View>
                  </View>
                ))}
              </View>
            ) : (
              <Text className="text-gray-500 text-center py-4">No hay actividad reciente.</Text>
            )}
          </View>
        </View>

        {/* Configuración PRO */}
        <View className="px-6 mb-10">
          <View className="bg-white dark:bg-[#022C22] rounded-3xl p-6 shadow-sm border border-gray-100 dark:border-emerald-900">
            <View className="flex-row items-center mb-6">
              <View className="p-2 bg-blue-50 dark:bg-blue-900/30 rounded-lg mr-3">
                <MaterialIcons name="settings" size={24} color="#3B82F6" />
              </View>
              <Text className="text-xl font-bold text-[#064E3B] dark:text-white">Configuración</Text>
            </View>

            {/* Tema Oscuro Toggle */}
            <View className="flex-row items-center justify-between border-b border-gray-100 dark:border-emerald-800 pb-4 mb-4">
              <View className="flex-row items-center">
                <View className={`p-2 rounded-full mr-3 ${colorScheme === 'dark' ? 'bg-indigo-900' : 'bg-amber-100'}`}>
                  <MaterialIcons name={colorScheme === 'dark' ? 'dark-mode' : 'light-mode'} size={20} color={colorScheme === 'dark' ? '#818CF8' : '#D97706'} />
                </View>
                <View>
                  <Text className="text-base font-bold text-[#064E3B] dark:text-white">Modo Oscuro</Text>
                  <Text className="text-xs text-gray-500 dark:text-gray-400">Cambia la apariencia de la app</Text>
                </View>
              </View>
              <Switch
                trackColor={{ false: '#D1D5DB', true: '#10B981' }}
                thumbColor={colorScheme === 'dark' ? '#ffffff' : '#ffffff'}
                onValueChange={toggleColorScheme}
                value={colorScheme === 'dark'}
              />
            </View>

            {/* Cerrar Sesión */}
            <TouchableOpacity onPress={handleLogout} className="flex-row items-center pt-2">
              <View className="p-2 bg-red-50 dark:bg-red-900/30 rounded-full mr-3">
                <MaterialIcons name="logout" size={20} color="#EF4444" />
              </View>
              <Text className="text-base font-bold text-red-500">Cerrar Sesión</Text>
            </TouchableOpacity>
          </View>
        </View>

      </ScrollView>
    </SafeAreaView>
  );
}
