import { create } from 'zustand';
import * as SecureStore from 'expo-secure-store';



export const useAuth = create((set) => ({
  user: null,
  token: null,
  isLoading: true,
  login: async (user, token, { persist = true } = {}) => {
    set({ user, token });
    try {
      if (persist) {
        await Promise.all([
          SecureStore.setItemAsync('token', token),
          SecureStore.setItemAsync('user', JSON.stringify(user)),
        ]);
      } else {
        await Promise.all([
          SecureStore.deleteItemAsync('token'),
          SecureStore.deleteItemAsync('user'),
        ]);
      }
    } catch (error) {
      console.error('Failed to persist session', error);
    }
  },
  updateUser: async (user) => {
    set({ user });
    try {
      if (await SecureStore.getItemAsync('token')) await SecureStore.setItemAsync('user', JSON.stringify(user));
    } catch (error) {
      console.error('Failed to persist user profile', error);
    }
  },
  logout: async () => {
    set({ user: null, token: null });
    await Promise.all([
      SecureStore.deleteItemAsync('token'),
      SecureStore.deleteItemAsync('user'),
    ]);
  },
  restoreToken: async () => {
    try {
      const [token, userStr] = await Promise.all([
        SecureStore.getItemAsync('token'),
        SecureStore.getItemAsync('user'),
      ]);
      if (token && userStr) {
        set({ token, user: JSON.parse(userStr) });
      }
    } catch (e) {
      console.error('Failed to restore token', e);
    } finally {
      set({ isLoading: false });
    }
  },
}));
