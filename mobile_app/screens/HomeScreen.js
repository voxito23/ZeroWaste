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
} from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import { StatusBar } from 'expo-status-bar';
import { SafeAreaView } from 'react-native-safe-area-context';
import { useScrollContext } from '../context/ScrollContext';
import { useAuth } from '../store/useAuth';
import { api } from '../api/axios';
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
  Share2,
  Bookmark,
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
    likes: '1.2k',
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
    likes: '850',
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
    likes: '920',
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
    likes: '1.5k',
    time: '7 min',
    comments: 42,
    icon: Leaf,
  }
];

const sabiasQueData = [
  { id: '1', icon: Users, val: '+5K', label: 'Voluntarios activos en la comunidad', bg: '#F0FDF4', iconBg: '#D1FAE5', c: '#059669' },
  { id: '2', icon: TreePine, val: '+165K', label: 'Árboles plantados este año', bg: '#ECFEFF', iconBg: '#CFFAFE', c: '#0891B2' },
  { id: '3', icon: Globe, val: '13', label: 'Municipios participando en QRO', bg: '#F5F3FF', iconBg: '#EDE9FE', c: '#7C3AED' },
  { id: '4', icon: Award, val: '30%', label: 'Tasa de reciclaje estatal alcanzada', bg: '#FFF7ED', iconBg: '#FFEDD5', c: '#EA580C' },
];

const defaultCampaigns = [
  { id: 1, title: 'Recolección de Electrónicos', date: '12 May', location: 'Plaza Central', tag: 'CAMPAÑA', img: require('../assets/images/event1.png'), likes: 234 },
  { id: 2, title: 'Limpieza de Playa', date: '20 May', location: 'Playa Norte', tag: 'EVENTO', img: require('../assets/images/event2.jpg'), likes: 512 },
];

