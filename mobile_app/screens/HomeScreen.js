import React, { useCallback, useEffect, useRef, useState } from 'react';
import {
  View,
  Text,
  ScrollView,
  ImageBackground,
  Image,
  TouchableOpacity,
  Animated,
  Pressable,
  Easing,
  FlatList,
  Dimensions,
  Linking,
  ActivityIndicator,
  RefreshControl,
} from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import { StatusBar } from 'expo-status-bar';
import { SafeAreaView } from 'react-native-safe-area-context';
import { useNavigation } from '@react-navigation/native';
import { useScrollContext } from '../context/ScrollContext';
import { useAuth } from '../store/useAuth';
import { api } from '../api/axios';
import RemoteImage from '../components/ui/RemoteImage';
import UserAvatar from '../components/ui/UserAvatar';
import { motion } from '../theme/tokens';
import { normalizeMediaUrl } from '../utils/media';
import { resolveAvatar } from '../utils/user';
import Skeleton from '../components/ui/Skeleton';
import { EDITORIAL_IMAGES, MOBILE_ARTICLES, MOBILE_NEWS } from '../data/editorialContent';
import {
  Search,
  Users,
  TreePine,
  Globe,
  Calendar,
  MapPin,
  ArrowRight,
  ArrowLeft,
  ChevronRight,
  Heart,
  Bell,
  Zap,
  Award,
  TrendingUp,
  Trophy,

  Target,
  Clock,
  MessageCircle,
  Sparkles,
  BarChart3,
} from 'lucide-react-native';

const { width } = Dimensions.get('window');
const CAMPAIGN_CARD_SNAP = 316;

/* ─── ANIMATED PRIMITIVES ─────────────────────────────────────────── */
const TouchableScale = ({ children, style, onPress, scaleVal = 0.97 }) => {
  const scale = useRef(new Animated.Value(1)).current;
  const { reduceMotion } = useScrollContext();
  if (!onPress) return <View style={style}>{children}</View>;
  return (
    <Pressable
      onPressIn={() => { if (!reduceMotion) Animated.timing(scale, { toValue: scaleVal, duration: motion.press, useNativeDriver: true }).start(); }}
      onPressOut={() => { if (!reduceMotion) Animated.timing(scale, { toValue: 1, duration: motion.press, useNativeDriver: true }).start(); }}
      onPress={onPress}
    >
      <Animated.View style={[{ transform: [{ scale }] }, style]}>{children}</Animated.View>
    </Pressable>
  );
};

const AnimCounter = ({ target, suffix = '', prefix = '', color = '#fff' }) => {
  const [val, setVal] = useState(0);
  useEffect(() => {
    let frame;
    const dur = 1800;
    const start = Date.now();
    const tick = () => {
      const t = Math.min((Date.now() - start) / dur, 1);
      const ease = 1 - Math.pow(1 - t, 4);
      setVal(Math.round(ease * target));
      if (t < 1) frame = requestAnimationFrame(tick);
    };
    frame = requestAnimationFrame(tick);
    return () => cancelAnimationFrame(frame);
  }, [target]);
  return (
    <Text style={{ color, fontVariant: ['tabular-nums'], fontSize: 32, fontWeight: '900', letterSpacing: -1 }}>
      {prefix}{val.toLocaleString()}{suffix}
    </Text>
  );
};

/* ─── FADES ─────────────────────── */

