import React, { useEffect, useRef, useState } from 'react';
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
import { normalizeMediaUrl } from '../utils/media';
import {
  Search,
  Users,
  TreePine,
  Globe,
  Calendar,
  MapPin,
  ArrowRight,
  ArrowLeft,
  Leaf,
  ChevronRight,
  Recycle,
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
  Droplets,
  Sun,
} from 'lucide-react-native';

const { width } = Dimensions.get('window');

/* ─── DATA ────────────────────────────────────────────────────────── */
const tendencias = [
  {
    id: '1',
    title: 'Reciclar plástico:\n10 consejos para reducir hoy.',
    desc: 'Pequeños cambios diarios que generan un impacto global masivo en nuestro planeta.',
    tag: 'TENDENCIA',
    img: require('../assets/images/plasticos.png'),
    url: 'https://www.zerowaste-qro.com/tema/reciclar-plastico',
    time: '5 min',
    comments: 24,
    icon: Recycle,
  },
  {
    id: '2',
    title: 'Ahorro de agua:\nNuevas técnicas para el futuro.',
    desc: 'El agua es un recurso vital y finito. Aprende métodos simples de recolección y reutilización.',
    tag: 'TENDENCIA',
    img: require('../assets/images/aguah.png'),
    url: 'https://www.zerowaste-qro.com/tema/ahorro-agua',
    time: '8 min',
    comments: 18,
    icon: Droplets,
  },
  {
    id: '3',
    title: 'Energía solar:\nFuentes limpias para renovar.',
    desc: 'La transición a energías limpias comienza en casa. Conoce los beneficios de los paneles solares.',
    tag: 'TENDENCIA',
    img: require('../assets/images/solar.png'),
    url: 'https://www.zerowaste-qro.com/tema/energia-solar',
    time: '6 min',
    comments: 31,
    icon: Sun,
  },
  {
    id: '4',
    title: 'Compostaje urbano:\nNutrientes vivos para circular.',
    desc: 'Transforma tus desechos orgánicos en nueva vida. Guía práctica para iniciar tu compostera.',
    tag: 'TENDENCIA',
    img: require('../assets/images/composta.png'),
    url: 'https://www.zerowaste-qro.com/tema/compostaje-urbano',
    time: '7 min',
    comments: 42,
    icon: Leaf,
  }
];

