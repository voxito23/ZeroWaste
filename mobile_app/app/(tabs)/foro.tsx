import React, { useState, useEffect } from 'react';
import { View, Text, TextInput, TouchableOpacity, ScrollView, SafeAreaView, Image, ActivityIndicator, Alert } from 'react-native';
import { MaterialIcons } from '@expo/vector-icons';
import { useRouter } from 'expo-router';
import { useAuth } from '../context/AuthContext';
import { API_URL } from '../../config';

export default function Foro() {
  const router = useRouter();
  const { token } = useAuth();
  const [filter, setFilter] = useState('todos');
  const [posts, setPosts] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    fetchForos();
  }, []);

  const fetchForos = async () => {
    try {
      const response = await fetch(`${API_URL}/foro`, {
        headers: {
          'Authorization': `Bearer ${token}`
        }
      });
      const data = await response.json();
      if (data.success) {
        setPosts(data.data);
      }
    } catch (error) {
      console.error(error);
      Alert.alert('Error', 'No se pudieron cargar los foros');
    } finally {
      setLoading(false);
    }
  };

  return (
    <SafeAreaView className="flex-1 bg-white dark:bg-[#062C25]">
      <ScrollView className="flex-1" showsVerticalScrollIndicator={false}>
        
        {/* Header / Hero */}
        <View className="bg-[#022C22] px-6 py-10 relative overflow-hidden">
          <View className="absolute inset-0 bg-[#022C22]/80" />
          <View className="relative z-10">
            <View className="self-start px-4 py-1.5 rounded-full border border-white/20 bg-white/10 mb-4 flex-row items-center">
              <View className="w-2 h-2 rounded-full bg-[#10B981] mr-2 shadow-[0_0_8px_#10B981]" />
              <Text className="text-white text-xs font-bold tracking-wider uppercase">Comunidad Activa</Text>
            </View>
            <Text className="text-4xl font-extrabold text-white mb-4">
              Foro de la <Text className="text-[#10B981]">Comunidad</Text>
            </Text>
            <Text className="text-emerald-50 text-base mb-6 leading-relaxed">
              Comparte eco-tips, resuelve tus dudas sobre reciclaje y conecta con miles de voluntarios.
            </Text>

            <View className="bg-white rounded-full flex-row items-center px-4 py-1 mb-4 shadow-lg">
              <MaterialIcons name="search" size={24} color="#059669" />
              <TextInput 
                className="flex-1 h-12 ml-2 text-base text-[#064E3B] font-semibold"
                placeholder="Buscar temas..."
                placeholderTextColor="#059669"
              />
            </View>

            <TouchableOpacity className="flex-row items-center">
              <MaterialIcons name="add-circle" size={28} color="#10B981" />
              <Text className="text-[#10B981] font-black text-lg ml-2 border-b-2 border-[#10B981]">Nuevo Post</Text>
            </TouchableOpacity>
          </View>
        </View>

        {/* Content Section */}
        <View className="p-6 bg-white dark:bg-[#062C25]">
          <View className="mb-6">
            <Text className="text-3xl font-extrabold text-[#064E3B] dark:text-white mb-2">Discusiones</Text>
            <Text className="text-gray-500 dark:text-gray-400 text-sm font-medium mb-4">Únete a las conversaciones más populares</Text>
            
            {/* Filter Pills */}
            <ScrollView horizontal showsHorizontalScrollIndicator={false} className="flex-row mb-4">
              {['todos', 'populares', 'recientes'].map(f => (
                <TouchableOpacity 
                  key={f}
                  onPress={() => setFilter(f)}
                  className={`px-5 py-2 rounded-full mr-3 border ${filter === f ? 'bg-[#064E3B] border-[#064E3B]' : 'bg-emerald-50 dark:bg-emerald-900/40 border-emerald-200 dark:border-emerald-700'}`}
                >
                  <Text className={`font-bold capitalize ${filter === f ? 'text-white' : 'text-emerald-800 dark:text-emerald-200'}`}>
                    {f}
                  </Text>
                </TouchableOpacity>
              ))}
            </ScrollView>
          </View>

          {/* Posts List */}
          {loading ? (
            <ActivityIndicator size="large" color="#00E096" className="mt-8" />
          ) : posts.length === 0 ? (
            <Text className="text-center text-gray-500 mt-8">No hay foros disponibles.</Text>
          ) : (
            posts.map(post => (
              <TouchableOpacity 
                key={post.id}
                onPress={() => router.push(`/(tabs)/foro/${post.id}` as any)}
                className="bg-white dark:bg-[#022C22] rounded-3xl p-5 mb-5 shadow-sm border border-gray-100 dark:border-emerald-900"
              >
                <View className="flex-row justify-between items-center mb-3">
                  <View className="bg-amber-50 dark:bg-amber-900/20 px-3 py-1 rounded-full border border-amber-400">
                    <Text className="text-amber-700 dark:text-amber-300 text-xs font-bold uppercase">{post.categoria}</Text>
                  </View>
                  <View className="flex-row items-center">
                    <MaterialIcons name="schedule" size={14} color="#9CA3AF" />
                    <Text className="text-gray-400 text-xs font-medium ml-1">{post.fecha}</Text>
                  </View>
                </View>

                <Text className="text-xl font-bold text-[#064E3B] dark:text-white mb-2">{post.titulo}</Text>
                <Text className="text-gray-600 dark:text-gray-300 text-sm mb-4 leading-relaxed line-clamp-2">{post.contenido}</Text>

                <View className="flex-row items-center justify-between border-t border-gray-100 dark:border-emerald-800/50 pt-4">
                  <View className="flex-row items-center">
                    <Image 
                      source={post.autor.foto_perfil ? { uri: post.autor.foto_perfil } : require('../../assets/images/default_avatar.png')} 
                      className="w-8 h-8 rounded-full bg-gray-200 mr-2" 
                    />
                    <Text className="text-sm font-bold text-[#064E3B] dark:text-emerald-100">{post.autor.nombre}</Text>
                  </View>
                  <View className="flex-row items-center">
                    <View className="flex-row items-center mr-3">
                      <MaterialIcons name="chat-bubble-outline" size={16} color="#9CA3AF" />
                      <Text className="text-gray-500 text-xs font-bold ml-1">{post.respuestas_count}</Text>
                    </View>
                    <View className="flex-row items-center">
                      <MaterialIcons name="favorite-border" size={16} color="#9CA3AF" />
                      <Text className="text-gray-500 text-xs font-bold ml-1">{post.likes}</Text>
                    </View>
                  </View>
                </View>
              </TouchableOpacity>
            ))
          )}
          
          <TouchableOpacity onPress={fetchForos} className="border-2 border-[#10B981] rounded-full py-3 items-center mt-2 mb-6">
            <Text className="text-[#10B981] font-bold text-base">Actualizar discusiones</Text>
          </TouchableOpacity>
        </View>

      </ScrollView>
    </SafeAreaView>
  );
}
