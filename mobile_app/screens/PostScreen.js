import React, { useCallback, useRef, useState } from 'react';
import {
  ActivityIndicator,
  FlatList,
  KeyboardAvoidingView,
  Platform,
  Text,
  TextInput,
  TouchableOpacity,
  View,
} from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import {
  Archive,
  ArrowLeft,
  Calendar,
  Folder,
  HelpCircle,
  Leaf,
  MessageCircle,
  Recycle,
  Send,
} from 'lucide-react-native';
import { useFocusEffect, useNavigation, useRoute } from '@react-navigation/native';
import { StatusBar } from 'expo-status-bar';
import { SafeAreaView, useSafeAreaInsets } from 'react-native-safe-area-context';

import { api } from '../api/axios';
import LikeButton from '../components/forum/LikeButton';
import RemoteImage from '../components/ui/RemoteImage';
import UserAvatar from '../components/ui/UserAvatar';
import { useAuth } from '../store/useAuth';
import { formatRelativeDate } from '../utils/date';
import { normalizeMediaUrl } from '../utils/media';


const stripHtml = (value) => {
  if (typeof value !== 'string') return '';
  return value.replace(/<[^>]*>/g, '').replace(/&nbsp;/g, ' ').trim();
};

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
  const insets = useSafeAreaInsets();
  const { user } = useAuth();
  const listRef = useRef(null);
  const likeRequestRef = useRef(false);
  const commentRequestRef = useRef(false);

  const [post, setPost] = useState(null);
  const [comments, setComments] = useState([]);
  const [loading, setLoading] = useState(true);
  const [loadError, setLoadError] = useState('');
  const [composerError, setComposerError] = useState('');
  const [actionError, setActionError] = useState('');
  const [replyText, setReplyText] = useState('');
  const [submitting, setSubmitting] = useState(false);
  const [likePending, setLikePending] = useState(false);

  const fetchPostDetail = useCallback(async () => {
    if (!postId) return;
    setLoading(true);
    setLoadError('');
    try {
      const [postResponse, commentsResponse] = await Promise.all([
        api.get(`/foro/posts/${postId}`),
        api.get(`/foro/posts/${postId}/respuestas`),
      ]);
      setPost(postResponse.data || null);
      setComments(Array.isArray(commentsResponse.data) ? commentsResponse.data : []);
    } catch (error) {
      setLoadError(error.userMessage || 'No se pudo cargar la publicación.');
    } finally {
      setLoading(false);
    }
  }, [postId]);

  useFocusEffect(useCallback(() => {
    void fetchPostDetail();
  }, [fetchPostDetail]));

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

  const submitReply = async () => {
    const content = replyText.trim();
    if (content.length <= 10) {
      setComposerError('Escribe al menos 11 caracteres.');
      return;
    }
    if (commentRequestRef.current) return;
    commentRequestRef.current = true;
    setSubmitting(true);
    setComposerError('');
    try {
      const { data } = await api.post(`/foro/posts/${postId}/respuestas`, { contenido: content });
      setComments((current) => [...current, data]);
      setPost((current) => {
        const count = Math.max(0, Number(current?.comments_count ?? current?.total_respuestas) || 0) + 1;
        return { ...current, comments_count: count, total_respuestas: count };
      });
      setReplyText('');
      requestAnimationFrame(() => listRef.current?.scrollToEnd({ animated: true }));
    } catch (error) {
      setComposerError(error.userMessage || 'No se pudo enviar la respuesta. Tu texto se conservó.');
    } finally {
      commentRequestRef.current = false;
      setSubmitting(false);
    }
  };

  if (loading && !post) {
    return <View className="flex-1 items-center justify-center bg-emerald-50"><ActivityIndicator size="large" color="#059669" /></View>;
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
  const postAvatar = author.avatar_url ?? post.avatar_url ?? post.autor_foto;
  const postImage = normalizeMediaUrl(post.image_url ?? post.imagen, 'foro');
  const likesCount = post.likes_count ?? post.total_likes ?? 0;
  const commentsCount = post.comments_count ?? post.total_respuestas ?? comments.length;

  const postHeader = (
    <>
      {loadError || actionError ? (
        <View className="mx-5 mt-3 rounded-xl border border-red-200 bg-red-50 p-3">
          <Text className="text-center font-bold text-red-700">{loadError || actionError}</Text>
        </View>
      ) : null}
      <View className="mt-4 border-y border-emerald-100 bg-white px-5 py-6">
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
        <Text className="mb-6 text-[16px] leading-6 text-slate-700">{stripHtml(post.contenido)}</Text>
        {postImage ? <RemoteImage uri={postImage} className="mb-4 w-full rounded-2xl" aspectRatio={16 / 9} accessibilityLabel="Imagen de la publicación" /> : null}
        <View className="mt-2 flex-row items-center gap-5 border-t border-slate-100 pt-3">
          <LikeButton liked={Boolean(post.liked_by_me)} count={likesCount} pending={likePending} onPress={toggleLike} />
          <View className="flex-row items-center gap-2"><MessageCircle color="#64748B" size={20} /><Text className="font-bold text-slate-600">{commentsCount}</Text></View>
        </View>
      </View>
      <Text className="px-5 pb-3 pt-6 text-[14px] font-black uppercase tracking-widest text-emerald-900">Comentarios</Text>
    </>
  );

  const renderComment = ({ item }) => {
    const commentAuthor = item.author || {};
    return (
      <View className="mx-5 mb-3 flex-row items-start gap-3">
        <UserAvatar uri={commentAuthor.avatar_url ?? item.avatar_url ?? item.autor_foto} name={commentAuthor.nombre || item.autor_nombre} size={38} />
        <View className={`min-w-0 flex-1 rounded-2xl px-4 py-3 ${item.contenido_invalido ? 'border border-amber-200 bg-amber-50' : 'bg-white'}`}>
          <View className="mb-1 flex-row items-center justify-between gap-3">
            <Text className="flex-1 text-[13px] font-bold text-emerald-950" numberOfLines={1}>{commentAuthor.nombre || item.autor_nombre || 'Usuario'}</Text>
            <Text className="text-[10px] font-medium text-slate-400">{formatRelativeDate(item.created_at)}</Text>
          </View>
          <Text className={`text-[14px] leading-5 ${item.contenido_invalido ? 'text-amber-800' : 'text-slate-700'}`}>{item.contenido}</Text>
        </View>
      </View>
    );
  };

  return (
    <SafeAreaView className="flex-1 bg-emerald-50" edges={['top']}>
      <StatusBar style="dark" />
      <LinearGradient colors={['#D1FAE5', '#ECFDF5']} className="absolute inset-0" />
      <View className="z-20 flex-row items-center border-b border-emerald-100 bg-emerald-50/95 px-5 py-3">
        <TouchableOpacity onPress={() => navigation.goBack()} className="h-10 w-10 items-center justify-center rounded-full bg-white" accessibilityLabel="Volver">
          <ArrowLeft color="#064E3B" size={20} />
        </TouchableOpacity>
        <Text className="ml-4 flex-1 text-[18px] font-black text-emerald-950">Discusión</Text>
      </View>
      <KeyboardAvoidingView behavior={Platform.OS === 'ios' ? 'padding' : 'height'} keyboardVerticalOffset={0} style={{ flex: 1 }}>
        <FlatList
          ref={listRef}
          data={comments}
          keyExtractor={(item, index) => String(item.id ?? index)}
          renderItem={renderComment}
          ListHeaderComponent={postHeader}
          ListEmptyComponent={<View className="items-center px-8 py-10"><MessageCircle color="#94A3B8" size={36} /><Text className="mt-3 text-center font-bold text-slate-500">Aún no hay respuestas</Text><Text className="mt-1 text-center text-xs text-slate-400">Sé la primera persona en unirte a la conversación.</Text></View>}
          contentContainerStyle={{ paddingBottom: 16 }}
          keyboardShouldPersistTaps="handled"
          keyboardDismissMode="on-drag"
          automaticallyAdjustKeyboardInsets={Platform.OS === 'ios'}
          showsVerticalScrollIndicator={false}
        />
        <View className="border-t border-emerald-100 bg-white px-4 pt-2" style={{ paddingBottom: Math.max(insets.bottom, 10) }}>
          {composerError ? <Text className="mb-2 px-2 text-xs font-bold text-red-600">{composerError}</Text> : null}
          <View className="flex-row items-end gap-2">
            <UserAvatar uri={user?.avatar_url ?? user?.foto_perfil} name={user?.nombre} size={36} />
            <TextInput
              value={replyText}
              onChangeText={(value) => { setReplyText(value); if (composerError) setComposerError(''); }}
              placeholder="Escribe un comentario..."
              placeholderTextColor="#94A3B8"
              multiline
              maxLength={1000}
              textAlignVertical="top"
              className="max-h-28 min-h-11 flex-1 rounded-2xl bg-slate-100 px-4 py-3 text-[15px] text-slate-900"
              accessibilityLabel="Comentario"
            />
            <TouchableOpacity
              onPress={submitReply}
              disabled={replyText.trim().length <= 10 || submitting}
              className={`h-11 w-11 items-center justify-center rounded-full ${replyText.trim().length > 10 && !submitting ? 'bg-emerald-600' : 'bg-slate-200'}`}
              accessibilityLabel="Enviar comentario"
              accessibilityState={{ disabled: replyText.trim().length <= 10 || submitting, busy: submitting }}
            >
              {submitting ? <ActivityIndicator size="small" color="#fff" /> : <Send color={replyText.trim().length > 10 ? '#fff' : '#94A3B8'} size={18} />}
            </TouchableOpacity>
          </View>
        </View>
      </KeyboardAvoidingView>
    </SafeAreaView>
  );
}
