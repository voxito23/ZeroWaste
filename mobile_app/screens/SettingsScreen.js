import React, { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { Animated, Linking, Pressable, ScrollView, Text, TouchableOpacity, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import {
  Accessibility,
  ArrowLeft,
  Bell,
  Camera,
  ChevronRight,
  FileText,
  HelpCircle,
  KeyRound,
  LogOut,
  Map,
  MapPin,
  MessageCircle,
  Newspaper,
  ShieldCheck,
  Sparkles,
  ThumbsUp,
  UserRoundPen,
  Volume2,
} from 'lucide-react-native';
import { useNavigation } from '@react-navigation/native';

import { api } from '../api/axios';
import UserAvatar from '../components/ui/UserAvatar';
import Skeleton from '../components/ui/Skeleton';
import ZeroWasteDialog, { useZeroWasteDialog } from '../components/ui/ZeroWasteDialog';
import useMapAppearance from '../hooks/useMapAppearance';
import { getPushRegistrationStatus, registerPushToken, unregisterPushToken } from '../services/mobileNotifications';
import { voiceNavigation } from '../services/voiceNavigation';
import { useAuth } from '../store/useAuth';
import { resolveAvatar } from '../utils/user';

const MAP_LABELS = { automatic: 'Automático', day: 'Día', dusk: 'Atardecer', night: 'Noche' };
const MAP_SEQUENCE = ['automatic', 'day', 'dusk', 'night'];
const VOICE_VOLUMES = [0.45, 0.72, 1];
const volumeLabel = (value) => value < 0.6 ? 'Bajo' : value < 0.9 ? 'Medio' : 'Alto';
const DEFAULT_PREFERENCES = { push_enabled: true, in_app_enabled: true, comments: true, replies: true, likes: true, news: true, articles: true, campaigns: true, collections: true, points: true, rewards: true, system: true };

const maskedEmail = (email) => {
  const [name = '', domain = ''] = String(email || '').split('@');
  if (!domain) return '';
  return `${name.slice(0, 2)}${'*'.repeat(Math.max(2, name.length - 2))}@${domain}`;
};

export default function SettingsScreen() {
  const navigation = useNavigation();
  const { user, logout } = useAuth();
  const { showDialog } = useZeroWasteDialog();
  const { preference: mapPreference, setPreference: setMapPreference } = useMapAppearance();
  const [logoutVisible, setLogoutVisible] = useState(false);
  const [preferences, setPreferences] = useState(DEFAULT_PREFERENCES);
  const [preferencesError, setPreferencesError] = useState('');
  const [preferencesLoading, setPreferencesLoading] = useState(true);
  const [savingPreference, setSavingPreference] = useState('');
  const [voiceEnabled, setVoiceEnabled] = useState(true);
  const [voiceVolume, setVoiceVolume] = useState(1);
  const [pushStatus, setPushStatus] = useState({ nativeAvailable: true, permission: 'undetermined', registered: false, activeDevices: 0, lastError: null });
  const voiceAvailable = voiceNavigation.isAvailable();

  const loadPreferences = useCallback(() => {
    setPreferencesLoading(true);
    setPreferencesError('');
    return api.get('/preferences/notifications')
      .then(({ data }) => setPreferences({ ...DEFAULT_PREFERENCES, ...data }))
      .catch((error) => setPreferencesError(error.userMessage || 'No fue posible cargar las preferencias de notificaciones.'))
      .finally(() => setPreferencesLoading(false));
  }, []);

  const loadPushStatus = useCallback(() => getPushRegistrationStatus()
    .then(setPushStatus)
    .catch(() => setPushStatus((current) => ({ ...current, registered: false, lastError: 'StatusUnavailable' }))), []);

  useEffect(() => {
    void loadPreferences();
    void loadPushStatus();
    voiceNavigation.hydrate().then(() => {
      const enabled = voiceAvailable && !voiceNavigation.isMuted();
      setVoiceEnabled(enabled);
      setVoiceVolume(voiceNavigation.getVolume());
    });
  }, [loadPreferences, loadPushStatus, voiceAvailable]);

  const savePreference = async (key, value) => {
    const previous = preferences[key];
    setSavingPreference(key);
    setPreferences((current) => ({ ...current, [key]: value }));
    setPreferencesError('');
    try {
      const { data } = await api.patch('/preferences/notifications', { [key]: value });
      setPreferences((current) => ({ ...current, ...data }));
    } catch (error) {
      setPreferences((current) => ({ ...current, [key]: previous }));
      setPreferencesError(error.userMessage || 'No se pudo guardar la preferencia.');
    } finally {
      setSavingPreference('');
    }
  };

  const enablePush = async () => {
    try {
      const result = await registerPushToken();
      if (result.status === 'granted') {
        await savePreference('push_enabled', true);
        await loadPushStatus();
      }
      else if (result.status === 'denied') showDialog({ type: 'permission', title: 'Permiso no concedido', message: 'Activa las notificaciones de ZeroWaste desde los ajustes del teléfono y vuelve a esta pantalla.', primaryLabel: 'Abrir ajustes', onPrimary: Linking.openSettings, secondaryLabel: 'Ahora no' });
      else showDialog({ type: 'info', title: 'Dispositivo no compatible', message: result.message || 'Esta instalación todavía no puede registrar notificaciones push.' });
    } catch (error) {
      showDialog({ type: 'error', title: 'No se activaron las notificaciones', message: error.response?.data?.detail || error.userMessage || error.message || 'Verifica tu conexión e inténtalo de nuevo.' });
    }
  };

  const togglePush = (value) => {
    if (!value) {
      void unregisterPushToken().finally(async () => {
        await savePreference('push_enabled', false);
        await loadPushStatus();
      });
      return;
    }
    showDialog({
      type: 'permission',
      title: 'Activa notificaciones útiles',
      message: 'ZeroWaste puede avisarte sobre comentarios, respuestas, recolecciones, puntos y recompensas. Puedes elegir cada categoría y desactivarlas cuando quieras.',
      primaryLabel: 'Continuar',
      secondaryLabel: 'Ahora no',
      onPrimary: () => void enablePush(),
    });
  };

  const pushActive = Boolean(preferences.push_enabled && pushStatus.nativeAvailable && pushStatus.permission === 'granted' && pushStatus.registered);
  const pushDescription = !pushStatus.nativeAvailable
    ? 'Requiere una Development Build con expo-notifications'
    : pushStatus.permission === 'denied'
      ? 'Permiso desactivado en el teléfono'
      : pushStatus.lastError === 'StatusUnavailable'
        ? 'No fue posible comprobar el registro'
        : pushStatus.lastError
        ? `Requiere revisión: ${pushStatus.lastError}`
        : pushStatus.registered
          ? `${pushStatus.activeDevices} dispositivo${pushStatus.activeDevices === 1 ? '' : 's'} registrado${pushStatus.activeDevices === 1 ? '' : 's'}`
          : 'Toca para registrar este dispositivo';

  const cycleMapPreference = async () => {
    const next = MAP_SEQUENCE[(MAP_SEQUENCE.indexOf(mapPreference) + 1) % MAP_SEQUENCE.length];
    await setMapPreference(next);
  };

  const toggleVoice = async (value) => {
    setVoiceEnabled(value);
    await voiceNavigation.setEnabled(value);
  };

  const cycleVoiceVolume = async () => {
    const index = VOICE_VOLUMES.findIndex((value) => Math.abs(value - voiceVolume) < 0.02);
    const next = VOICE_VOLUMES[(index + 1) % VOICE_VOLUMES.length];
    setVoiceVolume(next);
    await voiceNavigation.setVolume(next);
  };

  const accountRows = useMemo(() => [
    { label: 'Editar perfil', description: 'Foto, nombre, biografía y ubicación', icon: UserRoundPen, onPress: () => navigation.navigate('EditProfile') },
    { label: 'Contraseña y seguridad', description: 'Actualiza tus credenciales de acceso', icon: KeyRound, onPress: () => navigation.navigate('ChangePassword') },
  ], [navigation]);

  const notificationRows = [
    { key: 'push_enabled', label: 'Notificaciones push', description: pushDescription, icon: Bell, onChange: togglePush },
    { key: 'comments', label: 'Comentarios', description: 'Actividad en tus publicaciones', icon: MessageCircle },
    { key: 'replies', label: 'Respuestas', description: 'Respuestas a tus comentarios', icon: MessageCircle },
    { key: 'likes', label: 'Me gusta', description: 'Reacciones a tus publicaciones', icon: ThumbsUp },
    { key: 'news', label: 'Noticias', description: 'Noticias locales publicadas', icon: Newspaper },
    { key: 'articles', label: 'Artículos', description: 'Contenido editorial destacado', icon: FileText },
    { key: 'campaigns', label: 'Campañas', description: 'Campañas ambientales activas', icon: Sparkles },
    { key: 'collections', label: 'Recolecciones', description: 'Asignaciones, recordatorios y estado', icon: Sparkles },
    { key: 'points', label: 'Puntos', description: 'Movimientos en tu cuenta', icon: Sparkles },
    { key: 'rewards', label: 'Recompensas', description: 'Estado de canjes y entregas', icon: Sparkles },
    { key: 'system', label: 'Sistema', description: 'Avisos de seguridad y servicio', icon: ShieldCheck },
    { key: 'in_app_enabled', label: 'Dentro de la app', description: 'Guardar avisos en el centro de notificaciones', icon: Bell },
  ];

  return (
    <SafeAreaView className="flex-1 bg-slate-50" edges={['top', 'bottom']}>
      <View className="flex-row items-center border-b border-slate-100 bg-white px-5 py-4"><TouchableOpacity onPress={() => navigation.goBack()} className="h-11 w-11 items-center justify-center rounded-full bg-slate-100" accessibilityLabel="Volver"><ArrowLeft color="#111827" size={20} /></TouchableOpacity><View className="ml-4"><Text className="text-xl font-black text-slate-950">Ajustes y actividad</Text><Text className="text-xs font-semibold text-slate-500">Cuenta, privacidad y preferencias</Text></View></View>
      <ScrollView contentContainerStyle={{ padding: 18, paddingBottom: 40 }}>
        <TouchableOpacity onPress={() => navigation.navigate('EditProfile')} className="mb-7 flex-row items-center rounded-[26px] border border-emerald-100 bg-emerald-950 p-5"><UserAvatar uri={resolveAvatar(user)} name={user?.nombre} size={64} /><View className="ml-4 flex-1"><Text className="text-xl font-black text-white" numberOfLines={1}>{user?.nombre || 'Tu cuenta'}</Text><Text className="mt-1 text-sm text-emerald-100">{maskedEmail(user?.email)}</Text><Text className="mt-2 text-xs font-black uppercase tracking-widest text-emerald-300">Editar perfil</Text></View><ChevronRight color="#A7F3D0" size={20} /></TouchableOpacity>

        <SettingsGroup title="TU CUENTA" rows={accountRows} />

        <Text className="mb-2 mt-1 px-1 text-xs font-black uppercase tracking-widest text-slate-500">NOTIFICACIONES</Text>
        {preferencesError ? <TouchableOpacity onPress={loadPreferences} className="mb-3 rounded-2xl border border-amber-200 bg-amber-50 p-3"><Text className="font-bold text-amber-900">{preferencesError} Toca para reintentar.</Text></TouchableOpacity> : null}
        {preferencesLoading ? <View className="mb-7 rounded-3xl bg-white p-4">{[0, 1, 2, 3].map((item) => <Skeleton key={item} className="mb-3 h-[58px] rounded-2xl" />)}</View> : <View className="mb-7 overflow-hidden rounded-3xl border border-slate-100 bg-white">{notificationRows.map(({ key, ...row }, index) => <SettingSwitch key={key} {...row} value={key === 'push_enabled' ? pushActive : Boolean(preferences[key])} disabled={savingPreference === key} onChange={row.onChange || ((value) => savePreference(key, value))} divider={index > 0} />)}</View>}

        <Text className="mb-2 px-1 text-xs font-black uppercase tracking-widest text-slate-500">PREFERENCIAS</Text>
        <View className="mb-7 overflow-hidden rounded-3xl border border-slate-100 bg-white">
          <SettingRow icon={Map} label="Apariencia del mapa" description="Amanecer, día, atardecer y noche" value={MAP_LABELS[mapPreference]} onPress={cycleMapPreference} />
          <SettingSwitch icon={Volume2} label="Asistente de voz" description={voiceAvailable ? 'Instrucciones de navegación en español' : 'Disponible después de la nueva Development Build'} value={voiceEnabled} onChange={toggleVoice} disabled={!voiceAvailable} divider />
          {voiceAvailable ? <SettingRow icon={Volume2} label="Volumen de voz" description="Nivel del asistente durante la ruta" value={volumeLabel(voiceVolume)} onPress={cycleVoiceVolume} divider /> : null}
          <SettingRow icon={Accessibility} label="Accesibilidad del dispositivo" description="Movimiento reducido, texto y permisos" onPress={Linking.openSettings} divider />
        </View>

        <Text className="mb-2 px-1 text-xs font-black uppercase tracking-widest text-slate-500">PRIVACIDAD Y PERMISOS</Text>
        <View className="mb-7 overflow-hidden rounded-3xl border border-slate-100 bg-white"><SettingRow icon={MapPin} label="Ubicación" description="Mapa, distancia y rutas internas" onPress={Linking.openSettings} /><SettingRow icon={Camera} label="Cámara y fotografías" description="Escáner QR, perfil y publicaciones" onPress={Linking.openSettings} divider /><SettingRow icon={ShieldCheck} label="Privacidad" description="Cómo protegemos tus datos" onPress={() => navigation.navigate('InfoDocument', { document: 'privacy' })} divider /></View>

        <Text className="mb-2 px-1 text-xs font-black uppercase tracking-widest text-slate-500">AYUDA</Text>
        <View className="mb-7 overflow-hidden rounded-3xl border border-slate-100 bg-white"><SettingRow icon={HelpCircle} label="Ayuda y soporte" description="Asistente de voz, preguntas frecuentes y contacto" onPress={() => navigation.navigate('HelpSupport')} /><SettingRow icon={FileText} label="Términos" description="Condiciones de uso dentro de la app" onPress={() => navigation.navigate('InfoDocument', { document: 'terms' })} divider /></View>

        <TouchableOpacity onPress={() => setLogoutVisible(true)} className="min-h-14 flex-row items-center justify-center rounded-2xl border border-red-100 bg-red-50"><LogOut color="#DC2626" size={20} /><Text className="ml-2 font-black text-red-600">Cerrar sesión</Text></TouchableOpacity>
      </ScrollView>
      <ZeroWasteDialog visible={logoutVisible} type="warning" title="Cerrar sesión" message="¿Deseas salir de tu cuenta ZeroWaste en este dispositivo? El token push se desactivará." primaryLabel="Cerrar sesión" onPrimary={() => { setLogoutVisible(false); void unregisterPushToken().finally(logout); }} secondaryLabel="Cancelar" onSecondary={() => setLogoutVisible(false)} />
    </SafeAreaView>
  );
}

function SettingsGroup({ title, rows }) {
  return <View className="mb-7"><Text className="mb-2 px-1 text-xs font-black uppercase tracking-widest text-slate-500">{title}</Text><View className="overflow-hidden rounded-3xl border border-slate-100 bg-white">{rows.map((row, index) => <SettingRow key={row.label} {...row} divider={index > 0} />)}</View></View>;
}

function SettingRow({ icon: Icon, label, description, value, onPress, divider }) {
  return <TouchableOpacity onPress={onPress} className={`min-h-[72px] flex-row items-center px-4 py-3 ${divider ? 'border-t border-slate-100' : ''}`} accessibilityRole="button"><View className="h-10 w-10 items-center justify-center rounded-full bg-emerald-50"><Icon color="#059669" size={19} /></View><View className="ml-3 flex-1"><Text className="font-bold text-slate-900">{label}</Text><Text className="mt-0.5 text-xs leading-4 text-slate-500">{description}</Text></View>{value ? <Text className="mr-2 max-w-24 text-right text-xs font-bold text-slate-500">{value}</Text> : null}<ChevronRight color="#94A3B8" size={18} /></TouchableOpacity>;
}

function SettingSwitch({ icon: Icon, label, description, value, onChange, divider, disabled }) {
  return <View className={`min-h-[72px] flex-row items-center px-4 py-3 ${divider ? 'border-t border-slate-100' : ''}`}><View className="h-10 w-10 items-center justify-center rounded-full bg-emerald-50"><Icon color="#059669" size={19} /></View><View className="ml-3 flex-1"><Text className="font-bold text-slate-900">{label}</Text><Text className="mt-0.5 text-xs leading-4 text-slate-500">{description}</Text></View><PremiumSwitch value={value} disabled={disabled} onChange={onChange} label={label} /></View>;
}

function PremiumSwitch({ value, disabled, onChange, label }) {
  const thumbX = useRef(new Animated.Value(value ? 22 : 0)).current;
  useEffect(() => {
    const animation = Animated.spring(thumbX, { toValue: value ? 22 : 0, damping: 19, stiffness: 260, mass: 0.72, overshootClamping: true, useNativeDriver: true });
    animation.start();
    return () => animation.stop();
  }, [thumbX, value]);
  return <Pressable accessibilityRole="switch" accessibilityLabel={label} accessibilityState={{ checked: value, disabled }} disabled={disabled} onPress={() => onChange(!value)} className={disabled ? 'opacity-50' : ''} hitSlop={8}><View className={`h-8 w-[54px] justify-center rounded-full p-1 ${value ? 'bg-emerald-600' : 'bg-slate-300'}`}><Animated.View className="h-6 w-6 rounded-full bg-white shadow-sm" style={{ transform: [{ translateX: thumbX }] }} /></View></Pressable>;
}
