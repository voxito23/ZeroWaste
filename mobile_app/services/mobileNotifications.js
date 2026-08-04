import { Platform } from 'react-native';
import * as SecureStore from 'expo-secure-store';

import { api } from '../api/axios';

let NativeNotifications = null;
let Device = { isDevice: false };
let Constants = {};
try { NativeNotifications = require('expo-notifications'); } catch { NativeNotifications = null; }
try { Device = require('expo-device'); } catch { Device = { isDevice: false }; }
try { Constants = require('expo-constants').default || require('expo-constants'); } catch { Constants = {}; }
if (NativeNotifications) {
  try {
    NativeNotifications.setNotificationHandler({
      handleNotification: async () => ({ shouldShowBanner: true, shouldShowList: true, shouldPlaySound: true, shouldSetBadge: false }),
    });
  } catch {
    NativeNotifications = null;
  }
}

const emptySubscription = () => ({ remove() {} });
const Notifications = NativeNotifications || {
  addNotificationResponseReceivedListener: emptySubscription,
  addPushTokenListener: emptySubscription,
  clearLastNotificationResponseAsync: async () => {},
  getLastNotificationResponseAsync: async () => null,
};
export const NOTIFICATIONS_NATIVE_AVAILABLE = Boolean(NativeNotifications);

const DEVICE_ID_KEY = 'zerowaste.push.device-id';
const PUSH_TOKEN_KEY = 'zerowaste.push.expo-token';
export const NOTIFICATION_CHANNEL = 'zerowaste-general';

const getDeviceId = async () => {
  const stored = await SecureStore.getItemAsync(DEVICE_ID_KEY);
  if (stored) return stored;
  const generated = `device-${Platform.OS}-${Date.now()}-${Math.random().toString(36).slice(2, 12)}`;
  await SecureStore.setItemAsync(DEVICE_ID_KEY, generated);
  return generated;
};

export const configureNotificationChannel = async () => {
  if (!NativeNotifications || Platform.OS !== 'android') return;
  await Notifications.setNotificationChannelAsync(NOTIFICATION_CHANNEL, {
    name: 'ZeroWaste',
    importance: Notifications.AndroidImportance.HIGH,
    vibrationPattern: [0, 180, 120, 180],
    lightColor: '#10B981',
  });
};

export const getNotificationPermission = async () => {
  if (!NativeNotifications) return 'unavailable';
  const permission = await Notifications.getPermissionsAsync();
  return permission.status;
};

export const registerPushToken = async () => {
  if (!NativeNotifications) return { status: 'unsupported', message: 'Esta Development Build todavía no incluye el módulo nativo de notificaciones.' };
  if (!Device.isDevice) return { status: 'unsupported', message: 'Las notificaciones push requieren un dispositivo físico.' };
  await configureNotificationChannel();
  let permission = await Notifications.getPermissionsAsync();
  if (permission.status !== 'granted') permission = await Notifications.requestPermissionsAsync();
  if (permission.status !== 'granted') return { status: 'denied' };
  const projectId = Constants.expoConfig?.extra?.eas?.projectId || Constants.easConfig?.projectId;
  if (!projectId) return { status: 'error', message: 'El Project ID de Expo no está disponible.' };
  const token = (await Notifications.getExpoPushTokenAsync({ projectId })).data;
  const deviceId = await getDeviceId();
  const registration = { expoPushToken: token, deviceId, platform: Platform.OS };
  try {
    await api.post('/devices/push-token', registration);
  } catch (error) {
    if (error.response?.status !== 409) throw error;
    await api.post('/devices/push-token', registration);
  }
  await SecureStore.setItemAsync(PUSH_TOKEN_KEY, token);
  return { status: 'granted' };
};

export const getPushRegistrationStatus = async () => {
  const permission = await getNotificationPermission();
  if (!NativeNotifications) return { nativeAvailable: false, permission, registered: false, activeDevices: 0, lastError: null };
  const { data } = await api.get('/devices/push-status');
  return {
    nativeAvailable: true,
    permission,
    registered: Boolean(data?.registered),
    activeDevices: Math.max(0, Number(data?.active_devices) || 0),
    lastError: typeof data?.last_error === 'string' ? data.last_error : null,
    lastSeenAt: data?.last_seen_at || null,
  };
};

export const unregisterPushToken = async () => {
  const expoPushToken = await SecureStore.getItemAsync(PUSH_TOKEN_KEY);
  if (!expoPushToken) return;
  const deviceId = await getDeviceId();
  try {
    await api.delete('/devices/push-token', { data: { expoPushToken, deviceId, platform: Platform.OS } });
    await SecureStore.deleteItemAsync(PUSH_TOKEN_KEY);
  } catch {
    // Keep the local token so a later authenticated logout can retry deactivation.
  }
};

const integerId = (value) => {
  const normalized = String(value ?? '');
  return /^\d+$/.test(normalized) ? normalized : null;
};

const safeSlug = (value) => {
  const normalized = String(value ?? '');
  return /^[a-z0-9][a-z0-9-]{0,119}$/i.test(normalized) ? normalized : null;
};

export const notificationTarget = (data = {}) => {
  const type = String(data.type || '');
  if (['post_comment', 'comment_reply', 'post_like'].includes(type)) {
    const id = integerId(data.postId || data.entityId);
    if (!id) return null;
    return { name: 'PostDetail', params: { id, focusComments: Boolean(data.openComments), highlightCommentId: integerId(data.highlightCommentId) } };
  }
  if (type === 'article_published') {
    const articleId = safeSlug(data.entityId);
    return articleId ? { name: 'ArticleDetail', params: { articleId } } : null;
  }
  if (type === 'news_published') {
    const articleId = safeSlug(data.entityId);
    return articleId ? { name: 'NewsDetail', params: { articleId } } : null;
  }
  if (type.startsWith('collection_')) {
    const collectionId = integerId(data.entityId);
    return { name: 'MisRecolecciones', params: collectionId ? { collectionId } : undefined };
  }
  if (type === 'reward_status') return { name: 'MyRedemptions' };
  if (type === 'points_earned') return { name: 'PointsHistory' };
  return null;
};

export { Notifications };
