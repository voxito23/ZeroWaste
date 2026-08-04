import React, { useCallback, useRef, useState } from 'react';
import {
  ScrollView,
  Share,
  Text,
  TouchableOpacity,
  View,
} from 'react-native';
import {
  Archive,
  ArrowLeft,
  Calendar,
  Folder,
  HelpCircle,
  Leaf,
  MessageCircle,
  Recycle,
  Share2,
} from 'lucide-react-native';
import { useFocusEffect, useNavigation, useRoute } from '@react-navigation/native';
import { StatusBar } from 'expo-status-bar';
import { SafeAreaView } from 'react-native-safe-area-context';

import { api } from '../api/axios';
import LikeButton from '../components/forum/LikeButton';
import RemoteImage from '../components/ui/RemoteImage';
import UserAvatar from '../components/ui/UserAvatar';
import { formatRelativeDate } from '../utils/date';
import { normalizeMediaUrl } from '../utils/media';
import { htmlToPlainText } from '../utils/text';
import { resolveAvatar } from '../utils/user';
import { mobileShareUrl } from '../navigation/linking';
import CommentsModal from '../components/forum/CommentsModal';
import { PostCardSkeleton } from '../components/ui/Skeleton';


const categoryStyle = (category) => {
  const fallback = { text: '#4B5563', border: '#E5E7EB', icon: <Folder size={12} color="#4B5563" /> };
  if (!category) return fallback;
  const name = category.trim();
  if (name === 'Reciclaje') return { text: '#B45309', border: '#FCD34D', icon: <Recycle size={12} color="#B45309" /> };
  if (name === 'Compostaje') return { text: '#047857', border: '#6EE7B7', icon: <Leaf size={12} color="#047857" /> };
  if (name === 'Reducción de residuos') return { text: '#0E7490', border: '#67E8F9', icon: <Archive size={12} color="#0E7490" /> };
  if (name === 'Eventos') return { text: '#6D28D9', border: '#C4B5FD', icon: <Calendar size={12} color="#6D28D9" /> };
  if (name === 'Dudas') return { text: '#BE123C', border: '#FDA4AF', icon: <HelpCircle size={12} color="#BE123C" /> };
  return fallback;
};

