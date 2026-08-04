import React, { useCallback, useEffect, useRef, useState } from 'react';
import { FlatList, Pressable, View, Text, ScrollView, TouchableOpacity, TextInput, RefreshControl, Share, useWindowDimensions } from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import { BlurView } from 'expo-blur';
import {
  Search,
  Bell,
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
import { useFocusEffect, useNavigation } from '@react-navigation/native';
import { useAuth } from '../store/useAuth';
import { ArrowRight } from 'lucide-react-native';
import { StatusBar } from 'expo-status-bar';
import { SafeAreaView } from 'react-native-safe-area-context';
import { useScrollContext } from '../context/ScrollContext';
import { normalizeMediaUrl } from '../utils/media';
import { mobileShareUrl } from '../navigation/linking';
import { formatRelativeDate } from '../utils/date';
import RemoteImage from '../components/ui/RemoteImage';
import UserAvatar from '../components/ui/UserAvatar';
import LikeButton from '../components/forum/LikeButton';
import { htmlToPlainText } from '../utils/text';
import { resolveAvatar, resolveDisplayName } from '../utils/user';
import { PostCardSkeleton } from '../components/ui/Skeleton';
import CommentsModal from '../components/forum/CommentsModal';

// ─── TYPES ─────────────────────────────────────────────────────────

// Strip HTML tags from content
const stripHtml = htmlToPlainText;

export default function ForumScreen({ route }) {
  const navigation = useNavigation();
  const { width: screenWidth } = useWindowDimensions();
  const compactLayout = screenWidth < 350;
  const { handleScroll } = useScrollContext();
  const { user } = useAuth();
  const [activeTab, setActiveTab] = useState('Todo');
  const [posts, setPosts] = useState([]);
  const [isLoading, setIsLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [tabs, setTabs] = useState(['Todo']);
  const [error, setError] = useState('');
  const [searchQuery, setSearchQuery] = useState('');
  const [likePending, setLikePending] = useState({});
  const [commentsPost, setCommentsPost] = useState(null);
  const pendingLikesRef = useRef(new Set());
  const postsRequestRef = useRef(0);
  const hasLoadedRef = useRef(false);
  const navigationPendingRef = useRef(false);

  const fetchPosts = useCallback(async ({ manualRefresh = false } = {}) => {
    const requestId = ++postsRequestRef.current;
    if (!hasLoadedRef.current) setIsLoading(true);
    if (manualRefresh) setRefreshing(true);
    setError('');
    try {
      // Las categorías y publicaciones siempre provienen de FastAPI.
      try {
        const catRes = await api.get('/foro/categorias');
        if (catRes.data && catRes.data.length > 0) {
          const categoryNames = catRes.data
            .map((category) => String(category.nombre || '').trim())
            .filter((name) => name && name !== 'Todo' && name !== 'Todos');
          setTabs(['Todo', ...new Set(categoryNames)]);
        }
      } catch {
        // Conserva categorías visibles mientras la sección se sincroniza.
      }

      // RN -> FastAPI -> Supabase
      const response = await api.get('/foro/posts');
      if (requestId !== postsRequestRef.current) return;
      const rows = Array.isArray(response.data) ? response.data : [];
      setPosts(rows.map((post) => ({ ...post, type: post.type || 'forum_post' })));
    } catch (e) {
      if (requestId !== postsRequestRef.current) return;
      setError(e.userMessage || 'No se pudo cargar el foro.');
    } finally {
      if (requestId === postsRequestRef.current) {
        hasLoadedRef.current = true;
        setIsLoading(false);
        setRefreshing(false);
      }
    }
  }, []);

  useFocusEffect(useCallback(() => {
    // Sincroniza en segundo plano sin vaciar el feed ni activar el indicador del encabezado.
    void fetchPosts();
  }, [fetchPosts]));

  useEffect(() => {
    const createdPost = route?.params?.createdPost;
    if (!createdPost?.id) return;
    setPosts((current) => [
      { ...createdPost, type: createdPost.type || 'forum_post' },
      ...current.filter((post) => String(post.id) !== String(createdPost.id)),
    ]);
    navigation.setParams({ createdPost: undefined });
  }, [navigation, route?.params?.updateKey]);

  const openPost = (post, focusComments = false) => {
    if (focusComments) {
      setCommentsPost(post);
      return;
    }
    if (!post?.id || navigationPendingRef.current) return;
    navigationPendingRef.current = true;
    navigation.navigate('PostDetail', { id: post.id, focusComments });
    setTimeout(() => { navigationPendingRef.current = false; }, 500);
  };

  const togglePostLike = async (post) => {
    if (pendingLikesRef.current.has(post.id)) return;
    pendingLikesRef.current.add(post.id);
    const previousLiked = Boolean(post.liked_by_me);
    const previousCount = Math.max(0, Number(post.likes_count ?? post.total_likes) || 0);
    const nextLiked = !previousLiked;
    const optimisticCount = Math.max(0, previousCount + (nextLiked ? 1 : -1));
    const updatePost = (changes) => setPosts((current) => current.map((item) => (
      item.id === post.id ? { ...item, ...changes } : item
    )));
    setLikePending((current) => ({ ...current, [post.id]: true }));
    setError('');
    updatePost({ liked_by_me: nextLiked, likes_count: optimisticCount, total_likes: optimisticCount });
    try {
      const response = nextLiked
        ? await api.put(`/foro/posts/${post.id}/like`)
        : await api.delete(`/foro/posts/${post.id}/like`);
      const count = Math.max(0, Number(response.data?.likes_count ?? response.data?.total) || 0);
      updatePost({ liked_by_me: Boolean(response.data?.liked), likes_count: count, total_likes: count });
    } catch (requestError) {
      updatePost({ liked_by_me: previousLiked, likes_count: previousCount, total_likes: previousCount });
      setError(requestError.userMessage || 'No se pudo actualizar el Me gusta.');
    } finally {
      pendingLikesRef.current.delete(post.id);
      setLikePending((current) => ({ ...current, [post.id]: false }));
    }
  };

  const getTimeAgo = formatRelativeDate;

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

  const sharePost = async (post) => {
    try {
      await Share.share({
        title: post.titulo,
        message: `Te comparto esta publicación de ZeroWaste: ${post.titulo}\n${mobileShareUrl('posts', post.id)}`,
      });
    } catch {
      setError('No se pudo abrir el menú para compartir esta publicación.');
    }
  };

  const currentAvatarUrl = resolveAvatar(user);
  const normalizedSearch = searchQuery.trim().toLocaleLowerCase('es');
  const visiblePosts = posts
    .filter((post) => activeTab === 'Todo' || activeTab === 'Todos' || post.categoria_nombre === activeTab)
    .filter((post) => !normalizedSearch || `${post.titulo || ''} ${stripHtml(post.contenido)}`.toLocaleLowerCase('es').includes(normalizedSearch));

  return (
    <SafeAreaView className="flex-1 bg-[#ECFDF5]" edges={['top']}>
      <StatusBar style="dark" />
      {error ? (
        <View className="z-20 mx-5 mt-3 rounded-2xl border border-red-200 bg-red-50 p-4">
          <Text className="text-red-700 font-bold text-center">{error}</Text>
          <TouchableOpacity onPress={() => fetchPosts({ manualRefresh: true })} className="mt-3 self-center rounded-xl bg-red-600 px-5 py-2">
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
        refreshControl={<RefreshControl refreshing={refreshing} onRefresh={() => fetchPosts({ manualRefresh: true })} tintColor="#047857" />}
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
              <UserAvatar uri={currentAvatarUrl} name={user?.nombre} size={44} accessibilityLabel="Avatar del usuario" />
            </TouchableOpacity>
          </View>
        </View>

        {/* ─── CREATE POST (Facebook style) ────────────────────── */}
        <View className="px-5 mb-6">
          <View className="bg-white rounded-[24px] p-4 shadow-sm border border-gray-100 flex-row items-center gap-3">
            <UserAvatar uri={currentAvatarUrl} name={user?.nombre} size={40} accessibilityLabel="Avatar del usuario" />
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
          <FlatList
            horizontal
            data={tabs}
            keyExtractor={(tab) => tab}
            showsHorizontalScrollIndicator={false}
            contentContainerStyle={{ paddingRight: 20 }}
            ItemSeparatorComponent={() => <View className="w-3" />}
            renderItem={({ item: tab }) => {
              const isActive = activeTab === tab;
              return (
                <Pressable
                  onPress={() => setActiveTab(tab)}
                  accessibilityRole="button"
                  accessibilityState={{ selected: isActive }}
                  className={`min-h-11 justify-center rounded-full px-5 py-2.5 ${isActive ? 'bg-[#064E3B]' : 'border border-[#D1FAE5] bg-white shadow-sm'}`}
                  style={({ pressed }) => ({ opacity: pressed ? 0.82 : 1, transform: [{ scale: pressed ? 0.97 : 1 }] })}
                >
                  <Text className={`font-black text-[14px] ${isActive ? 'text-white' : 'text-[#059669]'}`}>
                    {tab}
                  </Text>
                </Pressable>
              );
            }}
          />
        </View>

        {/* ─── FEED ───────────────────────────────── */}
        <View className="px-5">
          {isLoading && posts.length === 0 ? <><PostCardSkeleton /><PostCardSkeleton /></> : visiblePosts.length === 0 ? (
            <View className="items-center justify-center py-20">
              <MessageCircle color="#9CA3AF" size={48} className="mb-4 opacity-50" />
              <Text className="text-gray-500 font-bold text-lg">{posts.length ? 'No encontramos resultados' : 'No hay publicaciones aún'}</Text>
              <Text className="mt-2 text-center text-sm text-gray-400">{posts.length ? 'Prueba otra categoría o cambia tu búsqueda.' : 'Sé el primero en compartir algo con la comunidad.'}</Text>
            </View>
          ) : (
            visiblePosts
              .map((post) => {
                const catStyle = getCatStyle(post.categoria_nombre);
                const postImageUrl = normalizeMediaUrl(post.image_url ?? post.imagen, 'foro');
                const isTrend = post.type === 'article';

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
                        <Text style={{ color: '#1F2937' }}>{main}</Text>{' '}
                        <Text style={{ color: '#10B981' }}>{greenWords}</Text>
                        {darkWords ? <Text style={{ color: '#1F2937' }}> {darkWords}</Text> : null}
                      </Text>
                    );
                  }
                  return <Text className="text-[#1F2937]">{title}</Text>;
                };

                return (
                  <View
                    key={`article:${post.id}`}
                    className="bg-white rounded-[32px] overflow-hidden mb-8 shadow-[0_10px_40px_rgba(0,0,0,0.06)] border border-gray-100"
                  >
                    <View className="relative bg-gray-100" style={{ aspectRatio: 16 / 10 }}>
                      <RemoteImage uri={postImageUrl} className="w-full h-full" aspectRatio={16 / 10} />

                      {/* White fade at bottom - Taller for the text */}
                      <LinearGradient
                        colors={['transparent', 'rgba(255,255,255,0.4)', 'rgba(255,255,255,0.9)', '#ffffff']}
                        locations={[0, 0.5, 0.8, 1]}
                        style={{ position: 'absolute', bottom: 0, left: 0, right: 0, height: 130 }}
                        pointerEvents="none"
                      />

                      {/* TENDENCIA PILL (Liquid Glass) */}
                      <View className="absolute left-5 right-5 top-5 flex-row">
                        <View className="max-w-full overflow-hidden rounded-full border border-white/20">
                        <BlurView intensity={40} tint="dark" className="flex-row items-center gap-2 bg-black/20 px-4 py-2">
                          <View className="w-2.5 h-2.5 rounded-full bg-[#10B981] shadow-[0_0_10px_rgba(16,185,129,0.8)]" />
                          <Text className="shrink text-[11px] font-black uppercase tracking-widest text-white" numberOfLines={1} ellipsizeMode="tail">
                            Tendencia en {post.categoria_nombre || 'Ecología'}
                          </Text>
                        </BlurView>
                        </View>
                      </View>

                    </View>

                    {/* CONTENT SECTION OVERLAPPING THE FADE */}
                    <View className="relative z-10 -mt-12 px-5 pb-6">
                      <Text className="text-[#10B981] text-[12px] font-black tracking-[0.2em] uppercase mb-3">
                        Artículo Destacado
                      </Text>

                      <Text className="mb-3 text-[28px] font-black leading-8" numberOfLines={3} ellipsizeMode="tail">
                        {renderTrendTitle(post.titulo)}
                      </Text>

                      <Text className="mb-5 pr-1 text-[15px] font-medium leading-6 text-gray-600" numberOfLines={4} ellipsizeMode="tail">
                        {stripHtml(post.contenido)}
                      </Text>

                      <View className="flex-row items-center justify-between gap-3">
                        <Pressable
                          onPress={() => openPost(post)}
                          accessibilityRole="button"
                          className="min-h-12 flex-row items-center gap-2 rounded-2xl border border-gray-200 bg-white px-5"
                          style={({ pressed }) => ({ opacity: pressed ? 0.78 : 1, transform: [{ scale: pressed ? 0.98 : 1 }] })}
                        >
                          <Text className="text-[#064E3B] font-black text-[15px]">Leer más</Text>
                          <ArrowRight color="#064E3B" size={18} strokeWidth={3} />
                        </Pressable>

                        <View className="flex-row items-center gap-2">
                          <LikeButton liked={Boolean(post.liked_by_me)} count={post.likes_count ?? post.total_likes} pending={Boolean(likePending[post.id])} onPress={() => togglePostLike(post)} size={24} />
                          <Pressable onPress={() => sharePost(post)} accessibilityRole="button" accessibilityLabel="Compartir publicación" className="h-11 w-11 items-center justify-center rounded-full" style={({ pressed }) => ({ opacity: pressed ? 0.65 : 1, transform: [{ scale: pressed ? 0.92 : 1 }] })}>
                            <Share2 color="#1F2937" size={24} strokeWidth={2.5} />
                          </Pressable>
                        </View>
                      </View>
                    </View>
                  </View>
                );
              }

              // Normal layout for other posts
              return (
                <View
                  key={`post:${post.id}`}
                  className="bg-white rounded-[32px] overflow-hidden mb-6 shadow-sm border border-gray-100"
                >
                  {/* ══ IMAGE SECTION ══ */}
                  {postImageUrl ? <View className="relative bg-gray-100" style={{ aspectRatio: 16 / 10 }}>
                    <RemoteImage uri={postImageUrl} className="h-full w-full" aspectRatio={16 / 10} />

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
                    <View className="mb-4 flex-row items-center justify-between gap-2">
                      <View className="max-w-[46%] flex-row items-center gap-1.5 rounded-full bg-[#ECFDF5] px-3 py-1" style={{ borderWidth: 1, borderColor: catStyle.border }}>
                        {catStyle.icon}
                          <Text className="shrink text-[10px] font-bold uppercase tracking-wider" numberOfLines={1} ellipsizeMode="tail" style={{ color: catStyle.text }}>
                          {post.categoria_nombre || 'General'}
                        </Text>
                      </View>

                      <View className="min-w-0 flex-1 flex-row items-center justify-end gap-2">
                        <View className="min-w-0 flex-1 items-end">
                           <Text className="w-full text-right text-[13px] font-bold text-gray-800" numberOfLines={1} ellipsizeMode="tail">{post.autor_nombre || 'Usuario'}</Text>
                          <Text className="text-[11px] font-medium text-gray-500 mt-0.5">{getTimeAgo(post.created_at)}</Text>
                        </View>
                        <UserAvatar uri={resolveAvatar(post)} name={resolveDisplayName(post)} size={40} accessibilityLabel="Avatar del autor" />
                      </View>
                    </View>

                    <Text className="text-[20px] font-black text-gray-900 leading-tight mb-2 pr-4" numberOfLines={3} ellipsizeMode="tail">
                      {post.titulo}
                    </Text>

                    {post.aprobado === false ? <View className="mb-3 self-start rounded-full border border-amber-200 bg-amber-50 px-3 py-1"><Text className="text-[11px] font-black text-amber-800">Pendiente de revisión · solo tú la ves</Text></View> : null}

                    <View className="flex-row items-center gap-1.5 mb-6">
                      <ArrowUpRight color="#6B7280" size={14} />
                      <Text className="text-gray-500 text-[13px] font-medium flex-1" numberOfLines={3} ellipsizeMode="tail">
                        {stripHtml(post.contenido)}
                      </Text>
                    </View>

                    <View className={`${compactLayout ? 'gap-3' : 'flex-row items-center justify-between gap-2'} mt-3 border-t border-gray-100 pt-4`}>
                      <Pressable
                        onPress={() => openPost(post)}
                        accessibilityRole="button"
                        className={`${compactLayout ? 'self-stretch justify-center' : ''} min-h-11 flex-row items-center gap-2 rounded-xl bg-emerald-600 px-5 py-2.5`}
                        style={({ pressed }) => ({ opacity: pressed ? 0.82 : 1, transform: [{ scale: pressed ? 0.98 : 1 }] })}
                      >
                        <Text className="text-white font-black text-[13px]">Ver Post</Text>
                        <ArrowRight color="#fff" size={14} strokeWidth={3} />
                      </Pressable>

                      <View className={`flex-row items-center ${compactLayout ? 'justify-between' : 'gap-2'}`}>
                        <LikeButton liked={Boolean(post.liked_by_me)} count={post.likes_count ?? post.total_likes} pending={Boolean(likePending[post.id])} onPress={() => togglePostLike(post)} size={18} />
                        <Pressable onPress={() => openPost(post, true)} accessibilityRole="button" accessibilityLabel={`${post.total_respuestas || 0} comentarios`} className="min-h-11 flex-row items-center gap-1.5 px-1" style={({ pressed }) => ({ opacity: pressed ? 0.65 : 1 })}>
                          <MessageCircle color="#9CA3AF" size={18} />
                          <Text className="text-gray-600 text-[13px] font-bold">{post.total_respuestas || 0}</Text>
                        </Pressable>
                        <Pressable onPress={() => sharePost(post)} className="ml-1 h-11 w-11 items-center justify-center rounded-full" accessibilityRole="button" accessibilityLabel="Compartir publicación" style={({ pressed }) => ({ opacity: pressed ? 0.65 : 1, transform: [{ scale: pressed ? 0.92 : 1 }] })}>
                          <Share2 color="#9CA3AF" size={18} />
                        </Pressable>
                      </View>
                    </View>
                  </View>
                </View>
              );
            })
          )}
        </View>

      </ScrollView>
      <CommentsModal
        visible={Boolean(commentsPost)}
        post={commentsPost}
        onClose={() => setCommentsPost(null)}
        onCountChange={(count) => {
          setPosts((current) => current.map((item) => item.id === commentsPost?.id ? { ...item, total_respuestas: count, comments_count: count } : item));
          setCommentsPost((current) => current ? { ...current, total_respuestas: count, comments_count: count } : current);
        }}
      />
    </SafeAreaView>
  );
}