/* ─── ANIMATED PRIMITIVES ─────────────────────────────────────────── */
const TouchableScale = ({ children, style, onPress, scaleVal = 0.97 }) => {
  const scale = useRef(new Animated.Value(1)).current;
  if (!onPress) return <View style={style}>{children}</View>;
  return (
    <Pressable
      onPressIn={() => Animated.spring(scale, { toValue: scaleVal, useNativeDriver: true, speed: 30, bounciness: 6 }).start()}
      onPressOut={() => Animated.spring(scale, { toValue: 1, useNativeDriver: true, speed: 30, bounciness: 6 }).start()}
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
  const { user } = useAuth();
  const { handleScroll } = useScrollContext();
  const [tendIndex, setTendIndex] = useState(0);
  const [campaignsList, setCampaignsList] = useState([]);
  const [contentError, setContentError] = useState('');
  const [contentLoading, setContentLoading] = useState(true);
  const [unreadNotifications, setUnreadNotifications] = useState(0);
  const [impactSummary, setImpactSummary] = useState(null);
  const [impactLoading, setImpactLoading] = useState(true);
  const [impactError, setImpactError] = useState(false);

  useEffect(() => {
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
        setCampaignsList([]);
        setContentError(e.userMessage || 'No se pudieron cargar las campañas y eventos.');
      } finally {
        setContentLoading(false);
      }
    };
    fetchActiveCampaignsAndEvents();
  }, []);

  useEffect(() => {
    Animated.stagger(100,
      anims.map((a, i) =>
        Animated.parallel([
          Animated.timing(a, { toValue: 1, duration: 600, easing: Easing.out(Easing.cubic), useNativeDriver: true }),
          Animated.timing(slides[i], { toValue: 0, duration: 600, easing: Easing.out(Easing.cubic), useNativeDriver: true }),
        ])
      )
    ).start();

    Animated.loop(Animated.sequence([
      Animated.timing(glow, { toValue: 1, duration: 1800, easing: Easing.inOut(Easing.ease), useNativeDriver: true }),
      Animated.timing(glow, { toValue: 0.4, duration: 1800, easing: Easing.inOut(Easing.ease), useNativeDriver: true }),
    ])).start();

    Animated.loop(Animated.sequence([
      Animated.timing(ringPulse, { toValue: 1.08, duration: 2000, easing: Easing.inOut(Easing.ease), useNativeDriver: true }),
      Animated.timing(ringPulse, { toValue: 1, duration: 2000, easing: Easing.inOut(Easing.ease), useNativeDriver: true }),
    ])).start();

    const tendTimer = setInterval(() => {
      setTendIndex(prev => {
        const next = (prev + 1) % tendencias.length;
        tendListRef.current?.scrollToIndex({ index: next, animated: true });
        return next;
      });
    }, 5000);

    return () => clearInterval(tendTimer);
  }, []);

  const anim = (i) => ({ opacity: anims[i], transform: [{ translateY: slides[i] }] });

  const goTendencia = (dir) => {
    const next = (tendIndex + dir + tendencias.length) % tendencias.length;
    setTendIndex(next);
    tendListRef.current?.scrollToIndex({ index: next, animated: true });
  };

  const avatarUrl = user?.avatar_url ?? user?.foto_perfil;

  return (
    <SafeAreaView className="flex-1 bg-[#FAFAFA]" edges={['top']}>
      <StatusBar style="dark" />

      <ScrollView 
        showsVerticalScrollIndicator={false} 
        contentContainerStyle={{ paddingTop: 16, paddingBottom: 130 }}
        onScroll={handleScroll}
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
        <Animated.View style={anim(1)} className="mb-10">
          <View className="px-5 mb-4 flex-row items-center justify-between">
            <View>
              <Text className="text-[26px] font-black text-gray-900 tracking-tight">Tendencias</Text>
              <Text className="text-[12px] text-gray-400 font-bold mt-0.5">Artículos destacados</Text>
            </View>
            <View className="flex-row gap-1.5">
              {tendencias.map((_, idx) => (
                <View key={idx} className={`h-1.5 rounded-full ${idx === tendIndex ? 'w-5 bg-emerald-600' : 'w-1.5 bg-gray-200'}`} />
              ))}
            </View>
          </View>
          
          <FlatList
            ref={tendListRef}
            data={tendencias}
            horizontal
            pagingEnabled
            showsHorizontalScrollIndicator={false}
            keyExtractor={item => item.id}
            onScrollToIndexFailed={() => {}}
            renderItem={({ item }) => (
              <View style={{ width: width, paddingHorizontal: 20 }}>
                <TouchableScale scaleVal={0.98} onPress={() => Linking.openURL(item.url)}>
                  <View
                    className="rounded-[32px] overflow-hidden bg-[#111827] mb-2"
                    style={{ shadowColor: '#000', shadowOffset: { width: 0, height: 16 }, shadowOpacity: 0.25, shadowRadius: 28, elevation: 14 }}
                  >
                    {/* Image Section */}
                    <View className="relative h-[240px] bg-gray-100">
                      <Image source={item.img} className="w-full h-full" resizeMode="cover" />
                      
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
                        <Text className="text-white text-[10px] font-black uppercase tracking-wider">{item.tag}</Text>
                      </View>

                    </View>

                    {/* Content Section */}
                    <View className="px-6 pb-6 pt-5 relative z-10">

                      <Text className="text-white text-[24px] font-black leading-[28px] tracking-tight mb-2">
                        {item.title}
                      </Text>

                      <Text className="text-gray-400 text-[13px] font-medium leading-relaxed mb-5 pr-4 line-clamp-2" numberOfLines={2}>
                        {item.desc}
                      </Text>

                      {/* Progress bar */}
                      <View className="bg-white/10 rounded-full h-1.5 mb-4 overflow-hidden">
                        <View className="bg-[#059669] h-full rounded-full" style={{ width: '35%' }} />
                      </View>

                      {/* Action Row */}
                      <View className="flex-row items-center justify-between pt-4 border-t border-white/8">
                        <View className="flex-row items-center gap-2.5 bg-emerald-500 px-5 py-3 rounded-2xl">
                          <Text className="text-white font-black text-[14px]">Leer artículo</Text>
                          <ArrowRight color="#fff" size={16} strokeWidth={3} />
                        </View>
                      </View>
                    </View>
                  </View>
                </TouchableScale>
              </View>
            )}
          />
        </Animated.View>

        {/* ═══ 5. CAMPAÑAS ACTIVAS ════════════════════════════ */}
        <Animated.View style={anim(4)} className="mb-10">
          <View className="px-5 mb-5 flex-row items-center justify-between">
            <Text className="text-[24px] font-black text-gray-900 tracking-tight">Campañas <Text className="text-emerald-600">Activas</Text></Text>
          </View>

          {contentLoading ? (
            <ActivityIndicator color="#047857" />
          ) : contentError ? (
            <View className="mx-5 rounded-2xl border border-red-200 bg-red-50 p-4"><Text className="text-red-700 text-center font-bold">{contentError}</Text></View>
          ) : campaignsList.length === 0 ? (
            <View className="px-5 py-8 items-center justify-center bg-gray-50 rounded-[28px] mx-5 border border-gray-100">
              <Calendar color="#9CA3AF" size={32} />
              <Text className="text-gray-500 font-bold text-center mt-2 text-base">No hay campañas o eventos activos</Text>
              <Text className="text-gray-400 text-xs text-center mt-1">Próximamente publicaremos nuevas iniciativas eco-amigables.</Text>
            </View>
          ) : (
            <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={{ paddingHorizontal: 20, gap: 16 }}>
              {campaignsList.map((camp) => (
                <TouchableScale key={camp.id} onPress={camp.link ? () => Linking.openURL(camp.link) : undefined}>
                  <View
                    className="w-[300px] rounded-[28px] overflow-hidden bg-white border border-gray-100"
                    style={{ shadowColor: '#000', shadowOffset: { width: 0, height: 10 }, shadowOpacity: 0.08, shadowRadius: 24, elevation: 10 }}
                  >
                    <View className="relative h-[190px]">
                      <RemoteImage uri={camp.imageUrl} fallbackSource={camp.fallbackImage} className="h-full w-full" accessibilityLabel={`Imagen de ${camp.title}`} />
                      
                      <View className="absolute top-4 left-4 bg-black/60 px-3 py-1.5 rounded-full border border-white/10">
                        <Text className="text-white text-[10px] font-black uppercase tracking-[0.1em]">{camp.tag}</Text>
                      </View>

                      <View className="absolute bottom-0 w-full bg-black/50 px-4 py-2.5 flex-row justify-between items-center">
                        <View className="flex-row items-center gap-1.5">
                          <Calendar color="#fff" size={13} />
                          <Text className="text-white text-[12px] font-bold">{camp.date}</Text>
                        </View>
                        <View className="flex-row items-center gap-1.5">
                          <MapPin color="#fff" size={13} />
                          <Text className="text-white text-[12px] font-bold">{camp.location}</Text>
                        </View>
                      </View>
                    </View>

                    <View className="p-5 pt-4">
                      <Text className="text-gray-900 font-extrabold text-[18px] leading-tight mb-2" numberOfLines={2}>{camp.title}</Text>
                      
                      {camp.link ? <TouchableScale onPress={() => Linking.openURL(camp.link)}>
                        <View className="bg-[#064E3B] rounded-2xl py-3.5 items-center flex-row justify-center gap-2">
                          <Text className="text-white text-[15px] font-black">Unirse</Text>
                          <ArrowRight color="#fff" size={16} strokeWidth={3} />
                        </View>
                      </TouchableScale> : null}
                    </View>
                  </View>
                </TouchableScale>
              ))}
            </ScrollView>
          )}
        </Animated.View>

        {/* ═══ 6. NOTICIA LOCAL (Premium with emerald gradient overlay) ═══ */}
        <Animated.View style={anim(5)} className="px-5 mb-10">
          <View className="mb-4">
            <Text className="text-[24px] font-black text-gray-900 tracking-tight">Noticia <Text className="text-emerald-600">Local</Text></Text>
          </View>
          <TouchableScale scaleVal={0.98} onPress={() => Linking.openURL('https://www.zerowaste-qro.com/noticia-queretaro')}>
            <View
              className="rounded-[28px] overflow-hidden bg-[#111827]"
              style={{ shadowColor: '#064E3B', shadowOffset: { width: 0, height: 16 }, shadowOpacity: 0.15, shadowRadius: 28, elevation: 14 }}
            >
              {/* Full-bleed image */}
              <View className="relative h-[260px]">
                <Image source={require('../assets/images/qrocapita.jpg')} className="w-full h-full" resizeMode="cover" />
                
                {/* Dark fade at bottom — multi-stop LinearGradient */}
                <LinearGradient
                  colors={[
                    'transparent',
                    'rgba(17,24,39,0.3)',
                    'rgba(17,24,39,0.6)',
                    'rgba(17,24,39,0.9)',
                    'rgba(17,24,39,1)',
                  ]}
                  locations={[0, 0.4, 0.6, 0.8, 1]}
                  style={{ position: 'absolute', bottom: 0, left: 0, right: 0, height: 140 }}
                  pointerEvents="none"
                />

                {/* Content over the blur */}
                <View className="absolute top-4 left-4 flex-row items-center gap-2 z-20">
                  {/* Floating badge */}
                  <View className="flex-row items-center gap-2 bg-emerald-500/90 px-3.5 py-1.5 rounded-full border border-emerald-300/50">
                    <Animated.View style={{ opacity: glow }}>
                      <View className="w-2 h-2 rounded-full bg-white" />
                    </Animated.View>
                    <Text className="text-white text-[10px] font-black uppercase tracking-wider">Noticia Local</Text>
                  </View>

                  <View className="flex-row items-center gap-1.5 bg-black/40 px-2.5 py-1.5 rounded-full">
                    <Clock color="#fff" size={10} />
                    <Text className="text-white text-[10px] font-bold">5 min de lectura</Text>
                  </View>
                </View>
              </View>

              {/* Content on dark */}
              <View className="p-5 pt-4">
                <Text className="text-white text-[24px] font-black leading-[28px] tracking-tight mb-3">
                  Querétaro recicla{' '}
                  <Text className="text-emerald-400">2.4 kg per cápita</Text>{' '}
                  al día
                </Text>
                <Text className="text-gray-400 text-[14px] leading-relaxed font-medium mb-5">
                  Se ha incrementado el porcentaje de reciclaje de residuos hasta llegar al 30% de los 2.4 kilos per cápita generados diariamente.
                </Text>

                <View className="flex-row items-center justify-between pt-4 border-t border-white/8">
                  <View className="flex-row items-center gap-2.5 bg-emerald-500 px-5 py-3 rounded-2xl">
                    <Text className="text-white font-black text-[14px]">Leer artículo</Text>
                    <ArrowRight color="#fff" size={16} strokeWidth={3} />
                  </View>
                </View>
              </View>
            </View>
          </TouchableScale>
        </Animated.View>

      </ScrollView>
    </SafeAreaView>
  );
}
