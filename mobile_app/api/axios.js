import axios from 'axios';
import * as SecureStore from 'expo-secure-store';
import { useAuth } from '../store/useAuth';

const PRODUCTION_API_URL = 'https://www.zerowaste-qro.com/api';
const PRODUCTION_LARAVEL_URL = 'https://www.zerowaste-qro.com/zw-interno';
const REQUEST_TIMEOUT_MS = 12000;

const normalizeBaseUrl = (value, fallback) => (value || fallback).trim().replace(/\/$/, '');

export const api = axios.create({
  baseURL: normalizeBaseUrl(process.env.EXPO_PUBLIC_API_URL, PRODUCTION_API_URL),
  timeout: REQUEST_TIMEOUT_MS,
  headers: {
    'Content-Type': 'application/json',
  },
});

export const laravelApi = axios.create({
  baseURL: normalizeBaseUrl(process.env.EXPO_PUBLIC_LARAVEL_URL, PRODUCTION_LARAVEL_URL),
  timeout: REQUEST_TIMEOUT_MS,
  headers: {
    'Content-Type': 'application/json',
  },
});

api.interceptors.request.use(async (config) => {
  try {
    if (typeof FormData !== 'undefined' && config.data instanceof FormData) {
      delete config.headers['Content-Type'];
      delete config.headers['content-type'];
    }
    const token = await SecureStore.getItemAsync('token') || useAuth.getState().token;
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
    const token = await SecureStore.getItemAsync('token') || useAuth.getState().token;
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
    error.userMessage = getApiErrorMessage(error);
    const isAuthenticationRequest = error.config?.url?.startsWith('/auth/');
    const hadBearerToken = Boolean(error.config?.headers?.Authorization);
    if (error.response?.status === 401 && hadBearerToken && !isAuthenticationRequest) {
      void useAuth.getState().logout();
    }
    return Promise.reject(error);
  }
);

export function getApiErrorMessage(error) {
  if (error?.code === 'ECONNABORTED') return 'La solicitud tardó demasiado. Intenta nuevamente.';
  if (!error?.response) return 'Sin conexión con el servidor. Verifica tu Internet.';

  const status = error.response.status;
  const detail = error.response.data?.detail || error.response.data?.message || error.response.data?.error;
  const requestUrl = String(error?.config?.url || '');
  const loginRequest = requestUrl.startsWith('/auth/mobile/login') || requestUrl.startsWith('/auth/google/link');
  if (status === 401 && loginRequest) return typeof detail === 'string' ? detail : 'Usuario o contraseña incorrectos.';
  if (status === 401) return 'Tu sesión expiró. Inicia sesión nuevamente.';
  if (status === 403) return 'No tienes permiso para realizar esta acción.';
  if (status === 404) return 'No se encontró la información solicitada.';
  if (status === 409) return detail || 'La operación entra en conflicto con información existente.';
  if (status === 422) return typeof detail === 'string' ? detail : 'Revisa los datos enviados.';
  if (status >= 500) return 'El servidor no está disponible temporalmente.';
  return typeof detail === 'string' ? detail : 'No fue posible completar la solicitud.';
}