/* ─── ANIMATED PRIMITIVES ─────────────────────────────────────────── */
const TouchableScale = ({ children, style, onPress, scaleVal = 0.97 }) => {
  const scale = useRef(new Animated.Value(1)).current;
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
  const anims = Array.from({ length: 7 }, () => useRef(new Animated.Value(0)).current);
  const slides = Array.from({ length: 7 }, () => useRef(new Animated.Value(50)).current);
  const glow = useRef(new Animated.Value(0.4)).current;
  const ringPulse = useRef(new Animated.Value(1)).current;

  const tendListRef = useRef(null);
  const sabiasListRef = useRef(null);
  const { user } = useAuth();
  const { handleScroll } = useScrollContext();
  const [tendIndex, setTendIndex] = useState(0);
  const [sabiasIndex, setSabiasIndex] = useState(0);
  const [campaignsList, setCampaignsList] = useState(defaultCampaigns);

  useEffect(() => {
    const fetchActiveCampaignsAndEvents = async () => {
      try {
        const [campRes, evRes] = await Promise.all([
          api.get('/campanas').catch(() => ({ data: null })),
          api.get('/eventos').catch(() => ({ data: null }))
        ]);
        if (campRes.data !== null || evRes.data !== null) {
          const dbCampaigns = (campRes.data || []).map(c => ({
            id: `camp-${c.id}`,
            title: c.nombre || 'Campaña Eco',
            date: c.fecha_inicio ? new Date(c.fecha_inicio).toLocaleDateString('es-ES', { day: '2-digit', month: 'short' }) : 'Próximamente',
            location: c.lugar || 'Querétaro',
            tag: (c.tipo_etiqueta || 'CAMPAÑA').toUpperCase(),
            img: c.imagen_url ? { uri: c.imagen_url.startsWith('http') ? c.imagen_url : `https://zerowaste-qro.com/static/img/campanas/${c.imagen_url}` } : require('../assets/images/event1.png'),
            likes: c.recompensa_puntos || 100
          }));
          const dbEvents = (evRes.data || []).map(e => ({
            id: `ev-${e.id}`,
            title: e.titulo || 'Evento Eco',
            date: e.fecha_inicio ? new Date(e.fecha_inicio).toLocaleDateString('es-ES', { day: '2-digit', month: 'short' }) : 'Próximamente',
            location: e.lugar || 'Querétaro',
            tag: (e.tipo_etiqueta || 'EVENTO').toUpperCase(),
            img: e.imagen_url ? { uri: e.imagen_url.startsWith('http') ? e.imagen_url : `https://zerowaste-qro.com/static/img/eventos/${e.imagen_url}` } : require('../assets/images/event2.jpg'),
            likes: 250
          }));
          setCampaignsList([...dbCampaigns, ...dbEvents]);
        }
      } catch (e) {
        console.log('Error al cargar campañas y eventos desde BD:', e);
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

    const sabiasTimer = setInterval(() => {
      setSabiasIndex(prev => {
        const next = (prev + 1) % sabiasQueData.length;
        sabiasListRef.current?.scrollToIndex({ index: next, animated: true });
        return next;
      });
    }, 3500);

    return () => { clearInterval(tendTimer); clearInterval(sabiasTimer); };
  }, []);

  const anim = (i) => ({ opacity: anims[i], transform: [{ translateY: slides[i] }] });

  const goTendencia = (dir) => {
    const next = (tendIndex + dir + tendencias.length) % tendencias.length;
    setTendIndex(next);
    tendListRef.current?.scrollToIndex({ index: next, animated: true });
  };

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
            <TouchableScale scaleVal={0.9}>
              <View className="w-10 h-10 rounded-full bg-white items-center justify-center border border-gray-100 shadow-sm">
                <Search color="#374151" size={18} strokeWidth={2.5} />
              </View>
            </TouchableScale>
            <TouchableScale scaleVal={0.9}>
              <View className="w-10 h-10 rounded-full bg-white items-center justify-center border border-gray-100 shadow-sm">
                <Bell color="#374151" size={18} strokeWidth={2.5} />
                <View className="absolute -top-0.5 -right-0.5 w-4 h-4 rounded-full bg-red-500 items-center justify-center border-2 border-white">
                  <Text className="text-white text-[8px] font-black">3</Text>
                </View>
              </View>
            </TouchableScale>
            <TouchableScale scaleVal={0.9}>
              <View className="w-10 h-10 rounded-full bg-emerald-500 items-center justify-center border-2 border-emerald-100 overflow-hidden">
                {user?.foto_perfil && user.foto_perfil !== 'perfil_default.png' ? (
                  <Image 
                    source={{ uri: user.foto_perfil.startsWith('http') ? user.foto_perfil : `https://zerowaste-qro.com/static/img/perfiles/${user.foto_perfil}` }} 
                    className="w-full h-full"
                    resizeMode="cover"
                  />
                ) : (
                  <Text className="text-white text-[14px] font-black">{user?.nombre?.charAt(0).toUpperCase() || 'V'}</Text>
                )}
              </View>
            </TouchableScale>
          </View>
        </View>

        {/* ═══ 1. IMPACT DASHBOARD (Premium Interactive) ═══════ */}
        <Animated.View style={anim(0)} className="px-5 mb-8 pt-2">
          <TouchableScale scaleVal={0.98}>
            <View
              className="rounded-[28px] overflow-hidden"
              style={{ shadowColor: '#064E3B', shadowOffset: { width: 0, height: 20 }, shadowOpacity: 0.25, shadowRadius: 30, elevation: 18 }}
            >
              {/* Dark base with emerald accent layers */}
              <View className="bg-[#0A0F1A] p-6">
                {/* Decorative orbs */}
                <View className="absolute -top-16 -right-16 w-48 h-48 rounded-full" style={{ backgroundColor: 'rgba(16,185,129,0.08)' }} />
                <View className="absolute -bottom-10 -left-10 w-32 h-32 rounded-full" style={{ backgroundColor: 'rgba(34,211,238,0.06)' }} />

                {/* Top Row: Level badge */}
                <View className="flex-row items-center gap-3 mb-7">
                  <Animated.View style={{ transform: [{ scale: ringPulse }] }}>
                    <View className="w-14 h-14 rounded-[20px] items-center justify-center border-2 border-emerald-500/30" style={{ backgroundColor: 'rgba(16,185,129,0.12)' }}>
                      <Trophy color="#34D399" size={24} />
                    </View>
                  </Animated.View>
                  <View>
                    <Text className="text-emerald-400 text-[11px] font-black uppercase tracking-[0.15em]">Tu impacto</Text>
                    <Text className="text-white text-[18px] font-black tracking-tight mt-0.5">Eco-Hero</Text>
                  </View>
                </View>

                {/* Stats Grid */}
                {/* Stats Grid — centered */}
                <View className="flex-row gap-3 mb-6 justify-center">
                  {[
                    { icon: Recycle, value: 15, suffix: 'kg', label: 'Reciclado', color: '#34D399', bg: 'rgba(16,185,129,0.1)', border: 'rgba(16,185,129,0.15)' },
                    { icon: TreePine, value: 3, suffix: '', label: 'Árboles', color: '#22D3EE', bg: 'rgba(34,211,238,0.1)', border: 'rgba(34,211,238,0.15)' },
                    { icon: Target, value: 82, suffix: '%', label: 'Ranking', color: '#A78BFA', bg: 'rgba(167,139,250,0.1)', border: 'rgba(167,139,250,0.15)' },
                  ].map((m, i) => (
                    <TouchableScale key={i} scaleVal={0.95} style={{ flex: 1, maxWidth: (width - 40 - 48 - 24) / 3 }}>
                      <View
                        className="rounded-[20px] py-4 px-2 items-center justify-center border"
                        style={{ backgroundColor: m.bg, borderColor: m.border }}
                      >
                        <m.icon color={m.color} size={18} strokeWidth={2.5} />
                        <AnimCounter target={m.value} suffix={m.suffix} color={m.color} />
                        <Text className="text-gray-500 text-[10px] font-bold uppercase tracking-wider mt-1 text-center">{m.label}</Text>
                      </View>
                    </TouchableScale>
                  ))}
                </View>

                {/* XP Progress */}
                <View className="bg-white/5 rounded-2xl p-4 border border-white/5">
                  <View className="flex-row items-center justify-between mb-3">
                    <View className="flex-row items-center gap-2">
                      <Sparkles color="#34D399" size={14} />
                      <Text className="text-gray-400 text-[11px] font-bold uppercase tracking-wider">Progreso nivel</Text>
                    </View>
                    <Text className="text-emerald-400 text-[12px] font-black">720 / 1000 XP</Text>
                  </View>
                  <View className="bg-white/8 rounded-full h-3 overflow-hidden">
                    <View className="h-full rounded-full" style={{ width: '72%', backgroundColor: '#34D399' }}>
                      <Animated.View
                        className="absolute right-0 top-0 bottom-0 w-8 rounded-full"
                        style={{ backgroundColor: 'rgba(255,255,255,0.3)', opacity: glow }}
                      />
                    </View>
                  </View>
                  <View className="flex-row items-center justify-between mt-2.5">
                    <Text className="text-gray-500 text-[11px] font-bold">Nivel 4</Text>
                    <View className="flex-row items-center gap-1.5">
                      <BarChart3 color="#6B7280" size={11} />
                      <Text className="text-gray-500 text-[11px] font-bold">280 XP para Nivel 5</Text>
                    </View>
                  </View>
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
                <TouchableScale scaleVal={0.98}>
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

                      {/* Bookmark Ribbon on top right */}
                      <View className="absolute top-0 right-6 w-10 h-14 bg-[#059669] rounded-b-lg items-center justify-center shadow-sm z-10">
                        <Bookmark color="#fff" size={18} fill="#34D399" className="-mt-2" />
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
                        <View className="flex-row items-center gap-4">
                          <View className="flex-row items-center gap-1.5">
                            <Heart color="#EF4444" size={17} fill="#EF4444" />
                            <Text className="text-gray-400 text-[13px] font-bold">{item.likes}</Text>
                          </View>
                          <Share2 color="#6B7280" size={17} />
                        </View>
                      </View>
                    </View>
                  </View>
                </TouchableScale>
              </View>
            )}
          />
        </Animated.View>

        {/* ═══ 3. ¿SABÍAS QUE...? (Auto carousel) ═════════════ */}
        <Animated.View style={anim(2)} className="mb-10">
          <View className="px-5 mb-4">
            <Text className="text-[22px] font-black text-gray-900 tracking-tight">¿Sabías que...?</Text>
          </View>
          <FlatList
            ref={sabiasListRef}
            data={sabiasQueData}
            horizontal
            showsHorizontalScrollIndicator={false}
            snapToInterval={width * 0.75 + 16}
            decelerationRate="fast"
            contentContainerStyle={{ paddingHorizontal: 20, gap: 16 }}
            keyExtractor={item => item.id}
            onScrollToIndexFailed={() => {}}
            renderItem={({ item }) => (
              <TouchableScale scaleVal={0.96}>
                <View 
                  className="rounded-[24px] p-5 flex-row items-center gap-4 border" 
                  style={{ width: width * 0.75, backgroundColor: item.bg, borderColor: item.iconBg }}
                >
                  <View className="w-14 h-14 rounded-[18px] items-center justify-center shadow-sm" style={{ backgroundColor: item.iconBg }}>
                    <item.icon color={item.c} size={24} strokeWidth={2.5} />
                  </View>
                  <View className="flex-1">
                    <Text className="text-[26px] font-black text-gray-900 tracking-tighter mb-0.5">{item.val}</Text>
                    <Text className="text-[12px] text-gray-600 font-bold leading-tight pr-2">{item.label}</Text>
                  </View>
                </View>
              </TouchableScale>
            )}
          />
        </Animated.View>

        {/* ═══ 4. CLASIFICA Y REDUCE (Gradient banner) ════════ */}
        <Animated.View style={anim(3)} className="px-5 mb-10">
          <TouchableScale scaleVal={0.97}>
            <View className="rounded-[24px] overflow-hidden bg-[#064E3B] relative border border-emerald-700/50">
              {/* Decorative layer */}
              <View className="absolute top-0 left-0 w-full h-full" style={{ backgroundColor: 'rgba(255,255,255,0.03)' }} />
              <View className="absolute -top-12 -right-12 w-44 h-44 rounded-full" style={{ backgroundColor: 'rgba(52,211,153,0.15)' }} />
              <View className="absolute -bottom-8 -left-8 w-32 h-32 rounded-full" style={{ backgroundColor: 'rgba(16,185,129,0.1)' }} />
              
              <View className="p-6 flex-row items-center justify-between z-10">
                <View className="flex-1 pr-6">
                  <View className="flex-row items-center gap-2 mb-2">
                    <Leaf color="#34D399" size={16} />
                    <Text className="text-emerald-300 text-[11px] font-black uppercase tracking-[0.2em]">Movimiento Eco</Text>
                  </View>
                  <Text className="text-white text-[20px] font-black leading-tight mb-1">Clasifica y Reduce.</Text>
                  <Text className="text-emerald-100/70 text-[13px] font-medium">Construyendo un futuro verde.</Text>
                </View>
                
                <View className="w-12 h-12 rounded-full bg-white items-center justify-center" style={{ shadowColor: '#000', shadowOpacity: 0.2, shadowRadius: 10, elevation: 5 }}>
                  <ArrowRight color="#064E3B" size={20} strokeWidth={3} />
                </View>
              </View>
            </View>
          </TouchableScale>
        </Animated.View>

        {/* ═══ 5. CAMPAÑAS ACTIVAS ════════════════════════════ */}
        <Animated.View style={anim(4)} className="mb-10">
          <View className="px-5 mb-5 flex-row items-center justify-between">
            <Text className="text-[24px] font-black text-gray-900 tracking-tight">Campañas <Text className="text-emerald-600">Activas</Text></Text>
            <TouchableOpacity className="flex-row items-center gap-1 bg-emerald-50 px-3 py-1.5 rounded-full">
              <Text className="text-[12px] font-bold text-emerald-600">Ver todas</Text>
              <ChevronRight color="#059669" size={12} />
            </TouchableOpacity>
          </View>

          {campaignsList.length === 0 ? (
            <View className="px-5 py-8 items-center justify-center bg-gray-50 rounded-[28px] mx-5 border border-gray-100">
              <Calendar color="#9CA3AF" size={32} />
              <Text className="text-gray-500 font-bold text-center mt-2 text-base">No hay campañas o eventos activos</Text>
              <Text className="text-gray-400 text-xs text-center mt-1">Próximamente publicaremos nuevas iniciativas eco-amigables.</Text>
            </View>
          ) : (
            <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={{ paddingHorizontal: 20, gap: 16 }}>
              {campaignsList.map((camp) => (
                <TouchableScale key={camp.id}>
                  <View
                    className="w-[300px] rounded-[28px] overflow-hidden bg-white border border-gray-100"
                    style={{ shadowColor: '#000', shadowOffset: { width: 0, height: 10 }, shadowOpacity: 0.08, shadowRadius: 24, elevation: 10 }}
                  >
                    <View className="relative h-[190px]">
                      <Image source={camp.img} className="w-full h-full" resizeMode="cover" />
                      
                      <View className="absolute top-4 left-4 bg-black/60 px-3 py-1.5 rounded-full border border-white/10">
                        <Text className="text-white text-[10px] font-black uppercase tracking-[0.1em]">{camp.tag}</Text>
                      </View>
                      <View className="absolute top-4 right-4">
                        <TouchableScale scaleVal={0.85}>
                          <View className="w-9 h-9 rounded-full bg-white items-center justify-center shadow-sm">
                            <Bookmark color="#374151" size={15} />
                          </View>
                        </TouchableScale>
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
                      
                      <View className="flex-row items-center gap-4 mb-5">
                        <View className="flex-row items-center gap-1.5">
                          <Heart color="#EF4444" size={16} fill="#EF4444" />
                          <Text className="text-gray-500 text-[13px] font-bold">{camp.likes}</Text>
                        </View>
                        <Share2 color="#9CA3AF" size={15} />
                      </View>

                      <TouchableScale>
                        <View className="bg-[#064E3B] rounded-2xl py-3.5 items-center flex-row justify-center gap-2">
                          <Text className="text-white text-[15px] font-black">Unirse</Text>
                          <ArrowRight color="#fff" size={16} strokeWidth={3} />
                        </View>
                      </TouchableScale>
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
          <TouchableScale scaleVal={0.98}>
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

                {/* Bookmark Ribbon on top right */}
                <View className="absolute top-0 right-6 w-10 h-14 bg-[#059669] rounded-b-lg items-center justify-center shadow-sm z-10">
                  <Bookmark color="#fff" size={18} fill="#34D399" className="-mt-2" />
                </View>

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
                  <View className="flex-row items-center gap-4">
                    <TouchableScale scaleVal={0.85}>
                      <View className="flex-row items-center gap-1.5">
                        <Heart color="#EF4444" size={17} fill="#EF4444" />
                        <Text className="text-gray-400 text-[13px] font-bold">847</Text>
                      </View>
                    </TouchableScale>
                    <TouchableScale scaleVal={0.85}>
                      <Share2 color="#6B7280" size={17} />
                    </TouchableScale>
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