/* ═══════════════════════════════════════════════════════════════════ */
export default function HomeScreen() {
  const navigation = useNavigation();
  const anims = Array.from({ length: 7 }, () => useRef(new Animated.Value(0)).current);
  const slides = Array.from({ length: 7 }, () => useRef(new Animated.Value(50)).current);
  const glow = useRef(new Animated.Value(0.4)).current;
  const ringPulse = useRef(new Animated.Value(1)).current;

  const tendListRef = useRef(null);
  const campaignListRef = useRef(null);
  const articlesRequestRef = useRef(0);
  const articlesAbortRef = useRef(null);
  const newsRequestRef = useRef(0);
  const newsAbortRef = useRef(null);
  const { user } = useAuth();
  const { handleScroll, reduceMotion } = useScrollContext();
  const [tendIndex, setTendIndex] = useState(0);
  const [campaignIndex, setCampaignIndex] = useState(0);
  const [articles, setArticles] = useState([]);
  const [articlesLoading, setArticlesLoading] = useState(true);
  const [articlesRefreshing, setArticlesRefreshing] = useState(false);
  const [articlesEmpty, setArticlesEmpty] = useState(false);
  const [articlesError, setArticlesError] = useState('');
  const [localNews, setLocalNews] = useState(null);
  const [newsLoading, setNewsLoading] = useState(true);
  const [newsError, setNewsError] = useState('');
  const [campaignsList, setCampaignsList] = useState([]);
  const [contentError, setContentError] = useState('');
  const [contentLoading, setContentLoading] = useState(true);
  const [unreadNotifications, setUnreadNotifications] = useState(0);
  const [impactSummary, setImpactSummary] = useState(null);
  const [impactLoading, setImpactLoading] = useState(true);
  const [impactError, setImpactError] = useState(false);
  const [reactionPending, setReactionPending] = useState({});
  const [reactionError, setReactionError] = useState('');
  const [homeRefreshKey, setHomeRefreshKey] = useState(0);
  const [homeRefreshing, setHomeRefreshing] = useState(false);

  const toggleReaction = useCallback(async (contentType, content) => {
    if (!content?.id || reactionPending[content.id]) return;
    const nextLiked = !content.liked_by_me;
    setReactionPending((current) => ({ ...current, [content.id]: true }));
    setReactionError('');
    try {
      const endpoint = `/${contentType === 'news' ? 'news' : 'articles'}/${encodeURIComponent(content.id)}/like`;
      const { data } = nextLiked ? await api.put(endpoint) : await api.delete(endpoint);
      const updated = { ...content, liked_by_me: Boolean(data?.liked), likes_count: Number(data?.likes_count) || 0 };
      if (contentType === 'news') setLocalNews(updated);
      else setArticles((rows) => rows.map((row) => row.id === content.id ? updated : row));
    } catch (requestError) {
      setReactionError(requestError.userMessage || 'No fue posible actualizar el corazón. Inténtalo nuevamente.');
    } finally {
      setReactionPending((current) => ({ ...current, [content.id]: false }));
    }
  }, [reactionPending]);

  const fetchArticles = useCallback(async ({ manualRefresh = false } = {}) => {
    const requestId = ++articlesRequestRef.current;
    articlesAbortRef.current?.abort();
    const controller = new AbortController();
    articlesAbortRef.current = controller;
    if (manualRefresh) setArticlesRefreshing(true);
    else setArticlesLoading(true);
    setArticlesError('');
    let lastError;
    for (let attempt = 0; attempt < 2; attempt += 1) {
      try {
        if (attempt) await new Promise((resolve) => setTimeout(resolve, 650));
        const { data } = await api.get('/articles', { signal: controller.signal });
        if (requestId !== articlesRequestRef.current) return;
        const rows = Array.isArray(data) ? data.filter((article) => article?.id && article?.title) : [];
        const available = rows.filter((article) => article.id !== 'queretaro-recicla');
        const resolvedArticles = available.length ? available : MOBILE_ARTICLES;
        setArticles(resolvedArticles);
        setArticlesEmpty(resolvedArticles.length === 0);
        setArticlesError('');
        lastError = null;
        break;
      } catch (requestError) {
        if (controller.signal.aborted || requestId !== articlesRequestRef.current) return;
        lastError = requestError;
      }
    }
    if (requestId === articlesRequestRef.current) {
      if (lastError) {
        setArticles(MOBILE_ARTICLES);
        setArticlesEmpty(MOBILE_ARTICLES.length === 0);
        setArticlesError('');
      }
      setArticlesLoading(false);
      setArticlesRefreshing(false);
    }
  }, []);

  const fetchNews = useCallback(async () => {
    const requestId = ++newsRequestRef.current;
    newsAbortRef.current?.abort();
    const controller = new AbortController();
    newsAbortRef.current = controller;
    setNewsLoading(true);
    setNewsError('');
    try {
      const { data } = await api.get('/news', { signal: controller.signal });
      if (requestId !== newsRequestRef.current) return;
      const first = Array.isArray(data) ? data.find((item) => item?.id && item?.title) : null;
      setLocalNews(first || MOBILE_NEWS[0] || null);
    } catch (requestError) {
      if (controller.signal.aborted || requestId !== newsRequestRef.current) return;
      setLocalNews(MOBILE_NEWS[0] || null);
      setNewsError('');
    } finally {
      if (requestId === newsRequestRef.current) setNewsLoading(false);
    }
  }, []);

  useEffect(() => {
    void fetchArticles();
    void fetchNews();
    api.get('/usuarios/me/notificaciones/no-leidas').then(({ data }) => setUnreadNotifications(Number(data?.total) || 0)).catch(() => setUnreadNotifications(0));
    api.get('/impacto/me')
      .then(({ data }) => {
        setImpactSummary(data);
        setImpactError(false);
      })
      .catch(() => {
        setImpactSummary(null);
        setImpactError(true);
      })
      .finally(() => setImpactLoading(false));
    const fetchActiveCampaignsAndEvents = async () => {
      setContentLoading(true);
      setContentError('');
      try {
        const [campRes, evRes] = await Promise.all([
          api.get('/campanas'),
          api.get('/eventos')
        ]);
        if (campRes.data !== null || evRes.data !== null) {
          const dbCampaigns = (campRes.data || []).map(c => ({
            id: `camp-${c.id}`,
            title: c.nombre || 'Campaña Eco',
            summary: c.descripcion || 'Participa en una iniciativa ambiental activa en Querétaro.',
            date: c.fecha_inicio ? new Date(c.fecha_inicio).toLocaleDateString('es-ES', { day: '2-digit', month: 'short' }) : 'Próximamente',
            location: c.lugar || 'Querétaro',
            tag: (c.tipo_etiqueta || 'CAMPAÑA').toUpperCase(),
            imageUrl: normalizeMediaUrl(c.cover_url ?? c.image_url ?? c.imagen_url ?? c.imagen, 'campanas'),
            fallbackImage: require('../assets/images/event1.png'),
            link: c.link_evento || null
          }));
          const dbEvents = (evRes.data || []).map(e => ({
            id: `ev-${e.id}`,
            title: e.titulo || 'Evento Eco',
            summary: e.descripcion || 'Súmate a una actividad ambiental de la comunidad.',
            date: e.fecha_inicio ? new Date(e.fecha_inicio).toLocaleDateString('es-ES', { day: '2-digit', month: 'short' }) : 'Próximamente',
            location: e.lugar || 'Querétaro',
            tag: (e.tipo_etiqueta || 'EVENTO').toUpperCase(),
            imageUrl: normalizeMediaUrl(e.cover_url ?? e.image_url ?? e.imagen_url ?? e.imagen, 'eventos'),
            fallbackImage: require('../assets/images/event2.jpg'),
            link: e.link_evento || null
          }));
          setCampaignsList([...dbCampaigns, ...dbEvents]);
        }
      } catch (e) {
        setContentError(e.userMessage || 'No se pudieron actualizar las campañas y eventos.');
      } finally {
        setContentLoading(false);
        setHomeRefreshing(false);
      }
    };
    fetchActiveCampaignsAndEvents();
    return () => {
      articlesRequestRef.current += 1;
      articlesAbortRef.current?.abort();
      newsRequestRef.current += 1;
      newsAbortRef.current?.abort();
    };
  }, [fetchArticles, fetchNews, homeRefreshKey]);

  useEffect(() => {
    if (reduceMotion) {
      anims.forEach((value) => value.setValue(1));
      slides.forEach((value) => value.setValue(0));
      glow.setValue(1);
      ringPulse.setValue(1);
      return undefined;
    }
    const entranceAnimation = Animated.stagger(45,
      anims.map((a, i) =>
        Animated.parallel([
          Animated.timing(a, { toValue: 1, duration: motion.navigation, easing: Easing.out(Easing.cubic), useNativeDriver: true }),
          Animated.timing(slides[i], { toValue: 0, duration: motion.navigation, easing: Easing.out(Easing.cubic), useNativeDriver: true }),
        ])
      )
    );
    entranceAnimation.start();

    const glowAnimation = Animated.loop(Animated.sequence([
      Animated.timing(glow, { toValue: 1, duration: 1800, easing: Easing.inOut(Easing.ease), useNativeDriver: true }),
      Animated.timing(glow, { toValue: 0.4, duration: 1800, easing: Easing.inOut(Easing.ease), useNativeDriver: true }),
    ]));
    glowAnimation.start();

    const ringAnimation = Animated.loop(Animated.sequence([
      Animated.timing(ringPulse, { toValue: 1.08, duration: 2000, easing: Easing.inOut(Easing.ease), useNativeDriver: true }),
      Animated.timing(ringPulse, { toValue: 1, duration: 2000, easing: Easing.inOut(Easing.ease), useNativeDriver: true }),
    ]));
    ringAnimation.start();

    return () => {
      entranceAnimation.stop();
      glowAnimation.stop();
      ringAnimation.stop();
    };
  }, [reduceMotion]);

  useEffect(() => {
    if (articles.length < 2) return undefined;
    const tendTimer = setInterval(() => {
      setTendIndex(prev => {
        const next = (prev + 1) % articles.length;
        tendListRef.current?.scrollToIndex({ index: next, animated: true });
        return next;
      });
    }, 5000);
    return () => clearInterval(tendTimer);
  }, [articles.length]);

  useEffect(() => {
    setCampaignIndex(0);
    campaignListRef.current?.scrollTo({ x: 0, animated: false });
    if (campaignsList.length < 2 || reduceMotion) return undefined;
    const campaignTimer = setInterval(() => {
      setCampaignIndex((current) => {
        const next = (current + 1) % campaignsList.length;
        campaignListRef.current?.scrollTo({ x: next * CAMPAIGN_CARD_SNAP, animated: true });
        return next;
      });
    }, 5000);
    return () => clearInterval(campaignTimer);
  }, [campaignsList.length, reduceMotion]);

  const anim = (i) => ({ opacity: anims[i], transform: [{ translateY: slides[i] }] });

  const goTendencia = (dir) => {
    if (!articles.length) return;
    const next = (tendIndex + dir + articles.length) % articles.length;
    setTendIndex(next);
    tendListRef.current?.scrollToIndex({ index: next, animated: true });
  };

  const avatarUrl = resolveAvatar(user);

  return (
    <SafeAreaView className="flex-1 bg-white" edges={['top']}>
      <StatusBar style="dark" />

      <ScrollView 
        showsVerticalScrollIndicator={false} 
        contentContainerStyle={{ paddingTop: 16, paddingBottom: 130 }}
        onScroll={handleScroll}
        refreshControl={<RefreshControl refreshing={homeRefreshing} onRefresh={() => { setHomeRefreshing(true); setHomeRefreshKey((value) => value + 1); }} tintColor="#047857" colors={['#047857']} progressBackgroundColor="#FFFFFF" />}
        scrollEventThrottle={16}
      >
        {/* ── HEADER (Logo only, no location) ──────────────── */}
        <View className="px-5 mb-6 flex-row items-center justify-between">
          <View className="flex-row items-center gap-3">
            <Image source={require('../assets/images/logo.png')} className="w-9 h-9" resizeMode="contain" />
            <Text className="text-[20px] font-black text-gray-900 tracking-tight">Zero Waste</Text>
          </View>
          <View className="flex-row items-center gap-2">
            <TouchableScale scaleVal={0.9} onPress={() => navigation.navigate('Search')}>
              <View className="w-10 h-10 rounded-full bg-white items-center justify-center border border-gray-100 shadow-sm">
                <Search color="#374151" size={18} strokeWidth={2.5} />
              </View>
            </TouchableScale>
            <TouchableScale scaleVal={0.9} onPress={() => navigation.navigate('Notifications')}>
              <View className="w-10 h-10 rounded-full bg-white items-center justify-center border border-gray-100 shadow-sm">
                <Bell color="#374151" size={18} strokeWidth={2.5} />
                {unreadNotifications > 0 ? <View className="absolute -top-0.5 -right-0.5 min-w-4 h-4 rounded-full bg-red-500 items-center justify-center border border-white px-1"><Text className="text-white text-[8px] font-black">{unreadNotifications > 99 ? '99+' : unreadNotifications}</Text></View> : null}
              </View>
            </TouchableScale>
            <TouchableScale scaleVal={0.9} onPress={() => navigation.navigate('Profile')}>
              <UserAvatar uri={avatarUrl} name={user?.nombre} size={40} accessibilityLabel="Avatar del usuario" />
            </TouchableScale>
          </View>
        </View>

        {/* ═══ 1. IMPACT DASHBOARD (Premium Interactive) ═══════ */}
        <Animated.View style={anim(0)} className="px-5 mb-8 pt-2">
          <TouchableScale scaleVal={0.98} onPress={() => navigation.navigate('ImpactStats')}>
            <View
              className="rounded-[28px] overflow-hidden"
              style={{ shadowColor: '#064E3B', shadowOffset: { width: 0, height: 20 }, shadowOpacity: 0.25, shadowRadius: 30, elevation: 18 }}
            >
              {/* Dark base with emerald accent layers */}
              <View className="bg-[#0A0F1A] p-6">
                {/* Decorative orbs */}
                <View className="absolute -top-16 -right-16 w-48 h-48 rounded-full" style={{ backgroundColor: 'rgba(16,185,129,0.08)' }} />
                <View className="absolute -bottom-10 -left-10 w-32 h-32 rounded-full" style={{ backgroundColor: 'rgba(34,211,238,0.06)' }} />

                <View className="flex-row items-center gap-3 mb-7">
                  <Animated.View style={{ transform: [{ scale: ringPulse }] }}>
                    <View className="w-14 h-14 rounded-[20px] items-center justify-center border-2 border-emerald-500/30" style={{ backgroundColor: 'rgba(16,185,129,0.12)' }}>
                      <Trophy color="#34D399" size={24} />
                    </View>
                  </Animated.View>
                  <View>
                    <Text className="text-emerald-400 text-[11px] font-black uppercase tracking-[0.15em]">Tu impacto</Text>
                    <Text className="text-white text-[18px] font-black tracking-tight mt-0.5">
                      {impactLoading ? 'Consultando tus estadísticas…' : impactSummary ? `Posición #${impactSummary.posicion ?? '—'} · Nivel ${impactSummary.nivel ?? 'Inicial'}` : 'Consulta tu ranking de impacto'}
                    </Text>
                  </View>
                </View>

                <View className="bg-white/5 rounded-2xl p-4 border border-white/5">
                  {impactSummary ? <View className="flex-row justify-between">
                    <View><Text className="text-[11px] font-bold uppercase text-gray-400">Impacto histórico</Text><Text className="mt-1 text-2xl font-black text-white">{Number(impactSummary.impacto_historico || 0).toLocaleString('es-MX')}</Text></View>
                    <View><Text className="text-[11px] font-bold uppercase text-gray-400">Puntos disponibles</Text><Text className="mt-1 text-2xl font-black text-emerald-400">{Number(impactSummary.puntos_disponibles || 0).toLocaleString('es-MX')}</Text></View>
                  </View> : <Text className="text-gray-300 text-[13px] font-medium leading-5">{impactError ? 'No fue posible consultar el impacto. Toca la tarjeta para reintentar desde el ranking.' : 'Cargando datos verificados del servidor.'}</Text>}
                </View>
              </View>
            </View>
          </TouchableScale>
        </Animated.View>

        {/* ═══ 2. TENDENCIAS (Reference image style: dark card, image top, bold text, nav arrows) ═══ */}
        <Animated.View style={anim(1)} className="mb-10 bg-white">
          <View className="px-5 mb-4 flex-row items-center justify-between">
            <View>
              <Text className="text-[26px] font-black text-gray-900 tracking-tight">Tendencias</Text>
              <Text className="text-[12px] text-gray-400 font-bold mt-0.5">Artículos destacados</Text>
            </View>
            <View className="flex-row gap-1.5">
              <TouchableOpacity onPress={() => goTendencia(-1)} disabled={articles.length < 2} className="mr-2 h-9 w-9 items-center justify-center rounded-full bg-white" accessibilityLabel="Tendencia anterior"><ArrowLeft color="#047857" size={17} /></TouchableOpacity>
              <TouchableOpacity onPress={() => goTendencia(1)} disabled={articles.length < 2} className="h-9 w-9 items-center justify-center rounded-full bg-emerald-700" accessibilityLabel="Siguiente tendencia"><ArrowRight color="white" size={17} /></TouchableOpacity>
            </View>
            <View className="ml-3 flex-row gap-1.5">
              {articles.map((_, idx) => (
                <View key={idx} className={`h-1.5 rounded-full ${idx === tendIndex ? 'w-5 bg-emerald-600' : 'w-1.5 bg-gray-200'}`} />
              ))}
            </View>
          </View>
          
          {articlesLoading && articles.length === 0 ? <View className="mx-5 overflow-hidden rounded-[30px] bg-white"><Skeleton style={{ aspectRatio: 16 / 10 }} /><View className="p-5"><Skeleton className="h-5 w-28 rounded-full" /><Skeleton className="mt-4 h-7 w-full rounded-full" /><Skeleton className="mt-3 h-4 w-4/5 rounded-full" /><Skeleton className="mt-5 h-12 w-full rounded-2xl" /></View></View> : articles.length === 0 && articlesError ? (
            <View className="mx-5 items-center rounded-3xl border border-red-100 bg-red-50 p-6">
              <Text className="text-center font-bold text-red-700">{articlesError}</Text>
              <TouchableOpacity onPress={() => fetchArticles({ manualRefresh: true })} className="mt-4 rounded-full bg-emerald-700 px-6 py-3"><Text className="font-black text-white">Reintentar</Text></TouchableOpacity>
            </View>
          ) : articles.length === 0 && articlesEmpty ? (
            <View className="mx-5 items-center rounded-3xl border border-slate-100 bg-white p-6"><Text className="text-center font-bold text-slate-600">No hay artículos publicados por el momento.</Text><TouchableOpacity onPress={() => fetchArticles({ manualRefresh: true })} className="mt-4 rounded-full border border-emerald-700 px-6 py-3"><Text className="font-black text-emerald-800">Actualizar</Text></TouchableOpacity></View>
          ) : <><View className="bg-white"><FlatList
            ref={tendListRef}
            data={articles}
            horizontal
            pagingEnabled
            bounces={false}
            overScrollMode="never"
            removeClippedSubviews={false}
            style={{ backgroundColor: '#FFFFFF' }}
            contentContainerStyle={{ backgroundColor: '#FFFFFF' }}
            showsHorizontalScrollIndicator={false}
            keyExtractor={item => String(item.id)}
            onScrollToIndexFailed={() => {}}
            onMomentumScrollEnd={(event) => setTendIndex(Math.round(event.nativeEvent.contentOffset.x / width))}
            renderItem={({ item }) => (
              <View style={{ width: width, paddingHorizontal: 20 }}>
                <TouchableScale scaleVal={0.98} onPress={() => navigation.navigate('ArticleDetail', { articleId: item.id })}>
                  <View className="mb-2 overflow-hidden rounded-[32px] bg-[#111827]">
                    {/* Image Section */}
                    <View className="relative bg-white" style={{ aspectRatio: 16 / 10 }}>
                      <RemoteImage uri={normalizeMediaUrl(item.image_url)} fallbackSource={EDITORIAL_IMAGES[item.id]} className="h-full w-full" backgroundClassName="bg-white" loadingClassName="bg-white" aspectRatio={16 / 10} accessibilityLabel={`Imagen de ${item.title}`} />
                      
                      {/* Dark fade at bottom — smooth blend */}
                      <LinearGradient
                        colors={[
                          'transparent',
                          'rgba(17,24,39,0.3)',
                          'rgba(17,24,39,0.8)',
                          'rgba(17,24,39,1)',
                        ]}
                        locations={[0, 0.4, 0.7, 1]}
                        style={{ position: 'absolute', bottom: -1, left: 0, right: 0, height: 120 }}
                        pointerEvents="none"
                      />
                      
                      {/* Floating Category Chip Top Left */}
                      <View className="absolute top-4 left-4 bg-emerald-500/90 border border-emerald-300/50 px-3.5 py-1.5 rounded-full shadow-sm z-10 flex-row items-center gap-1.5">
                        <View className="w-2 h-2 rounded-full bg-white" />
                        <Text className="shrink text-[10px] font-black uppercase tracking-wider text-white" numberOfLines={1} ellipsizeMode="tail">{item.category}</Text>
                      </View>

                    </View>

                    {/* Content Section */}
                    <View className="relative z-10 px-5 pb-5 pt-4">

                      <Text className="mb-2 text-[22px] font-black leading-[27px] tracking-tight text-white" numberOfLines={3} ellipsizeMode="tail">
                        {item.title}
                      </Text>

                      <Text className="mb-4 pr-2 text-[13px] font-medium leading-5 text-gray-300" style={{ textAlign: 'justify' }} numberOfLines={3} ellipsizeMode="tail">
                        {item.excerpt}
                      </Text>

                      {/* Action Row */}
                      <View className="flex-row items-center justify-between border-t border-white/10 pt-4">
                        <View className="min-h-11 flex-row items-center gap-2.5 rounded-2xl bg-emerald-500 px-5 py-3">
                          <Text className="text-white font-black text-[14px]">Leer artículo</Text>
                          <ArrowRight color="#fff" size={16} strokeWidth={3} />
                        </View>
                        <TouchableOpacity
                          onPress={(event) => { event.stopPropagation?.(); void toggleReaction('article', item); }}
                          disabled={reactionPending[item.id]}
                          className={`min-h-11 min-w-11 flex-row items-center justify-center gap-1.5 rounded-full px-3 ${item.liked_by_me ? 'bg-rose-500' : 'bg-white/10'}`}
                          accessibilityRole="button"
                          accessibilityLabel={item.liked_by_me ? 'Quitar corazón' : 'Dar corazón'}
                          accessibilityState={{ selected: Boolean(item.liked_by_me), busy: Boolean(reactionPending[item.id]) }}
                        >
                          {reactionPending[item.id] ? <ActivityIndicator color="#fff" size="small" /> : <Heart color="#fff" fill={item.liked_by_me ? '#fff' : 'transparent'} size={18} />}
                          <Text className="text-xs font-black text-white">{Number(item.likes_count) || 0}</Text>
                        </TouchableOpacity>
                      </View>
                    </View>
                  </View>
                </TouchableScale>
              </View>
            )}
          /></View>
          {articlesRefreshing ? <Text className="mt-2 text-center text-xs font-bold text-emerald-700">Actualizando tendencias…</Text> : null}
          {reactionError ? <Text className="mx-5 mt-2 text-center text-xs font-bold text-red-600">{reactionError}</Text> : null}
          {articlesError ? <View className="mx-5 mt-3 flex-row items-center justify-between rounded-2xl border border-amber-200 bg-amber-50 p-3"><Text className="mr-3 flex-1 text-xs font-bold text-amber-900">No se pudo actualizar; se conservan los artículos visibles.</Text><TouchableOpacity onPress={() => fetchArticles({ manualRefresh: true })}><Text className="font-black text-amber-900">Reintentar</Text></TouchableOpacity></View> : null}
          </>}
        </Animated.View>

        {/* ═══ 5. CAMPAÑAS ACTIVAS ════════════════════════════ */}
        <Animated.View style={anim(4)} className="mb-10 bg-white">
          <View className="mb-5 items-center px-5">
            <Text className="text-center text-[24px] font-black tracking-tight text-gray-900">Campañas <Text className="text-emerald-600">Activas</Text></Text>
            <Text className="mt-1 text-center text-xs font-semibold text-slate-400">Iniciativas y eventos disponibles en Querétaro</Text>
            {contentLoading && campaignsList.length ? <View className="mt-3 flex-row items-center rounded-full bg-emerald-50 px-3 py-2"><ActivityIndicator color="#047857" size="small" /><Text className="ml-2 text-xs font-black text-emerald-800">Actualizando contenido</Text></View> : null}
            {!contentLoading && contentError && campaignsList.length ? <Text className="mt-3 text-center text-xs font-bold text-amber-700">No se pudo actualizar; conservamos el contenido disponible.</Text> : null}
          </View>

          {contentLoading && campaignsList.length === 0 ? (
            <View className="mx-5 overflow-hidden rounded-[28px] border border-slate-100 bg-white"><Skeleton className="h-[190px] w-full" /><View className="p-5"><Skeleton className="h-5 w-3/4 rounded-full" /><Skeleton className="mt-3 h-4 w-full rounded-full" /><Skeleton className="mt-2 h-4 w-2/3 rounded-full" /></View></View>
          ) : contentError && campaignsList.length === 0 ? (
            <View className="mx-5 rounded-2xl border border-red-200 bg-red-50 p-4"><Text className="text-red-700 text-center font-bold">{contentError}</Text></View>
          ) : campaignsList.length === 0 ? (
            <View className="mx-5 items-center justify-center rounded-[28px] border border-slate-100 bg-white px-5 py-8">
              <Calendar color="#9CA3AF" size={32} />
              <Text className="text-gray-500 font-bold text-center mt-2 text-base">No hay campañas o eventos activos</Text>
              <Text className="text-gray-400 text-xs text-center mt-1">Próximamente publicaremos nuevas iniciativas eco-amigables.</Text>
            </View>
          ) : (
            <View className="bg-white"><ScrollView ref={campaignListRef} horizontal bounces={false} overScrollMode="never" showsHorizontalScrollIndicator={false} snapToInterval={CAMPAIGN_CARD_SNAP} decelerationRate="fast" onMomentumScrollEnd={(event) => setCampaignIndex(Math.max(0, Math.min(campaignsList.length - 1, Math.round(event.nativeEvent.contentOffset.x / CAMPAIGN_CARD_SNAP))))} style={{ backgroundColor: '#FFFFFF' }} contentContainerStyle={{ paddingHorizontal: 20, gap: 16, backgroundColor: '#FFFFFF' }}>
              {campaignsList.map((camp) => (
                <TouchableScale key={camp.id} onPress={camp.link ? () => Linking.openURL(camp.link) : undefined}>
                  <View className="h-[438px] w-[300px] overflow-hidden rounded-[28px] border border-slate-100 bg-white">
                    <View className="relative h-[190px]">
                      <RemoteImage uri={camp.imageUrl} fallbackSource={camp.fallbackImage} className="h-full w-full" backgroundClassName="bg-white" loadingClassName="bg-white" accessibilityLabel={`Imagen de ${camp.title}`} />
                      
                      <View className="absolute top-4 left-4 bg-black/60 px-3 py-1.5 rounded-full border border-white/10">
                        <Text className="text-white text-[10px] font-black uppercase tracking-[0.1em]">{camp.tag}</Text>
                      </View>

                      <View className="absolute bottom-0 w-full bg-black/50 px-4 py-2.5 flex-row justify-between items-center">
                        <View className="mr-3 flex-row items-center gap-1.5">
                          <Calendar color="#fff" size={13} />
                          <Text className="text-white text-[12px] font-bold">{camp.date}</Text>
                        </View>
                        <View className="min-w-0 flex-1 flex-row items-center justify-end gap-1.5">
                          <MapPin color="#fff" size={13} />
                          <Text className="shrink text-white text-[12px] font-bold" numberOfLines={1}>{camp.location}</Text>
                        </View>
                      </View>
                    </View>

                    <View className="flex-1 justify-between p-5 pt-4">
                      <View>
                        <Text className="mb-2 min-h-[44px] text-[18px] font-extrabold leading-[22px] text-gray-900" numberOfLines={2}>{camp.title}</Text>
                        <Text className="min-h-[60px] text-[13px] font-medium leading-5 text-slate-500" style={{ textAlign: 'justify' }} numberOfLines={3}>{camp.summary}</Text>
                      </View>
                      
                      {camp.link ? <TouchableScale onPress={() => Linking.openURL(camp.link)}>
                        <View className="bg-[#064E3B] rounded-2xl py-3.5 items-center flex-row justify-center gap-2">
                          <Text className="text-white text-[15px] font-black">Unirse</Text>
                          <ArrowRight color="#fff" size={16} strokeWidth={3} />
                        </View>
                      </TouchableScale> : (
                        <View className="items-center justify-center rounded-2xl border border-slate-200 bg-slate-50 py-3.5">
                          <Text className="text-[13px] font-extrabold text-slate-500">Información próximamente</Text>
                        </View>
                      )}
                    </View>
                  </View>
                </TouchableScale>
              ))}
            </ScrollView><View className="mt-4 flex-row justify-center gap-1.5">{campaignsList.map((camp, index) => <View key={`campaign-dot:${camp.id}`} className={`h-1.5 rounded-full ${index === campaignIndex ? 'w-5 bg-emerald-600' : 'w-1.5 bg-slate-200'}`} />)}</View></View>
          )}
        </Animated.View>

        {/* ═══ 6. NOTICIA LOCAL (Premium with emerald gradient overlay) ═══ */}
        <Animated.View style={anim(5)} className="px-5 mb-10">
          <View className="mb-4">
            <Text className="text-[24px] font-black text-gray-900 tracking-tight">Noticia <Text className="text-emerald-600">Local</Text></Text>
          </View>
          {newsLoading && !localNews ? (
            <Skeleton className="h-[390px] rounded-[28px]" />
          ) : newsError && !localNews ? (
            <View className="rounded-[28px] border border-red-100 bg-white p-6">
              <Text className="font-bold leading-6 text-red-700">{newsError}</Text>
              <TouchableOpacity onPress={fetchNews} className="mt-4 min-h-11 items-center justify-center rounded-xl bg-emerald-700"><Text className="font-black text-white">Reintentar noticias</Text></TouchableOpacity>
            </View>
          ) : localNews ? (
            <TouchableScale scaleVal={0.98} onPress={() => navigation.navigate('NewsDetail', { articleId: localNews.id })}>
              <View className="overflow-hidden rounded-[28px] bg-[#111827]" style={{ shadowColor: '#064E3B', shadowOffset: { width: 0, height: 16 }, shadowOpacity: 0.15, shadowRadius: 28, elevation: 14 }}>
                <View className="relative h-[260px]">
                  <RemoteImage uri={normalizeMediaUrl(localNews.image_url)} fallbackSource={EDITORIAL_IMAGES[localNews.id]} className="h-full w-full" aspectRatio={16 / 10} accessibilityLabel={`Imagen de ${localNews.title}`} />
                  <LinearGradient colors={['transparent', 'rgba(17,24,39,0.3)', 'rgba(17,24,39,0.7)', 'rgba(17,24,39,1)']} locations={[0, 0.45, 0.72, 1]} style={{ position: 'absolute', bottom: 0, left: 0, right: 0, height: 145 }} pointerEvents="none" />
                  <View className="absolute left-4 top-4 z-20 flex-row items-center gap-2">
                    <View className="flex-row items-center gap-2 rounded-full border border-emerald-300/50 bg-emerald-500/90 px-3.5 py-1.5"><Animated.View style={{ opacity: glow }}><View className="h-2 w-2 rounded-full bg-white" /></Animated.View><Text className="text-[10px] font-black uppercase tracking-wider text-white">Noticia local</Text></View>
                    {localNews.read_time ? <View className="flex-row items-center gap-1.5 rounded-full bg-black/40 px-2.5 py-1.5"><Clock color="#fff" size={10} /><Text className="text-[10px] font-bold text-white">{localNews.read_time}</Text></View> : null}
                  </View>
                </View>
                <View className="p-5 pt-4">
                  <Text className="mb-3 text-[24px] font-black leading-[29px] tracking-tight text-white">{localNews.title}</Text>
                  <Text className="mb-5 text-[14px] font-medium leading-6 text-gray-400" style={{ textAlign: 'justify' }} numberOfLines={3}>{localNews.excerpt}</Text>
                  <View className="flex-row items-center justify-between border-t border-white/10 pt-4">
                    <View className="flex-row items-center gap-2.5 rounded-2xl bg-emerald-500 px-5 py-3"><Text className="text-[14px] font-black text-white">Leer noticia</Text><ArrowRight color="#fff" size={16} strokeWidth={3} /></View>
                    <TouchableOpacity
                      onPress={(event) => { event.stopPropagation?.(); void toggleReaction('news', localNews); }}
                      disabled={reactionPending[localNews.id]}
                      className={`min-h-11 min-w-11 flex-row items-center justify-center gap-1.5 rounded-full px-3 ${localNews.liked_by_me ? 'bg-rose-500' : 'bg-white/10'}`}
                      accessibilityRole="button"
                      accessibilityLabel={localNews.liked_by_me ? 'Quitar corazón' : 'Dar corazón'}
                      accessibilityState={{ selected: Boolean(localNews.liked_by_me), busy: Boolean(reactionPending[localNews.id]) }}
                    >
                      {reactionPending[localNews.id] ? <ActivityIndicator color="#fff" size="small" /> : <Heart color="#fff" fill={localNews.liked_by_me ? '#fff' : 'transparent'} size={18} />}
                      <Text className="text-xs font-black text-white">{Number(localNews.likes_count) || 0}</Text>
                    </TouchableOpacity>
                  </View>
                </View>
              </View>
            </TouchableScale>
          ) : (
            <View className="rounded-[28px] border border-slate-100 bg-white p-6"><Text className="font-bold text-slate-600">Aún no hay noticias locales publicadas.</Text></View>
          )}
        </Animated.View>

      </ScrollView>
    </SafeAreaView>
  );
}
