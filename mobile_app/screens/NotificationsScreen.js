import React, { useCallback, useRef, useState } from 'react';
import { ActivityIndicator, FlatList, RefreshControl, Text, TouchableOpacity, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { ArrowLeft, Bell, CalendarClock, CheckCheck, Gift, Heart, Leaf, MapPin, MessageCircle, Navigation, Newspaper, PackageOpen, Recycle, Reply, RotateCcw, Truck } from 'lucide-react-native';
import { useFocusEffect, useNavigation } from '@react-navigation/native';

import { api } from '../api/axios';
import UserAvatar from '../components/ui/UserAvatar';
import Skeleton from '../components/ui/Skeleton';
import { notificationTarget } from '../services/mobileNotifications';
import { formatRelativeDate } from '../utils/date';

const PAGE_SIZE = 30;

const legacyNotificationData = (row) => {
  const url = String(row?.url || '');
  const payload = row?.payload && typeof row.payload === 'object' && !Array.isArray(row.payload) ? row.payload : {};
  const typedType = String(payload.type || row?.type || '');
  if (typedType) {
    return {
      ...payload,
      type: typedType,
      entityId: payload.entityId || row?.entity_id || undefined,
      route: payload.route || row?.route || undefined,
    };
  }
  const postMatch = url.match(/(?:zerowaste:\/\/posts\/|\/posts\/|\/foro\/)(\d+)/i);
  if (postMatch) return { type: 'post_comment', postId: postMatch[1], entityId: postMatch[1], openComments: true };
  const articleMatch = url.match(/(?:zerowaste:\/\/articles\/|\/articles\/)([a-z0-9-]+)/i);
  if (articleMatch) return { type: 'article_published', entityId: articleMatch[1] };
  const newsMatch = url.match(/(?:zerowaste:\/\/news\/|\/news\/)([a-z0-9-]+)/i);
  if (newsMatch) return { type: 'news_published', entityId: newsMatch[1] };
  const collectionMatch = url.match(/(?:zerowaste:\/\/collections\/|\/collections\/)(\d+)/i);
  if (collectionMatch) return { type: 'collection_status', entityId: collectionMatch[1] };
  return { type: 'system_notice' };
};

const normalizeLegacyNotification = (row) => {
  const data = legacyNotificationData(row);
  return {
    id: row.id,
    type: data.type,
    title: row.titulo || 'Notificación de ZeroWaste',
    body: row.mensaje || '',
    data,
    read: Boolean(row.leida),
    created_at: row.created_at,
  };
};

const dateGroup = (value) => {
  const date = new Date(value);
  const now = new Date();
  if (date.toDateString() === now.toDateString()) return 'Hoy';
  const yesterday = new Date(now); yesterday.setDate(now.getDate() - 1);
  if (date.toDateString() === yesterday.toDateString()) return 'Ayer';
  return date.toLocaleDateString('es-MX', { day: 'numeric', month: 'long' });
};

const iconFor = (type) => ({
  post_comment: MessageCircle,
  comment_reply: Reply,
  post_like: Heart,
  article_published: Leaf,
  news_published: Newspaper,
  reward_status: Gift,
  points_earned: Recycle,
  collection_created: Truck,
}[type] || Bell);

const collectionSchedule = (value) => {
  if (!value) return '';
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return '';
  return date.toLocaleString('es-MX', {
    timeZone: 'America/Mexico_City',
    weekday: 'short',
    day: 'numeric',
    month: 'short',
    hour: '2-digit',
    minute: '2-digit',
  });
};

export default function NotificationsScreen() {
  const navigation = useNavigation();
  const requestRef = useRef(0);
  const [rows, setRows] = useState([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [loadingMore, setLoadingMore] = useState(false);
  const [hasMore, setHasMore] = useState(false);
  const [error, setError] = useState('');
  const [legacyMode, setLegacyMode] = useState(false);

  const load = useCallback(async ({ refresh = false, more = false } = {}) => {
    const requestId = ++requestRef.current;
    if (refresh) setRefreshing(true); else if (more) setLoadingMore(true); else setLoading(true);
    setError('');
    const offset = more ? rows.length : 0;
    try {
      let data;
      let usingLegacy = legacyMode;
      if (usingLegacy) {
        const response = await api.get('/usuarios/me/notificaciones');
        data = { items: (Array.isArray(response.data) ? response.data : []).map(normalizeLegacyNotification), has_more: false };
      } else {
        try {
          const response = await api.get('/notifications', { params: { limit: PAGE_SIZE, offset } });
          data = response.data;
        } catch {
          const response = await api.get('/usuarios/me/notificaciones');
          data = { items: (Array.isArray(response.data) ? response.data : []).map(normalizeLegacyNotification), has_more: false };
          usingLegacy = true;
        }
      }
      if (requestId !== requestRef.current) return;
      const items = Array.isArray(data?.items) ? data.items : [];
      setRows((current) => more ? [...current, ...items.filter((item) => !current.some((existing) => existing.id === item.id))] : items);
      setHasMore(Boolean(data?.has_more));
      setLegacyMode(usingLegacy);
    } catch (requestError) {
      if (requestId === requestRef.current) setError(requestError.userMessage || 'No fue posible cargar tus notificaciones.');
    } finally {
      if (requestId === requestRef.current) { setLoading(false); setRefreshing(false); setLoadingMore(false); }
    }
  }, [legacyMode, rows.length]);

  useFocusEffect(useCallback(() => {
    void load();
    return () => { requestRef.current += 1; };
  }, []));

  const open = async (item) => {
    if (!item.read) {
      setRows((current) => current.map((row) => row.id === item.id ? { ...row, read: true } : row));
      const markRequest = legacyMode
        ? api.put(`/usuarios/me/notificaciones/${item.id}/leida`)
        : api.patch(`/notifications/${item.id}/read`).catch(() => api.put(`/usuarios/me/notificaciones/${item.id}/leida`));
      void markRequest.catch(() => setRows((current) => current.map((row) => row.id === item.id ? { ...row, read: false } : row)));
    }
    const target = notificationTarget(item.data);
    if (target) navigation.navigate(target.name, target.params);
  };

  const markAll = async () => {
    const previous = rows;
    setRows((current) => current.map((item) => ({ ...item, read: true })));
    try {
      if (legacyMode) {
        await Promise.all(previous.filter((item) => !item.read).map((item) => api.put(`/usuarios/me/notificaciones/${item.id}/leida`)));
      } else {
        await api.post('/notifications/read-all');
      }
    } catch { setRows(previous); }
  };

  const data = rows.flatMap((item, index) => {
    const group = dateGroup(item.created_at);
    const previousGroup = index ? dateGroup(rows[index - 1].created_at) : null;
    return previousGroup === group ? [item] : [{ id: `header:${group}`, header: group }, item];
  });

  return (
    <SafeAreaView className="flex-1 bg-slate-50" edges={['top', 'bottom']}>
      <View className="flex-row items-center border-b border-slate-100 bg-white px-5 py-4">
        <TouchableOpacity onPress={() => navigation.goBack()} className="h-11 w-11 items-center justify-center rounded-full bg-slate-100" accessibilityLabel="Volver"><ArrowLeft color="#111827" size={20} /></TouchableOpacity>
        <View className="ml-4 flex-1"><Text className="text-xl font-black text-slate-950">Notificaciones</Text><Text className="text-xs font-semibold text-slate-500">Actividad de tu cuenta</Text></View>
        {rows.some((item) => !item.read) ? <TouchableOpacity onPress={markAll} className="h-11 w-11 items-center justify-center rounded-full bg-emerald-50" accessibilityLabel="Marcar todas como leídas"><CheckCheck color="#047857" size={20} /></TouchableOpacity> : null}
      </View>
      {loading && !rows.length ? <View className="px-4 pt-5">{[0, 1, 2, 3].map((item) => <View key={item} className="mb-3 flex-row rounded-2xl bg-white p-4"><Skeleton className="h-11 w-11 rounded-full" /><View className="ml-3 flex-1"><Skeleton className="h-4 w-3/4 rounded" /><Skeleton className="mt-3 h-4 w-full rounded" /><Skeleton className="mt-3 h-3 w-20 rounded" /></View></View>)}</View> : (
        <FlatList
          data={data}
          keyExtractor={(item) => String(item.id)}
          refreshControl={<RefreshControl refreshing={refreshing} onRefresh={() => load({ refresh: true })} tintColor="#047857" />}
          contentContainerStyle={{ padding: 16, paddingBottom: 32 }}
          onEndReached={() => { if (hasMore && !loadingMore) void load({ more: true }); }}
          onEndReachedThreshold={0.35}
          ListEmptyComponent={!error ? <View className="items-center px-8 pt-20"><View className="h-20 w-20 items-center justify-center rounded-full bg-emerald-50"><Bell color="#059669" size={34} /></View><Text className="mt-5 text-lg font-black text-slate-900">Todo está al día</Text><Text className="mt-2 text-center leading-5 text-slate-500">Aquí aparecerán comentarios, respuestas, puntos y novedades relevantes.</Text></View> : null}
          ListHeaderComponent={error ? <TouchableOpacity onPress={() => load({ refresh: true })} className="mb-4 flex-row items-center rounded-2xl border border-red-200 bg-red-50 p-4"><RotateCcw color="#B91C1C" size={18} /><Text className="ml-3 flex-1 font-bold text-red-700">{error} Toca para reintentar.</Text></TouchableOpacity> : null}
          ListFooterComponent={loadingMore ? <ActivityIndicator className="my-5" color="#047857" /> : null}
          renderItem={({ item }) => {
            if (item.header) return <Text className="mb-2 mt-4 px-1 text-xs font-black uppercase tracking-widest text-slate-500">{item.header}</Text>;
            const Icon = iconFor(item.type);
            const isCollection = item.type === 'collection_created';
            const requesterName = String(item.data?.requesterName || item.data?.requester_name || '').trim();
            const destination = String(item.data?.address || item.data?.direccion || '').trim();
            const materials = String(item.data?.materials || item.data?.materiales || '').trim();
            const quantity = String(item.data?.quantity || item.data?.cantidad_estimada || '').trim();
            const schedule = collectionSchedule(item.data?.scheduledAt || item.data?.scheduled_at);
            const avatar = isCollection ? item.data?.requesterAvatarUrl || item.data?.requester_avatar_url : item.data?.actorAvatarUrl;
            const avatarName = isCollection ? requesterName : item.data?.actorName;
            const accessibilityLabel = isCollection
              ? `Nueva solicitud de ${requesterName || 'un usuario'}. Destino: ${destination || 'domicilio indicado'}. Abrir ruta.`
              : item.title;
            return <TouchableOpacity onPress={() => open(item)} className={`mb-2 flex-row items-start rounded-3xl border p-4 ${item.read ? 'border-slate-100 bg-white' : 'border-emerald-200 bg-emerald-50'}`} accessibilityState={{ selected: !item.read }} accessibilityLabel={accessibilityLabel}>
              {avatar ? <UserAvatar uri={avatar} name={avatarName} size={44} /> : <View className="h-11 w-11 items-center justify-center rounded-full bg-white"><Icon color="#047857" size={20} /></View>}
              <View className="ml-3 flex-1"><View className="flex-row items-start"><Text className="flex-1 font-black leading-5 text-slate-950">{isCollection ? 'Nueva solicitud de recolección' : item.title}</Text>{!item.read ? <View className="ml-2 mt-1 h-2.5 w-2.5 rounded-full bg-emerald-500" /> : null}</View>
                {isCollection ? <View className="mt-2">
                  <Text className="text-sm font-bold text-slate-800">Solicitó: <Text className="font-black text-emerald-800">{requesterName || 'Usuario ZeroWaste'}</Text></Text>
                  <View className="mt-2 flex-row items-start"><MapPin color="#047857" size={16} /><Text className="ml-2 flex-1 text-sm leading-5 text-slate-600">Destino: {destination || 'Domicilio indicado en la solicitud'}</Text></View>
                  {materials ? <View className="mt-1.5 flex-row items-start"><PackageOpen color="#64748B" size={16} /><Text className="ml-2 flex-1 text-xs leading-5 text-slate-500">{materials}{quantity ? ` · ${quantity}` : ''}</Text></View> : null}
                  {schedule ? <View className="mt-1.5 flex-row items-center"><CalendarClock color="#64748B" size={16} /><Text className="ml-2 text-xs font-semibold text-slate-500">{schedule}</Text></View> : null}
                  <View className="mt-3 flex-row items-center self-start rounded-full bg-emerald-700 px-3 py-2"><Navigation color="white" size={14} /><Text className="ml-1.5 text-xs font-black text-white">Ver ruta</Text></View>
                </View> : <Text className="mt-1 text-sm leading-5 text-slate-600" numberOfLines={2}>{item.body}</Text>}
                <Text className="mt-2 text-xs font-semibold text-slate-400">{formatRelativeDate(item.created_at)}</Text></View>
            </TouchableOpacity>;
          }}
        />
      )}
    </SafeAreaView>
  );
}
