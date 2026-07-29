import axios from 'axios';
import * as SecureStore from 'expo-secure-store';
import { Platform } from 'react-native';

// For Android Emulator localhost is 10.0.2.2. For iOS Simulator it is localhost.
const getFastApiUrl = () => {
  if (process.env.EXPO_PUBLIC_API_URL) {
    return process.env.EXPO_PUBLIC_API_URL;
  }
  // Caddy proxy routes /api to fast_api:6000
  return Platform.OS === 'android' ? 'http://10.0.2.2/api' : 'http://localhost/api';
};

const getLaravelUrl = () => {
  if (process.env.EXPO_PUBLIC_LARAVEL_URL) {
    return process.env.EXPO_PUBLIC_LARAVEL_URL;
  }
  // Caddy proxy routes /zw-interno to laravel admin
  return Platform.OS === 'android' ? 'http://10.0.2.2/zw-interno' : 'http://localhost/zw-interno';
};

export const api = axios.create({
  baseURL: getFastApiUrl(),
  headers: {
    'Content-Type': 'application/json',
    'X-API-Key': process.env.EXPO_PUBLIC_API_KEY || 'zw_mobile_secret_key_2026',
  },
});

export const laravelApi = axios.create({
  baseURL: getLaravelUrl(),
  headers: {
    'Content-Type': 'application/json',
  },
});

api.interceptors.request.use(async (config) => {
  try {
    const apiKey = process.env.EXPO_PUBLIC_API_KEY || 'zw_mobile_secret_key_2026';
    if (apiKey) {
      config.headers['X-API-Key'] = apiKey;
    }

    const token = await SecureStore.getItemAsync('token');
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
  } catch (error) {
    console.error('Error getting token', error);
  }
  return config;
});

laravelApi.interceptors.request.use(async (config) => {
  try {
    const token = await SecureStore.getItemAsync('token');
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
  } catch (error) {
    console.error('Error getting token', error);
  }
  return config;
});

api.interceptors.response.use(
  (response) => response,
  (error) => {
    // Handle global errors like 401 Unauthorized
    if (error.response && error.response.status === 401) {
      // Trigger logout or token refresh here
    }
    return Promise.reject(error);
  }
);