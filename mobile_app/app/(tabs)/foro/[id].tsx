import React, { useEffect, useState } from 'react';
import { View, Text, ScrollView, SafeAreaView, TouchableOpacity, ActivityIndicator, Image } from 'react-native';
import { useLocalSearchParams, useRouter } from 'expo-router';
import { MaterialIcons } from '@expo/vector-icons';
import { useAuth } from '../../context/AuthContext';
import { API_URL } from '../../../config';

export default function ForoDetail() {
  const { id } = useLocalSearchParams();
  const router = useRouter();
  const { token } = useAuth();
  
  const [post, setPost] = useState<any>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const fetchDetail = async () => {
      try {
        const res = await fetch(`${API_URL}/foro/${id}`, {
          headers: {
            'Authorization': `Bearer ${token}`
          }
        });
        const data = await res.json();
        if (data.success) {
          setPost(data.data);
        }
      } catch (error) {
        console.error(error);
      } finally {
        setLoading(false);
      }
    };
    if (id) fetchDetail();
  }, [id]);

  return (
    <SafeAreaView className="flex-1 bg-white dark:bg-[#062C25]">
      {/* Header */}
      <View className="bg-[#022C22] px-4 py-4 flex-row items-center">
        <TouchableOpacity onPress={() => router.back()} className="mr-3">
          <MaterialIcons name="arrow-back" size={24} color="white" />
        </TouchableOpacity>
        <Text className="text-xl font-bold text-white flex-1" numberOfLines={1}>
          {post ? post.titulo : 'Cargando...'}
        </Text>
      </View>

      {loading ? (
        <View className="flex-1 justify-center items-center">
          <ActivityIndicator size="large" color="#00E096" />
        </View>
      ) : !post ? (
        <View className="flex-1 justify-center items-center p-6">
          <Text className="text-gray-500 dark:text-gray-400 text-lg">Post no encontrado.</Text>
        </View>
      ) : (
        <ScrollView className="flex-1 p-6" showsVerticalScrollIndicator={false}>
          {/* Post Principal */}
          <View className="bg-white dark:bg-[#022C22] rounded-3xl p-5 shadow-sm border border-gray-100 dark:border-emerald-900 mb-6">
            <View className="flex-row justify-between items-center mb-4">
              <View className="bg-amber-50 dark:bg-amber-900/20 px-3 py-1 rounded-full border border-amber-400">
                <Text className="text-amber-700 dark:text-amber-300 text-xs font-bold uppercase">{post.categoria}</Text>
              </View>
              <Text className="text-gray-400 text-xs font-medium">{post.fecha}</Text>
            </View>
            
            <Text className="text-2xl font-bold text-[#064E3B] dark:text-white mb-3">{post.titulo}</Text>
            <Text className="text-gray-700 dark:text-gray-200 text-base leading-relaxed mb-6">
              {post.contenido}
            </Text>

            <View className="flex-row items-center justify-between border-t border-gray-100 dark:border-emerald-800/50 pt-4">
              <View className="flex-row items-center">
                <Image 
                  source={post.autor.foto_perfil ? { uri: post.autor.foto_perfil } : require('../../../assets/images/default_avatar.png')} 
                  className="w-10 h-10 rounded-full bg-gray-200 mr-3" 
                />
                <Text className="text-sm font-bold text-[#064E3B] dark:text-emerald-100">{post.autor.nombre}</Text>
              </View>
              <View className="flex-row items-center">
                <MaterialIcons name="favorite" size={20} color="#10B981" />
                <Text className="text-[#10B981] font-bold ml-1">{post.likes}</Text>
              </View>
            </View>
          </View>

          {/* Respuestas */}
          <Text className="text-xl font-bold text-[#064E3B] dark:text-emerald-100 mb-4">
            Respuestas ({post.respuestas.length})
          </Text>

          {post.respuestas.length === 0 ? (
            <Text className="text-gray-500 dark:text-gray-400 text-center mb-8">No hay respuestas aún. ¡Sé el primero!</Text>
          ) : (
            post.respuestas.map((r: any) => (
              <View key={r.id} className="bg-gray-50 dark:bg-[#064E3B]/20 p-4 rounded-2xl mb-3 border border-gray-100 dark:border-emerald-900/50">
                <View className="flex-row justify-between items-center mb-2">
                  <View className="flex-row items-center">
                    <Image 
                      source={r.autor.foto_perfil ? { uri: r.autor.foto_perfil } : require('../../../assets/images/default_avatar.png')} 
                      className="w-8 h-8 rounded-full bg-gray-200 mr-2" 
                    />
                    <Text className="text-sm font-bold text-[#064E3B] dark:text-emerald-100">{r.autor.nombre}</Text>
                  </View>
                  <Text className="text-gray-400 text-xs">{r.fecha}</Text>
                </View>
                <Text className="text-gray-600 dark:text-gray-300 text-sm leading-relaxed">{r.contenido}</Text>
              </View>
            ))
          )}
          
          <View className="h-10" />
        </ScrollView>
      )}
    </SafeAreaView>
  );
}
