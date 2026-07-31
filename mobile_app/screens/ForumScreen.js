import React, { useState, useEffect } from 'react';
import { View, Text, ScrollView, TouchableOpacity, Image, TextInput, Dimensions, RefreshControl, Share } from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import { BlurView } from 'expo-blur';
import {
  Search,
  Bell,
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
import { ArrowRight } from 'lucide-react-native';
import { StatusBar } from 'expo-status-bar';
import { SafeAreaView } from 'react-native-safe-area-context';
import { useScrollContext } from '../context/ScrollContext';
import { forumPostImageUrl, profileImageUrl } from '../utils/media';
import { formatRelativeDate } from '../utils/date';
import RemoteImage from '../components/ui/RemoteImage';

const { width } = Dimensions.get('window');

// ─── TYPES ─────────────────────────────────────────────────────────

// Strip HTML tags from content
const stripHtml = (html) => {
  if (!html || typeof html !== 'string') return '';
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
  const [error, setError] = useState('');
  const [searchQuery, setSearchQuery] = useState('');

  useEffect(() => {
    fetchPosts();
  }, []);

  const fetchPosts = async () => {
    setIsLoading(true);
    setError('');
    try {
      // 1. Obtener categorías desde FastAPI (o hardcoded temporalmente si no hay endpoint)
      // Como no hay endpoint directo de categorías en FastAPI sin auth, usamos api
      try {
        const catRes = await api.get('/foro/categorias');
        if (catRes.data && catRes.data.length > 0) {
          setTabs(['Todo', ...catRes.data.map((c) => c.nombre)]);
        }
      } catch (e) {
        setError(e.userMessage || 'No se pudieron cargar las categorías.');
      }

      // 2. Cargar Posts desde FastAPI (arquitectura correcta: RN -> FastAPI -> Supabase)
      const response = await api.get('/foro/posts');
      if (response.data && response.data.length > 0) {
        setPosts(response.data);
      } else {
        setPosts([]);
      }
    } catch (e) {
      setPosts([]);
      setError(e.userMessage || 'No se pudo cargar el foro.');
    } finally {
      setIsLoading(false);
    }
  };

  const getTimeAgo = formatRelativeDate;

  const getImageUrl = (path, type) => type === 'post' ? forumPostImageUrl(path) : profileImageUrl(path);

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

  const sharePost = (post) => Share.share({
    title: post.titulo,
    message: `${post.titulo}\nhttps://www.zerowaste-qro.com/foro`,
  }).catch(() => {});

  return (
    <SafeAreaView className="flex-1 bg-[#ECFDF5]" edges={['top']}>
      <StatusBar style="dark" />
      {error ? (
        <View className="mx-5 mt-3 rounded-2xl border border-red-200 bg-red-50 p-4">
          <Text className="text-red-700 font-bold text-center">{error}</Text>
          <TouchableOpacity onPress={fetchPosts} className="mt-3 self-center rounded-xl bg-red-600 px-5 py-2">
            <Text className="text-white font-black">Reintentar</Text>
          </TouchableOpacity>
        </View>
      ) : null}
      <LinearGradient
        colors={['#D1FAE5', '#ECFDF5', '#ECFDF5']}
        locations={[0, 0.2, 1]}
        className="absolute inset-0"
      />

      <ScrollView 
        showsVerticalScrollIndicator={false} 
        contentContainerStyle={{ paddingTop: 16, paddingBottom: 130 }}
        onScroll={handleScroll}
        scrollEventThrottle={16}
        refreshControl={<RefreshControl refreshing={isLoading} onRefresh={fetchPosts} tintColor="#047857" />}
      >

        {/* ─── HEADER ──────────────────────────────── */}
        <View className="px-5 mb-6 flex-row items-center justify-between">
          <View className="flex-1 flex-row items-center bg-white h-12 rounded-full px-4 shadow-sm border border-gray-100 mr-4">
            <Search color="#9CA3AF" size={20} />
            <TextInput
              placeholder="Buscar..."
              placeholderTextColor="#9CA3AF"
              value={searchQuery}
              onChangeText={setSearchQuery}
              className="flex-1 ml-2 text-[15px] text-gray-800 font-medium h-full"
            />
          </View>

          <View className="flex-row items-center gap-3">
            <TouchableOpacity onPress={() => navigation.navigate('Notifications')} className="w-12 h-12 rounded-full bg-white items-center justify-center shadow-sm border border-gray-100">
              <Bell color="#4B5563" size={20} />
              <View className="absolute top-3 right-3 w-2 h-2 rounded-full bg-red-500 border border-white" />
            </TouchableOpacity>

            <TouchableOpacity onPress={() => navigation.navigate('Profile')} className="w-12 h-12 rounded-full bg-white shadow-sm border border-gray-100 p-0.5">
              <Image source={getImageUrl(user?.foto_perfil, 'perfil') ? { uri: getImageUrl(user.foto_perfil, 'perfil') } : require('../assets/images/logo.png')} className="w-full h-full rounded-full" />
            </TouchableOpacity>
          </View>
        </View>

        {/* ─── CREATE POST (Facebook style) ────────────────────── */}
        <View className="px-5 mb-6">
          <View className="bg-white rounded-[24px] p-4 shadow-sm border border-gray-100 flex-row items-center gap-3">
            <Image source={getImageUrl(user?.foto_perfil, 'perfil') ? { uri: getImageUrl(user.foto_perfil, 'perfil') } : require('../assets/images/logo.png')} className="w-10 h-10 rounded-full bg-gray-100" />
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
            posts
              .filter(post => activeTab === 'Todo' || activeTab === 'Todos' || post.categoria_nombre === activeTab)
              .filter(post => {
                const needle = searchQuery.trim().toLocaleLowerCase('es');
                return !needle || `${post.titulo || ''} ${stripHtml(post.contenido)}`.toLocaleLowerCase('es').includes(needle);
              })
              .map((post, index) => {
                const catStyle = getCatStyle(post.categoria_nombre);
                const postImageUrl = getImageUrl(post.imagen, 'post');
                const isTrend = Boolean(postImageUrl) && index === 0 && (activeTab === 'Todo' || activeTab === 'Todos');

              if (isTrend) {
                // Función para replicar el texto verde del diseño en Flask
                const renderTrendTitle = (title) => {
                  if (!title || typeof title !== 'string') {
                    return <Text className="text-[#1F2937]">{title || ''}</Text>;
                  }
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
                      <RemoteImage uri={postImageUrl} className="w-full h-full" />

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
                          <TouchableOpacity onPress={() => sharePost(post)} accessibilityLabel="Compartir publicación">
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
                  {postImageUrl ? <View className="h-[220px] relative bg-gray-100">
                    <RemoteImage uri={postImageUrl} className="w-full h-full" />

                    {/* Premium smooth fade into white */}
                    <LinearGradient
                      colors={['transparent', 'rgba(255,255,255,0.4)', 'rgba(255,255,255,0.8)', '#ffffff']}
                      locations={[0, 0.4, 0.7, 1]}
                      style={{ position: 'absolute', bottom: 0, left: 0, right: 0, height: 100 }}
                      pointerEvents="none"
                    />

                  </View> : null}

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
                          source={getImageUrl(post.autor_foto, 'perfil') ? { uri: getImageUrl(post.autor_foto, 'perfil') } : require('../assets/images/logo.png')}
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
                        <TouchableOpacity onPress={() => sharePost(post)} className="ml-1" accessibilityLabel="Compartir publicación">
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
