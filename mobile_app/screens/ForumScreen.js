import React, { useState, useEffect } from 'react';
import { View, Text, ScrollView, TouchableOpacity, Image, TextInput, Dimensions } from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import { BlurView } from 'expo-blur';
import {
  Search,
  Bell,
  Bookmark,
  Heart,
  MessageCircle,
  Share2,
  Clock,
  ArrowUpRight,
  Plus,
  Recycle,
  Leaf,
  Archive,
  Calendar,
  HelpCircle,
  Folder
} from 'lucide-react-native';
import { api } from '../api/axios';
import { useNavigation } from '@react-navigation/native';
import { useAuth } from '../store/useAuth';
import { supabase } from '../lib/supabase';
import { ArrowRight } from 'lucide-react-native';
import { StatusBar } from 'expo-status-bar';
import { SafeAreaView } from 'react-native-safe-area-context';
import { useScrollContext } from '../context/ScrollContext';

const { width } = Dimensions.get('window');

// ─── TYPES ─────────────────────────────────────────────────────────

// Strip HTML tags from content
const stripHtml = (html) => {
  return html.replace(/<[^>]*>/g, '').replace(/&nbsp;/g, ' ').trim();
};

export default function ForumScreen() {
  const navigation = useNavigation();
  const { handleScroll } = useScrollContext();
  const { user } = useAuth();
  const [activeTab, setActiveTab] = useState('Todo');
  const [posts, setPosts] = useState([]);
  const [isLoading, setIsLoading] = useState(true);
  const [tabs, setTabs] = useState(['Todo']);

  useEffect(() => {
    fetchPosts();
  }, []);

  const fetchPosts = async () => {
    setIsLoading(true);
    try {
      // 1. Obtener categorías desde FastAPI (o hardcoded temporalmente si no hay endpoint)
      // Como no hay endpoint directo de categorías en FastAPI sin auth, usamos api
      try {
        const catRes = await api.get('/foro/categorias');
        if (catRes.data && catRes.data.length > 0) {
          setTabs(['Todo', ...catRes.data.map((c) => c.nombre)]);
        }
      } catch (e) {
        // Fallback silently
      }

      // 2. Cargar Posts desde FastAPI (arquitectura correcta: RN -> FastAPI -> Supabase)
      const response = await api.get('/foro/posts');
      if (response.data && response.data.length > 0) {
        setPosts(response.data);
      } else {
        setPosts([]);
      }
    } catch (e) {
      console.log('Fallo la conexión a FastAPI, mostrando lista vacía.', e);
      setPosts([]);
    } finally {
      setIsLoading(false);
    }
  };

  const getTimeAgo = (dateString) => {
    const diff = Math.floor((new Date().getTime() - new Date(dateString).getTime()) / 60000);
    if (diff < 60) return `Hace ${diff} min`;
    const hours = Math.floor(diff / 60);
    if (hours < 24) return `Hace ${hours}h`;
    return `Hace ${Math.floor(hours / 24)}d`;
  };

  const getImageUrl = (path, type, name = 'Usuario') => {
    if (!path || path === 'perfil_default.png' || path === 'default.png') return type === 'post' ? 'https://images.unsplash.com/photo-1542601906990-b4d3fb778b09?auto=format&fit=crop&q=80' : `https://api.dicebear.com/7.x/identicon/png?seed=${encodeURIComponent(name)}`;
    if (path.startsWith('http')) return path;

    const baseUrl = api.defaults.baseURL ? api.defaults.baseURL.replace(/\/api\/?$/, '') : 'https://zerowaste-qro.com';
    return type === 'post' ? `${baseUrl}/static/img/posts/${path}` : `${baseUrl}/static/img/perfiles/${path}`;
  };

  const getCatStyle = (catName) => {
    const defaultStyle = { bg: 'transparent', text: '#4B5563', border: '#E5E7EB', icon: <Folder size={10} color="#4B5563" /> };
    if (!catName) return defaultStyle;
    const name = catName.trim();
    if (name === 'Reciclaje') return { bg: 'transparent', text: '#b45309', border: '#fcd34d', icon: <Recycle size={10} color="#b45309" /> };
    if (name === 'Compostaje') return { bg: 'transparent', text: '#047857', border: '#6ee7b7', icon: <Leaf size={10} color="#047857" /> };
    if (name === 'Reducción de residuos') return { bg: 'transparent', text: '#0e7490', border: '#67e8f9', icon: <Archive size={10} color="#0e7490" /> };
    if (name === 'Eventos') return { bg: 'transparent', text: '#6d28d9', border: '#c4b5fd', icon: <Calendar size={10} color="#6d28d9" /> };
    if (name === 'Dudas') return { bg: 'transparent', text: '#be123c', border: '#fda4af', icon: <HelpCircle size={10} color="#be123c" /> };
    return defaultStyle;
  };

  return (
    <SafeAreaView className="flex-1 bg-[#ECFDF5]" edges={['top']}>
      <StatusBar style="dark" />
      <LinearGradient
        colors={['#D1FAE5', '#ECFDF5', '#ECFDF5']}
        locations={[0, 0.2, 1]}
        className="absolute inset-0"
      />

      <ScrollView 
        showsVerticalScrollIndicator={false} 
        contentContainerStyle={{ paddingTop: 16, paddingBottom: 100 }}
        onScroll={handleScroll}
        scrollEventThrottle={16}
      >

        {/* ─── HEADER ──────────────────────────────── */}
        <View className="px-5 mb-6 flex-row items-center justify-between">
          <View className="flex-1 flex-row items-center bg-white h-12 rounded-full px-4 shadow-sm border border-gray-100 mr-4">
            <Search color="#9CA3AF" size={20} />
            <TextInput
              placeholder="Buscar..."
              placeholderTextColor="#9CA3AF"
              className="flex-1 ml-2 text-[15px] text-gray-800 font-medium h-full"
            />
          </View>

          <View className="flex-row items-center gap-3">
            <TouchableOpacity className="w-12 h-12 rounded-full bg-white items-center justify-center shadow-sm border border-gray-100">
              <Bell color="#4B5563" size={20} />
              <View className="absolute top-3 right-3 w-2 h-2 rounded-full bg-red-500 border border-white" />
            </TouchableOpacity>

            <TouchableOpacity className="w-12 h-12 rounded-full bg-white shadow-sm border border-gray-100 p-0.5">
              <Image
                source={{ uri: user?.foto_perfil ? getImageUrl(user.foto_perfil, 'perfil') : 'https://i.pravatar.cc/150?img=11' }}
                className="w-full h-full rounded-full"
              />
            </TouchableOpacity>
          </View>
        </View>

        {/* ─── CREATE POST (Facebook style) ────────────────────── */}
        <View className="px-5 mb-6">
          <View className="bg-white rounded-[24px] p-4 shadow-sm border border-gray-100 flex-row items-center gap-3">
            <Image
              source={{ uri: user?.foto_perfil ? getImageUrl(user.foto_perfil, 'perfil') : 'https://i.pravatar.cc/150?img=11' }}
              className="w-10 h-10 rounded-full bg-gray-100"
            />
            <TouchableOpacity
              className="flex-1 bg-gray-50 h-10 rounded-full px-4 justify-center"
              onPress={() => navigation.navigate('CreatePost')}
            >
              <Text className="text-gray-400 font-medium text-[14px]">¿Qué quieres compartir hoy?</Text>
            </TouchableOpacity>
          </View>
        </View>

        {/* ─── TABS ───────────────────────────────── */}
        <View className="pl-5 mb-8">
          <ScrollView horizontal showsHorizontalScrollIndicator={false}>
            {tabs.map(tab => {
              const isActive = activeTab === tab;
              return (
                <TouchableOpacity
                  key={tab}
                  onPress={() => setActiveTab(tab)}
                  className={`mr-3 px-6 py-3 rounded-full ${isActive ? 'bg-[#064E3B]' : 'bg-white shadow-sm border border-[#D1FAE5]'}`}
                >
                  <Text className={`font-black text-[14px] ${isActive ? 'text-white' : 'text-[#059669]'}`}>
                    {tab}
                  </Text>
                </TouchableOpacity>
              );
            })}
          </ScrollView>
        </View>

        {/* ─── FEED ───────────────────────────────── */}
        <View className="px-5">
          {posts.length === 0 && !isLoading ? (
            <View className="items-center justify-center py-20">
              <MessageCircle color="#9CA3AF" size={48} className="mb-4 opacity-50" />
              <Text className="text-gray-400 font-bold text-lg">No hay publicaciones aún</Text>
              <Text className="text-gray-400 text-sm text-center mt-2">Sé el primero en compartir algo con la comunidad.</Text>
            </View>
          ) : (
            posts.map((post, index) => {
              const catStyle = getCatStyle(post.categoria_nombre);
              const isTrend = index === 0 && activeTab === 'Todos';

              if (isTrend) {
                // Función para replicar el texto verde del diseño en Flask
                const renderTrendTitle = (title) => {
                  const parts = title.split(':');
                  if (parts.length > 1) {
                    const main = parts[0] + ':';
                    const rest = parts.slice(1).join(':').trim().split(' ');
                    const greenWords = rest.slice(0, 2).join(' ');
                    const darkWords = rest.slice(2).join(' ');
                    return (
                      <Text>
                        <Text style={{ color: '#1F2937' }}>{main}</Text>{'\n'}
                        <Text style={{ color: '#10B981' }}>{greenWords}</Text>
                        {darkWords ? <Text style={{ color: '#1F2937' }}> {darkWords}</Text> : null}
                      </Text>
                    );
                  }
                  return <Text className="text-[#1F2937]">{title}</Text>;
                };

                return (
                  <View
                    key={post.id || index}
                    className="bg-white rounded-[32px] overflow-hidden mb-8 shadow-[0_10px_40px_rgba(0,0,0,0.06)] border border-gray-100"
                  >
                    <View className="h-[340px] relative bg-gray-100">
                      <Image
                        source={{ uri: getImageUrl(post.imagen, 'post') }}
                        className="w-full h-full"
                        resizeMode="cover"
                      />

                      {/* White fade at bottom - Taller for the text */}
                      <LinearGradient
                        colors={['transparent', 'rgba(255,255,255,0.4)', 'rgba(255,255,255,0.9)', '#ffffff']}
                        locations={[0, 0.5, 0.8, 1]}
                        style={{ position: 'absolute', bottom: 0, left: 0, right: 0, height: 180 }}
                        pointerEvents="none"
                      />

                      {/* TENDENCIA PILL (Liquid Glass) */}
                      <View className="absolute top-6 left-5 overflow-hidden rounded-full border border-white/20">
                        <BlurView intensity={40} tint="dark" className="flex-row items-center gap-2 px-4 py-2 bg-black/20">
                          <View className="w-2.5 h-2.5 rounded-full bg-[#10B981] shadow-[0_0_10px_rgba(16,185,129,0.8)]" />
                          <Text className="text-white text-[11px] font-black tracking-widest uppercase">
                            Tendencia en {post.categoria_nombre || 'Ecología'}
                          </Text>
                        </BlurView>
                      </View>

                      {/* BOOKMARK */}
                      <View className="absolute top-6 right-5 w-11 h-11 rounded-full overflow-hidden border border-white/20">
                        <BlurView intensity={40} tint="dark" className="w-full h-full items-center justify-center bg-black/20">
                          <Bookmark color="#fff" size={20} />
                        </BlurView>
                      </View>
                    </View>

                    {/* CONTENT SECTION OVERLAPPING THE FADE */}
                    <View className="px-6 pb-8 -mt-20 relative z-10">
                      <Text className="text-[#10B981] text-[12px] font-black tracking-[0.2em] uppercase mb-3">
                        Artículo Destacado
                      </Text>

                      <Text className="text-[36px] font-black leading-[1.05] mb-5">
                        {renderTrendTitle(post.titulo)}
                      </Text>

                      <Text className="text-[16px] text-gray-500 font-medium mb-8 leading-relaxed pr-2">
                        {stripHtml(post.contenido)}
                      </Text>

                      <View className="flex-row items-center justify-between">
                        <TouchableOpacity
                          onPress={() => navigation.navigate('PostDetail', { id: post.id })}
                          className="flex-row items-center gap-2 bg-white border border-gray-200 px-6 py-3.5 rounded-2xl shadow-[0_4px_12px_rgba(0,0,0,0.04)]"
                        >
                          <Text className="text-[#064E3B] font-black text-[15px]">Leer más</Text>
                          <ArrowRight color="#064E3B" size={18} strokeWidth={3} />
                        </TouchableOpacity>

                        <View className="flex-row items-center gap-5 pr-2">
                          <View className="flex-row items-center gap-2">
                            <Heart color="#1F2937" size={24} strokeWidth={2.5} />
                            <Text className="text-gray-800 text-[16px] font-black">{post.total_likes > 0 ? (post.total_likes > 999 ? '1.2k' : post.total_likes) : 0}</Text>
                          </View>
                          <TouchableOpacity>
                            <Share2 color="#1F2937" size={24} strokeWidth={2.5} />
                          </TouchableOpacity>
                        </View>
                      </View>
                    </View>
                  </View>
                );
              }

              // Normal layout for other posts
              return (
                <View
                  key={post.id || index}
                  className="bg-white rounded-[32px] overflow-hidden mb-6 shadow-sm border border-gray-100"
                >
                  {/* ══ IMAGE SECTION ══ */}
                  <View className="h-[220px] relative bg-gray-100">
                    <Image
                      source={{ uri: getImageUrl(post.imagen, 'post') }}
                      className="w-full h-full"
                      resizeMode="cover"
                    />

                    {/* Premium smooth fade into white */}
                    <LinearGradient
                      colors={['transparent', 'rgba(255,255,255,0.4)', 'rgba(255,255,255,0.8)', '#ffffff']}
                      locations={[0, 0.4, 0.7, 1]}
                      style={{ position: 'absolute', bottom: 0, left: 0, right: 0, height: 100 }}
                      pointerEvents="none"
                    />

                    {/* Bookmark Ribbon */}
                    <View className="absolute top-0 right-6 w-10 h-14 bg-[#059669] rounded-b-lg items-center justify-center shadow-sm">
                      <Bookmark color="#fff" size={18} fill="#34D399" className="-mt-2" />
                    </View>

                  </View>

                  {/* ══ CONTENT SECTION ══ */}
                  <View className="px-6 pb-6 pt-4">
                    {/* Category & Author nicely separated from the image */}
                    <View className="flex-row justify-between items-center mb-4">
                      <View className="flex-row items-center gap-1.5 px-3 py-1 rounded-full bg-[#ECFDF5]" style={{ borderWidth: 1, borderColor: catStyle.border }}>
                        {catStyle.icon}
                        <Text className="text-[10px] font-bold uppercase tracking-wider" style={{ color: catStyle.text }}>
                          {post.categoria_nombre || 'General'}
                        </Text>
                      </View>

                      <View className="flex-row items-center gap-2">
                        <View className="items-end">
                          <Text className="text-[13px] font-bold text-gray-800">{post.autor_nombre || 'Usuario'}</Text>
                          <Text className="text-[11px] font-medium text-gray-500 mt-0.5">{getTimeAgo(post.created_at)}</Text>
                        </View>
                        <Image
                          source={{ uri: getImageUrl(post.autor_foto, 'perfil', post.autor_nombre || 'Usuario') }}
                          className="w-10 h-10 rounded-full border border-gray-100"
                        />
                      </View>
                    </View>

                    <Text className="text-[20px] font-black text-gray-900 leading-tight mb-2 pr-4">
                      {post.titulo}
                    </Text>

                    <View className="flex-row items-center gap-1.5 mb-6">
                      <ArrowUpRight color="#6B7280" size={14} />
                      <Text className="text-gray-500 text-[13px] font-medium flex-1" numberOfLines={1}>
                        {stripHtml(post.contenido)}
                      </Text>
                    </View>

                    <View className="flex-row items-center justify-between border-t border-gray-100 pt-4 mt-3">
                      <TouchableOpacity
                        onPress={() => navigation.navigate('PostDetail', { id: post.id })}
                        className="flex-row items-center gap-2 bg-emerald-500 px-5 py-2.5 rounded-xl"
                      >
                        <Text className="text-white font-black text-[13px]">Ver Post</Text>
                        <ArrowRight color="#fff" size={14} strokeWidth={3} />
                      </TouchableOpacity>

                      <View className="flex-row items-center gap-4">
                        <View className="flex-row items-center gap-1.5">
                          <Heart color="#EF4444" size={18} fill={post.total_likes > 0 ? "#EF4444" : "transparent"} />
                          <Text className="text-gray-600 text-[13px] font-bold">{post.total_likes || 0}</Text>
                        </View>
                        <View className="flex-row items-center gap-1.5">
                          <MessageCircle color="#9CA3AF" size={18} />
                          <Text className="text-gray-600 text-[13px] font-bold">{post.total_respuestas || 0}</Text>
                        </View>
                        <TouchableOpacity className="ml-1">
                          <Share2 color="#9CA3AF" size={18} />
                        </TouchableOpacity>
                      </View>
                    </View>
                  </View>
                </View>
              );
            })
          )}
        </View>

      </ScrollView>
    </SafeAreaView>
  );
}