export default function PostScreen() {
  const navigation = useNavigation();
  const route = useRoute();
  const postId = route.params?.id;
  const likeRequestRef = useRef(false);
  const detailRequestRef = useRef(0);
  const hasLoadedRef = useRef(false);

  const [post, setPost] = useState(null);
  const [loading, setLoading] = useState(true);
  const [loadError, setLoadError] = useState('');
  const [actionError, setActionError] = useState('');
  const [likePending, setLikePending] = useState(false);
  const [commentsModalVisible, setCommentsModalVisible] = useState(false);

  const fetchPostDetail = useCallback(async () => {
    if (!postId) return;
    const requestId = ++detailRequestRef.current;
    if (!hasLoadedRef.current) setLoading(true);
    setLoadError('');
    try {
      const postResponse = await api.get(`/foro/posts/${postId}`);
      if (requestId !== detailRequestRef.current) return;
      setPost(postResponse.data || null);
    } catch (error) {
      if (requestId !== detailRequestRef.current) return;
      setLoadError(error.userMessage || 'No se pudo cargar la publicación.');
    } finally {
      if (requestId === detailRequestRef.current) {
        hasLoadedRef.current = true;
        setLoading(false);
      }
    }
  }, [postId]);

  useFocusEffect(useCallback(() => {
    if (!hasLoadedRef.current) void fetchPostDetail();
    return () => { detailRequestRef.current += 1; };
  }, [fetchPostDetail]));

  React.useEffect(() => {
    if (post && route.params?.focusComments) setCommentsModalVisible(true);
  }, [post, route.params?.focusComments]);

  const toggleLike = async () => {
    if (!post || likeRequestRef.current) return;
    likeRequestRef.current = true;
    const previousLiked = Boolean(post.liked_by_me);
    const previousCount = Math.max(0, Number(post.likes_count ?? post.total_likes) || 0);
    const nextLiked = !previousLiked;
    const optimisticCount = Math.max(0, previousCount + (nextLiked ? 1 : -1));
    setLikePending(true);
    setActionError('');
    setPost((current) => ({
      ...current,
      liked_by_me: nextLiked,
      likes_count: optimisticCount,
      total_likes: optimisticCount,
    }));
    try {
      const response = nextLiked
        ? await api.put(`/foro/posts/${postId}/like`)
        : await api.delete(`/foro/posts/${postId}/like`);
      const count = Math.max(0, Number(response.data?.likes_count ?? response.data?.total) || 0);
      setPost((current) => ({
        ...current,
        liked_by_me: Boolean(response.data?.liked),
        likes_count: count,
        total_likes: count,
      }));
    } catch (error) {
      setPost((current) => ({
        ...current,
        liked_by_me: previousLiked,
        likes_count: previousCount,
        total_likes: previousCount,
      }));
      setActionError(error.userMessage || 'No se pudo actualizar el Me gusta.');
    } finally {
      likeRequestRef.current = false;
      setLikePending(false);
    }
  };

  if (loading && !post) {
    return <SafeAreaView className="flex-1 bg-slate-50" edges={['top']}><StatusBar style="dark" /><View className="flex-row items-center border-b border-slate-100 bg-white px-5 py-3"><View className="h-10 w-10 rounded-full bg-slate-100" /><Text className="ml-4 text-[18px] font-black text-slate-900">Publicación</Text></View><View className="px-4 pt-4"><PostCardSkeleton /></View></SafeAreaView>;
  }

  if (!post) {
    return (
      <View className="flex-1 items-center justify-center bg-emerald-50 px-8">
        <Text className="text-center font-bold text-emerald-950">{loadError || 'Publicación no encontrada'}</Text>
        <TouchableOpacity onPress={fetchPostDetail} className="mt-4 rounded-full border border-emerald-600 px-6 py-3"><Text className="font-bold text-emerald-700">Reintentar</Text></TouchableOpacity>
        <TouchableOpacity onPress={() => navigation.goBack()} className="mt-3 rounded-full bg-emerald-700 px-6 py-3"><Text className="font-bold text-white">Volver</Text></TouchableOpacity>
      </View>
    );
  }

  const category = categoryStyle(post.categoria_nombre);
  const author = post.author || {};
  const postAvatar = resolveAvatar(post);
  const postImage = normalizeMediaUrl(post.image_url ?? post.imagen, 'foro');
  const likesCount = post.likes_count ?? post.total_likes ?? 0;
  const commentsCount = post.comments_count ?? post.total_respuestas ?? 0;
  const sharePost = () => Share.share({
    title: post.titulo,
    message: `Te comparto esta publicación de ZeroWaste: ${post.titulo}\n${mobileShareUrl('posts', post.id)}`,
  }).catch(() => setActionError('No se pudo abrir el menú para compartir.'));

  const postHeader = (
    <>
      {loadError || actionError ? (
        <View className="mx-5 mt-3 rounded-xl border border-red-200 bg-red-50 p-3">
          <Text className="text-center font-bold text-red-700">{loadError || actionError}</Text>
        </View>
      ) : null}
      <View className="mx-4 mt-4 overflow-hidden rounded-[28px] border border-slate-100 bg-white px-5 py-6">
        <View className="mb-5 flex-row items-center gap-3">
          <UserAvatar uri={postAvatar} name={author.nombre || post.autor_nombre} size={48} accessibilityLabel="Avatar del autor" />
          <View className="flex-1">
            <Text className="text-[16px] font-bold text-emerald-950">{author.nombre || post.autor_nombre || 'Usuario'}</Text>
            <Text className="mt-0.5 text-[12px] font-medium text-slate-500">Miembro · {formatRelativeDate(post.created_at)}</Text>
          </View>
        </View>
        <View className="mb-4 self-start flex-row items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1" style={{ borderWidth: 1, borderColor: category.border }}>
          {category.icon}
          <Text className="text-[12px] font-bold uppercase tracking-wider" style={{ color: category.text }}>{post.categoria_nombre || 'General'}</Text>
        </View>
        <Text className="mb-4 text-[24px] font-black leading-7 text-emerald-950">{post.titulo}</Text>
        {post.aprobado === false ? <View className="mb-4 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3"><Text className="text-sm font-black text-amber-900">Pendiente de revisión</Text><Text className="mt-1 text-xs leading-4 text-amber-800">Solo tú puedes ver esta publicación hasta que sea aprobada.</Text></View> : null}
        <Text className="mb-6 text-[16px] leading-6 text-slate-700" style={{ textAlign: 'justify' }}>{htmlToPlainText(post.contenido)}</Text>
        {postImage ? <View className="mb-4 overflow-hidden rounded-[22px] bg-white"><RemoteImage uri={postImage} className="w-full" backgroundClassName="bg-white" loadingClassName="bg-white" aspectRatio={16 / 9} accessibilityLabel="Imagen de la publicación" /></View> : null}
        <View className="mt-2 flex-row flex-wrap items-center justify-between gap-2 border-t border-slate-100 pt-4">
          <LikeButton liked={Boolean(post.liked_by_me)} count={likesCount} pending={likePending} onPress={toggleLike} />
          <TouchableOpacity onPress={() => setCommentsModalVisible(true)} className="min-h-11 flex-row items-center gap-2 rounded-full bg-slate-50 px-3" accessibilityLabel={`Abrir ${commentsCount} comentarios`}>
            <MessageCircle color="#475569" size={19} /><Text className="text-sm font-bold text-slate-700">{commentsCount} Comentarios</Text>
          </TouchableOpacity>
          <TouchableOpacity onPress={sharePost} className="min-h-11 flex-row items-center gap-2 rounded-full bg-slate-50 px-3" accessibilityLabel="Compartir publicación"><Share2 color="#475569" size={18} /><Text className="text-sm font-bold text-slate-700">Compartir</Text></TouchableOpacity>
        </View>
      </View>
    </>
  );

  return (
    <SafeAreaView className="flex-1 bg-slate-50" edges={['top']}>
      <StatusBar style="dark" />
      <View className="z-20 flex-row items-center border-b border-slate-100 bg-white px-5 py-3">
        <TouchableOpacity onPress={() => navigation.goBack()} className="h-10 w-10 items-center justify-center rounded-full bg-slate-100" accessibilityLabel="Volver">
          <ArrowLeft color="#0F172A" size={20} />
        </TouchableOpacity>
        <View className="ml-4 flex-1"><Text className="text-[18px] font-black text-slate-950">Publicación</Text><Text className="text-xs font-semibold text-slate-500">Comunidad ZeroWaste</Text></View>
      </View>
      <ScrollView
          contentContainerStyle={{ paddingBottom: 40 }}
          keyboardShouldPersistTaps="handled"
          keyboardDismissMode="on-drag"
          showsVerticalScrollIndicator={false}
        >
        {postHeader}
      </ScrollView>
      <CommentsModal
        visible={commentsModalVisible}
        post={post}
        highlightCommentId={route.params?.highlightCommentId}
        onClose={() => {
          setCommentsModalVisible(false);
          navigation.setParams({ focusComments: false, highlightCommentId: undefined });
        }}
        onCountChange={(count) => setPost((current) => ({ ...current, total_respuestas: count, comments_count: count }))}
      />
    </SafeAreaView>
  );
}
