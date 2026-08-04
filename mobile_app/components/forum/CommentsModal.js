import React, { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { AccessibilityInfo, ActivityIndicator, Animated, FlatList, Keyboard, KeyboardAvoidingView, Modal, PanResponder, Platform, Pressable, Text, TextInput, TouchableOpacity, View } from 'react-native';
import { MessageCircle, Reply, Send, X } from 'lucide-react-native';
import { SafeAreaView, useSafeAreaInsets } from 'react-native-safe-area-context';

import { api } from '../../api/axios';
import { formatRelativeDate } from '../../utils/date';
import { htmlToPlainText } from '../../utils/text';
import { resolveAvatar, resolveDisplayName } from '../../utils/user';
import Skeleton from '../ui/Skeleton';
import UserAvatar from '../ui/UserAvatar';

export default function CommentsModal({ visible, post, highlightCommentId, onClose, onCountChange }) {
  const PAGE_SIZE = 50;
  const insets = useSafeAreaInsets();
  const listRef = useRef(null);
  const inputRef = useRef(null);
  const requestRef = useRef(0);
  const submittingRef = useRef(false);
  const keyboardVisibleRef = useRef(false);
  const commentsRef = useRef([]);
  const draftPostIdRef = useRef(null);
  const dragY = useRef(new Animated.Value(0)).current;
  const [comments, setComments] = useState([]);
  const [loading, setLoading] = useState(false);
  const [refreshing, setRefreshing] = useState(false);
  const [error, setError] = useState('');
  const [composerError, setComposerError] = useState('');
  const [draft, setDraft] = useState('');
  const [submitting, setSubmitting] = useState(false);
  const [replyingTo, setReplyingTo] = useState(null);
  const [hasMore, setHasMore] = useState(false);
  const [loadingMore, setLoadingMore] = useState(false);
  const [highlightedId, setHighlightedId] = useState(null);
  const [reduceMotion, setReduceMotion] = useState(false);
  const [keyboardVisible, setKeyboardVisible] = useState(false);
  const [inputHeight, setInputHeight] = useState(48);

  useEffect(() => {
    AccessibilityInfo.isReduceMotionEnabled().then(setReduceMotion);
    const subscription = AccessibilityInfo.addEventListener('reduceMotionChanged', setReduceMotion);
    return () => subscription.remove();
  }, []);

  useEffect(() => {
    if (!visible) return;
    dragY.setValue(0);
    keyboardVisibleRef.current = false;
    setKeyboardVisible(false);
  }, [dragY, visible]);

  useEffect(() => {
    const showEvent = Platform.OS === 'ios' ? 'keyboardWillShow' : 'keyboardDidShow';
    const hideEvent = Platform.OS === 'ios' ? 'keyboardWillHide' : 'keyboardDidHide';
    const showSubscription = Keyboard.addListener(showEvent, (event) => {
      if (Platform.OS === 'ios' && !reduceMotion && event) Keyboard.scheduleLayoutAnimation(event);
      if (keyboardVisibleRef.current) return;
      keyboardVisibleRef.current = true;
      setKeyboardVisible(true);
    });
    const hideSubscription = Keyboard.addListener(hideEvent, (event) => {
      if (Platform.OS === 'ios' && !reduceMotion && event) Keyboard.scheduleLayoutAnimation(event);
      if (!keyboardVisibleRef.current) return;
      keyboardVisibleRef.current = false;
      setKeyboardVisible(false);
      dragY.setValue(0);
    });
    return () => {
      showSubscription.remove();
      hideSubscription.remove();
    };
  }, [dragY, reduceMotion]);

  useEffect(() => {
    if (!visible || !post?.id || String(draftPostIdRef.current) === String(post.id)) return;
    draftPostIdRef.current = post.id;
    setDraft('');
    setReplyingTo(null);
    setComposerError('');
    setInputHeight(48);
  }, [post?.id, visible]);

  const closeModal = useCallback(() => {
    Keyboard.dismiss();
    keyboardVisibleRef.current = false;
    setKeyboardVisible(false);
    onClose();
  }, [onClose]);

  const dragResponder = useMemo(() => PanResponder.create({
    onMoveShouldSetPanResponder: (_event, gesture) => gesture.dy > 6 && Math.abs(gesture.dy) > Math.abs(gesture.dx),
    onPanResponderMove: (_event, gesture) => dragY.setValue(Math.max(0, gesture.dy)),
    onPanResponderRelease: (_event, gesture) => {
      if (gesture.dy > 85 || gesture.vy > 0.85) {
        closeModal();
        return;
      }
      if (reduceMotion) dragY.setValue(0);
      else Animated.spring(dragY, { toValue: 0, damping: 22, stiffness: 220, useNativeDriver: true }).start();
    },
    onPanResponderTerminate: () => dragY.setValue(0),
  }), [closeModal, dragY, reduceMotion]);

  const load = useCallback(async ({ refresh = false, more = false } = {}) => {
    if (!post?.id) return;
    const requestId = ++requestRef.current;
    if (refresh) setRefreshing(true); else if (more) setLoadingMore(true); else setLoading(true);
    setError('');
    try {
      const offset = more ? commentsRef.current.length : 0;
      const { data } = await api.get(`/foro/posts/${post.id}/respuestas`, { params: { limit: PAGE_SIZE, offset } });
      if (requestId !== requestRef.current) return;
      const rows = Array.isArray(data) ? data : [];
      setComments((current) => {
        const next = more ? [...current, ...rows.filter((item) => !current.some((existing) => existing.id === item.id))] : rows;
        commentsRef.current = next;
        return next;
      });
      setHasMore(rows.length === PAGE_SIZE);
    } catch (requestError) {
      if (requestId === requestRef.current) setError(requestError.userMessage || 'No fue posible cargar los comentarios.');
    } finally {
      if (requestId === requestRef.current) { setLoading(false); setRefreshing(false); setLoadingMore(false); }
    }
  }, [post?.id]);

  useEffect(() => {
    if (!visible || !post?.id) return;
    void load();
    return () => { requestRef.current += 1; };
  }, [load, post?.id, visible]);

  useEffect(() => {
    if (!highlightCommentId || !comments.length) return;
    const index = comments.findIndex((item) => String(item.id) === String(highlightCommentId));
    if (index < 0) {
      if (hasMore && !loadingMore) void load({ more: true });
      return;
    }
    setHighlightedId(highlightCommentId);
    const scrollTimer = setTimeout(() => listRef.current?.scrollToIndex({ index, animated: true, viewPosition: 0.45 }), 180);
    const highlightTimer = setTimeout(() => setHighlightedId(null), 2600);
    return () => { clearTimeout(scrollTimer); clearTimeout(highlightTimer); };
  }, [comments, hasMore, highlightCommentId, load, loadingMore]);

  const beginReply = (comment) => {
    setReplyingTo(comment);
    setComposerError('');
    setTimeout(() => inputRef.current?.focus(), 80);
  };

  const handleInputFocus = () => {
    setTimeout(() => listRef.current?.scrollToEnd({ animated: !reduceMotion }), 120);
  };

  const submit = async () => {
    const content = draft.trim();
    if (content.length < 11) { setComposerError('Escribe al menos 11 caracteres.'); return; }
    if (submittingRef.current) return;
    submittingRef.current = true;
    setSubmitting(true);
    setComposerError('');
    try {
      const { data } = await api.post(`/foro/posts/${post.id}/respuestas`, { contenido: content, parent_comment_id: replyingTo?.id || null });
      setComments((current) => {
        const next = [...current, data];
        commentsRef.current = next;
        return next;
      });
      setDraft('');
      setReplyingTo(null);
      setInputHeight(48);
      onCountChange?.(Math.max(Number(post.total_respuestas ?? post.comments_count) || 0, commentsRef.current.length));
      requestAnimationFrame(() => listRef.current?.scrollToEnd({ animated: true }));
    } catch (requestError) {
      setComposerError(requestError.userMessage || 'No se pudo enviar. Tu comentario se conservó.');
    } finally {
      submittingRef.current = false;
      setSubmitting(false);
    }
  };

  return (
    <Modal visible={visible} transparent animationType={reduceMotion ? 'none' : 'slide'} presentationStyle="overFullScreen" statusBarTranslucent navigationBarTranslucent={false} onRequestClose={closeModal}>
      <KeyboardAvoidingView behavior={Platform.OS === 'ios' ? 'padding' : undefined} keyboardVerticalOffset={0} style={{ flex: 1 }}>
        <View className="flex-1 justify-end bg-slate-950/45">
          <Pressable className="flex-1" onPress={closeModal} accessibilityLabel="Cerrar comentarios" />
          <Animated.View style={{ height: '92%', minHeight: '55%', transform: [{ translateY: dragY }] }}>
          <SafeAreaView className="flex-1 overflow-hidden rounded-t-[30px] bg-slate-50" edges={[]}>
            <View {...dragResponder.panHandlers} className="items-center bg-white pt-2"><TouchableOpacity onPress={closeModal} className="h-7 w-20 items-center justify-center" accessibilityLabel="Cerrar comentarios"><View className="h-1.5 w-12 rounded-full bg-slate-300" /></TouchableOpacity></View>
            <View className="flex-row items-center border-b border-slate-100 bg-white px-5 pb-4 pt-1"><View className="flex-1"><Text className="text-xl font-black text-slate-950">Comentarios</Text><Text className="mt-0.5 text-xs font-semibold text-slate-500" numberOfLines={1}>{post?.titulo || 'Publicación ZeroWaste'}</Text></View><TouchableOpacity onPress={closeModal} className="h-11 w-11 items-center justify-center rounded-full bg-slate-100" accessibilityLabel="Cerrar"><X color="#334155" size={20} /></TouchableOpacity></View>
            <View className="border-b border-slate-100 bg-white px-5 py-3"><View className="flex-row items-center"><UserAvatar uri={resolveAvatar(post)} name={resolveDisplayName(post)} size={32} /><Text className="ml-2 flex-1 text-xs font-black text-emerald-950" numberOfLines={1}>{resolveDisplayName(post)}</Text></View><Text className="mt-2 text-sm leading-5 text-slate-600" style={{ textAlign: 'justify' }} numberOfLines={2}>{htmlToPlainText(post?.contenido)}</Text></View>
            {error ? <TouchableOpacity onPress={() => load({ refresh: true })} className="mx-4 mt-3 rounded-2xl border border-red-200 bg-red-50 p-3"><Text className="text-center font-bold text-red-700">{error} Toca para reintentar.</Text></TouchableOpacity> : null}
            <FlatList
              ref={listRef}
              style={{ flex: 1 }}
              data={comments}
              keyExtractor={(item) => `comment:${item.id}`}
              contentContainerStyle={{ padding: 16, paddingBottom: 20 }}
              refreshing={refreshing}
              onRefresh={() => load({ refresh: true })}
              onEndReached={() => { if (hasMore && !loadingMore) void load({ more: true }); }}
              onEndReachedThreshold={0.35}
              keyboardShouldPersistTaps="handled"
              keyboardDismissMode="on-drag"
              onScrollToIndexFailed={({ index }) => setTimeout(() => listRef.current?.scrollToOffset({ offset: index * 100, animated: true }), 100)}
              ListEmptyComponent={loading ? <View>{[0, 1, 2].map((item) => <View key={item} className="mb-3 flex-row rounded-2xl bg-white p-3"><Skeleton className="h-10 w-10 rounded-full" /><View className="ml-3 flex-1"><Skeleton className="h-4 w-28 rounded" /><Skeleton className="mt-3 h-4 w-full rounded" /><Skeleton className="mt-2 h-4 w-2/3 rounded" /></View></View>)}</View> : !error ? <View className="items-center px-8 py-12"><MessageCircle color="#94A3B8" size={36} /><Text className="mt-3 font-black text-slate-700">Aún no hay comentarios</Text><Text className="mt-1 text-center text-sm text-slate-500">Inicia una conversación respetuosa.</Text></View> : null}
              ListFooterComponent={loadingMore ? <ActivityIndicator className="my-4" color="#047857" /> : null}
              renderItem={({ item }) => {
                const highlighted = String(item.id) === String(highlightedId);
                const parent = item.parent_comment_id ? comments.find((candidate) => candidate.id === item.parent_comment_id) : null;
                return <View className={`mb-3 rounded-2xl border p-3 ${highlighted ? 'border-emerald-400 bg-emerald-50' : 'border-slate-100 bg-white'}`}><View className="flex-row items-start"><UserAvatar uri={resolveAvatar(item)} name={resolveDisplayName(item)} size={38} /><View className="ml-3 flex-1"><View className="flex-row items-center"><Text className="flex-1 text-sm font-black text-slate-900" numberOfLines={1}>{resolveDisplayName(item)}</Text><Text className="text-[10px] font-semibold text-slate-400">{formatRelativeDate(item.created_at)}</Text></View>{parent ? <Text className="mt-1 text-xs font-bold text-emerald-700">En respuesta a {resolveDisplayName(parent)}</Text> : null}<Text className="mt-1 text-sm leading-5 text-slate-700" style={{ textAlign: 'justify' }}>{htmlToPlainText(item.contenido)}</Text><TouchableOpacity onPress={() => beginReply(item)} className="mt-2 min-h-8 flex-row items-center self-start" accessibilityLabel={`Responder a ${resolveDisplayName(item)}`}><Reply color="#64748B" size={14} /><Text className="ml-1.5 text-xs font-black text-slate-500">Responder</Text></TouchableOpacity></View></View></View>;
              }}
            />
            <View className="shrink-0 border-t border-slate-200 bg-white px-4 pt-3" style={{ paddingBottom: keyboardVisible ? 8 : Math.max(insets.bottom, 8), elevation: 12 }}>
              {replyingTo ? <View className="mb-2 flex-row items-center rounded-xl bg-emerald-50 px-3 py-2" accessibilityLiveRegion="polite"><Text className="flex-1 text-xs font-bold text-emerald-800" numberOfLines={1}>Respondiendo a {resolveDisplayName(replyingTo)}</Text><TouchableOpacity onPress={() => setReplyingTo(null)} className="h-8 w-8 items-center justify-center" accessibilityLabel="Cancelar respuesta"><X color="#047857" size={16} /></TouchableOpacity></View> : null}
              {composerError ? <Text className="mb-2 text-xs font-bold text-red-700" accessibilityLiveRegion="polite">{composerError}</Text> : null}
              <View className="mb-2 flex-row items-center justify-between"><Text className="text-xs font-black text-slate-700">{replyingTo ? 'Escribe tu respuesta' : 'Escribe un comentario'}</Text><Text className="text-[10px] font-bold text-slate-400">{draft.trim().length}/1000 · mín. 11</Text></View>
              <View className="flex-row items-end"><TextInput ref={inputRef} value={draft} onChangeText={(value) => { setDraft(value); if (composerError) setComposerError(''); }} onFocus={handleInputFocus} onContentSizeChange={({ nativeEvent }) => setInputHeight(Math.min(112, Math.max(48, nativeEvent.contentSize.height + 20)))} multiline blurOnSubmit={false} scrollEnabled={inputHeight >= 112} maxLength={1000} placeholder={replyingTo ? `Responde a ${resolveDisplayName(replyingTo)}…` : 'Escribe un comentario…'} placeholderTextColor="#94A3B8" className="flex-1 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-slate-900" style={{ height: inputHeight }} textAlignVertical="top" accessibilityLabel={replyingTo ? `Respuesta para ${resolveDisplayName(replyingTo)}` : 'Nuevo comentario'} /><TouchableOpacity disabled={submitting || draft.trim().length < 11} onPress={submit} className="ml-2 h-12 w-12 items-center justify-center rounded-full bg-emerald-700 disabled:opacity-40" accessibilityLabel={replyingTo ? 'Enviar respuesta' : 'Enviar comentario'}>{submitting ? <ActivityIndicator color="white" /> : <Send color="white" size={19} strokeWidth={2.5} />}</TouchableOpacity></View>
            </View>
          </SafeAreaView>
          </Animated.View>
        </View>
      </KeyboardAvoidingView>
    </Modal>
  );
}
