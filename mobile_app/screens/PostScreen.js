import React, { useState, useEffect } from 'react';
import { View, Text, ScrollView, TouchableOpacity, Image, TextInput, ActivityIndicator, KeyboardAvoidingView, Platform } from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import { 
  ArrowLeft, 
  MessageCircle, 
  Share2, 
  Heart, 
  MoreHorizontal,
  Recycle,
  Leaf,
  Archive,
  Calendar,
  HelpCircle,
  Folder,
  Send
} from 'lucide-react-native';
import { api } from '../api/axios';
import { useNavigation, useRoute } from '@react-navigation/native';
import { StatusBar } from 'expo-status-bar';
import { SafeAreaView } from 'react-native-safe-area-context';




export default function PostScreen() {
  const navigation = useNavigation();
  const route = useRoute();
  const postId = route.params?.id;

  const [post, setPost] = useState(null);
  const [respuestas, setRespuestas] = useState([]);
  const [isLoading, setIsLoading] = useState(true);
  const [replyText, setReplyText] = useState('');
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [error, setError] = useState('');

  useEffect(() => {
    if (postId) fetchPostDetail();
  }, [postId]);

  const fetchPostDetail = async () => {
    setError('');
    try {
      const response = await api.get(`/foro/posts/${postId}`);
      if (response.data) {
        setPost(response.data);
      }

      const respResponse = await api.get(`/foro/posts/${postId}/respuestas`);
      if (respResponse.data) {
        setRespuestas(respResponse.data);
      }
    } catch (e) {
      setError(e.userMessage || 'No se pudo cargar la publicación.');
    } finally {
      setIsLoading(false);
    }
  };

  const submitReply = async () => {
    if (replyText.trim().length <= 10) {
      setError('La respuesta debe tener más de 10 caracteres.');
      return;
    }
    setIsSubmitting(true);
    try {
      await api.post(`/foro/posts/${postId}/respuestas`, {
        contenido: replyText
      });
      
      setReplyText('');
      fetchPostDetail(); // Refresh the list
    } catch (e) {
      setError(e.userMessage || 'No se pudo enviar la respuesta.');
    } finally {
      setIsSubmitting(false);
    }
  };

  const getImageUrl = (path, type, name = 'Usuario') => {
    if (!path || path === 'perfil_default.png' || path === 'default.png') return type === 'post' ? 'https://images.unsplash.com/photo-1542601906990-b4d3fb778b09?auto=format&fit=crop&q=80' : `https://api.dicebear.com/7.x/identicon/png?seed=${encodeURIComponent(name)}`;
    if (path.startsWith('http')) return path;

    const baseUrl = api.defaults.baseURL || 'https://www.zerowaste-qro.com/api';
    return type === 'post' ? `${baseUrl}/foro/posts/imagenes/${path}` : `${baseUrl}/foro/perfiles/${path}`;
  };

  const stripHtml = (html) => {
    return html.replace(/<[^>]*>/g, '').replace(/&nbsp;/g, ' ').trim();
  };

  const getTimeAgo = (dateString) => {
    const diff = Math.floor((new Date().getTime() - new Date(dateString).getTime()) / 60000);
    if (diff < 60) return `Hace ${diff} min`;
    const hours = Math.floor(diff / 60);
    if (hours < 24) return `Hace ${hours}h`;
    return `Hace ${Math.floor(hours / 24)}d`;
  };

  const getCatStyle = (catName) => {
    const defaultStyle = { bg: 'transparent', text: '#4B5563', border: '#E5E7EB', icon: <Folder size={12} color="#4B5563" /> };
    if (!catName) return defaultStyle;
    const name = catName.trim();
    if (name === 'Reciclaje') return { bg: 'transparent', text: '#b45309', border: '#fcd34d', icon: <Recycle size={12} color="#b45309" /> };
    if (name === 'Compostaje') return { bg: 'transparent', text: '#047857', border: '#6ee7b7', icon: <Leaf size={12} color="#047857" /> };
    if (name === 'Reducción de residuos') return { bg: 'transparent', text: '#0e7490', border: '#67e8f9', icon: <Archive size={12} color="#0e7490" /> };
    if (name === 'Eventos') return { bg: 'transparent', text: '#6d28d9', border: '#c4b5fd', icon: <Calendar size={12} color="#6d28d9" /> };
    if (name === 'Dudas') return { bg: 'transparent', text: '#be123c', border: '#fda4af', icon: <HelpCircle size={12} color="#be123c" /> };
    return defaultStyle;
  };

  const formatDate = (dateString) => {
    const d = new Date(dateString);
    const months = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
    return `${d.getDate()} ${months[d.getMonth()]}, ${d.getHours().toString().padStart(2, '0')}:${d.getMinutes().toString().padStart(2, '0')}`;
  };

  if (isLoading) {
    return (
      <View className="flex-1 bg-[#ECFDF5] items-center justify-center">
        <ActivityIndicator size="large" color="#059669" />
      </View>
    );
  }

  if (!post) {
    return (
      <View className="flex-1 bg-[#ECFDF5] items-center justify-center">
        <Text className="text-[#064E3B] font-bold text-center px-8">{error || 'Publicación no encontrada'}</Text>
        {error ? <TouchableOpacity onPress={fetchPostDetail} className="mt-4 px-6 py-2 border border-[#059669] rounded-full"><Text className="text-[#059669] font-bold">Reintentar</Text></TouchableOpacity> : null}
        <TouchableOpacity onPress={() => navigation.goBack()} className="mt-4 px-6 py-2 bg-[#059669] rounded-full">
          <Text className="text-white font-bold">Volver</Text>
        </TouchableOpacity>
      </View>
    );
  }

  return (
    <SafeAreaView className="flex-1 bg-[#ECFDF5]" edges={['top']}>
      <StatusBar style="dark" />
      <KeyboardAvoidingView behavior={Platform.OS === 'ios' ? 'padding' : undefined} style={{ flex: 1 }}>
        {error ? <View className="mx-5 mt-3 rounded-xl border border-red-200 bg-red-50 p-3"><Text className="text-red-700 font-bold text-center">{error}</Text></View> : null}
        {/* Background Gradient */}
        <LinearGradient colors={['#D1FAE5', '#ECFDF5']} locations={[0, 1]} className="absolute inset-0" />

        {/* Header */}
        <View className="px-5 pt-4 pb-4 flex-row items-center border-b border-[#D1FAE5] bg-[#ECFDF5]/90 z-20">
        <TouchableOpacity onPress={() => navigation.goBack()} className="w-10 h-10 rounded-full bg-white items-center justify-center shadow-sm">
          <ArrowLeft color="#064E3B" size={20} />
        </TouchableOpacity>
        <Text className="text-[18px] font-black text-[#064E3B] ml-4 flex-1">Discusión</Text>
      </View>

      <ScrollView showsVerticalScrollIndicator={false} contentContainerStyle={{ paddingBottom: 100 }}>
        {/* Post Content */}
        <View className="bg-white px-5 py-6 shadow-sm border-b border-[#D1FAE5]">
          <View className="flex-row items-center justify-between mb-5">
            <View className="flex-row items-center gap-3">
              <Image 
                source={{ uri: getImageUrl(post.autor_foto, 'perfil', post.autor_nombre || 'Usuario') }} 
                className="w-12 h-12 rounded-full border border-gray-200" 
              />
              <View>
                <Text className="font-bold text-[#064E3B] text-[16px]">{post.autor_nombre}</Text>
                <Text className="text-gray-500 text-[12px] font-medium mt-0.5">Miembro • {formatDate(post.created_at)}</Text>
              </View>
            </View>
          </View>
            <View 
              className="flex-row items-center gap-1.5 px-3 py-1 rounded-full mb-4 self-start bg-[#ECFDF5]"
              style={{ borderWidth: 1, borderColor: getCatStyle(post.categoria_nombre).border }}
            >
              {getCatStyle(post.categoria_nombre).icon}
              <Text 
                className="text-[12px] font-bold uppercase tracking-wider"
                style={{ color: getCatStyle(post.categoria_nombre).text }}
              >
                {post.categoria_nombre}
              </Text>
            </View>

          <Text className="font-black text-[#022C22] text-[22px] leading-tight mb-4">{post.titulo}</Text>
          <Text className="text-[#047857] text-[16px] leading-relaxed mb-6">{stripHtml(post.contenido)}</Text>

          {post.imagen && (
            <Image source={{ uri: getImageUrl(post.imagen, 'post') }} className="w-full h-48 rounded-2xl mb-4" resizeMode="cover" />
          )}

          <View className="flex-row items-center gap-6 mt-2 pt-4 border-t border-gray-100">
            <View className="flex-row items-center gap-2">
              <Heart color="#059669" size={20} />
              <Text className="font-bold text-[#064E3B]">{post.total_likes}</Text>
            </View>
            <View className="flex-row items-center gap-2">
              <MessageCircle color="#059669" size={20} />
              <Text className="font-bold text-[#064E3B]">{post.total_respuestas} respuestas</Text>
            </View>
          </View>
        </View>

        {/* Replies Section */}
        <View className="px-5 py-6">
          <Text className="font-black text-[#064E3B] text-[16px] mb-4 uppercase tracking-widest">Comentarios</Text>
          
          {respuestas.length === 0 ? (
            <View className="items-center py-10">
              <MessageCircle color="#9CA3AF" size={40} className="mb-3 opacity-30" />
              <Text className="text-gray-400 font-bold text-center">Aún no hay respuestas</Text>
              <Text className="text-gray-400 text-xs text-center mt-1">Sé el primero en unirte a la conversación</Text>
            </View>
          ) : (
            respuestas.map((resp, i) => (
              <View key={resp.id || i} className="bg-white rounded-2xl p-4 mb-3 shadow-sm border border-[#D1FAE5]">
                <View className="flex-row items-center justify-between mb-2">
                  <View className="flex-row items-center gap-2">
                    <Image source={{ uri: getImageUrl(resp.autor_foto, 'perfil', resp.autor_nombre || 'Usuario') }} className="w-8 h-8 rounded-full" />
                    <Text className="font-bold text-[#064E3B] text-[13px]">{resp.autor_nombre}</Text>
                  </View>
                  <Text className="text-[#059669] text-[11px] font-semibold">{getTimeAgo(resp.created_at)}</Text>
                </View>
                <Text className="text-[#047857] text-[14px] leading-relaxed ml-10">{resp.contenido}</Text>
              </View>
            ))
          )}
        </View>
      </ScrollView>

      {/* Input Area */}
      <View className="px-5 py-3 bg-white border-t border-[#D1FAE5] flex-row items-end shadow-[0_-10px_20px_rgba(0,0,0,0.05)]">
        <TextInput
          value={replyText}
          onChangeText={setReplyText}
          placeholder="Escribe una respuesta..."
          placeholderTextColor="#9CA3AF"
          multiline
          className="flex-1 bg-[#F1F5F9] rounded-2xl px-4 py-3 min-h-[44px] max-h-24 text-[14px] text-[#064E3B] font-medium"
        />
        <TouchableOpacity 
          onPress={submitReply}
          disabled={!replyText.trim() || isSubmitting}
          className={`ml-3 w-11 h-11 rounded-full items-center justify-center ${replyText.trim() ? 'bg-[#059669]' : 'bg-gray-200'}`}
        >
          {isSubmitting ? (
            <ActivityIndicator size="small" color="#fff" />
          ) : (
            <Send color={replyText.trim() ? '#fff' : '#9CA3AF'} size={18} className="ml-1" />
          )}
        </TouchableOpacity>
      </View>
      </KeyboardAvoidingView>
    </SafeAreaView>
  );
}
