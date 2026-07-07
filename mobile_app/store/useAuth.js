import { create } from 'zustand';
import * as SecureStore from 'expo-secure-store';



export const useAuth = create((set) => ({
  user: null,
  token: null,
  isLoading: true,
  login: async (user, token) => {
    await SecureStore.setItemAsync('token', token);
    await SecureStore.setItemAsync('user', JSON.stringify(user));
    set({ user, token });
  },
  logout: async () => {
    await SecureStore.deleteItemAsync('token');
    await SecureStore.deleteItemAsync('user');
    set({ user: null, token: null });
  },
  restoreToken: async () => {
    try {
      const token = await SecureStore.getItemAsync('token');
      const userStr = await SecureStore.getItemAsync('user');
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