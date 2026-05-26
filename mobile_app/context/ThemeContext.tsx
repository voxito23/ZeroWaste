import React, { createContext, useState, useEffect, useContext, ReactNode, useCallback } from 'react';
import { useColorScheme as useSystemColorScheme, Appearance } from 'react-native';
import * as SecureStore from 'expo-secure-store';
import { Colors, ThemeColors } from '../constants/Colors';

type ThemeMode = 'light' | 'dark' | 'system';

interface ThemeContextData {
  /** El modo de tema elegido por el usuario: light, dark, o system */
  mode: ThemeMode;
  /** El esquema de colores efectivo resuelto (siempre 'light' o 'dark') */
  colorScheme: 'light' | 'dark';
  /** Objeto con todos los colores para el tema actual */
  colors: ThemeColors;
  /** true si el tema efectivo es oscuro */
  isDark: boolean;
  /** Cambia el modo de tema */
  setMode: (mode: ThemeMode) => void;
  /** Alterna entre light y dark (ignora system) */
  toggleTheme: () => void;
}

const THEME_STORAGE_KEY = 'zerowaste_theme_mode';

export const ThemeContext = createContext<ThemeContextData>({} as ThemeContextData);

export const ThemeProvider = ({ children }: { children: ReactNode }) => {
  const systemScheme = useSystemColorScheme();
  const [mode, setModeState] = useState<ThemeMode>('system');
  const [isLoaded, setIsLoaded] = useState(false);

  // Cargar preferencia guardada
  useEffect(() => {
    const load = async () => {
      try {
        const saved = await SecureStore.getItemAsync(THEME_STORAGE_KEY);
        if (saved === 'light' || saved === 'dark' || saved === 'system') {
          setModeState(saved);
        }
      } catch (e) {
        console.error('Error loading theme preference:', e);
      } finally {
        setIsLoaded(true);
      }
    };
    load();
  }, []);

  // Resolver el esquema efectivo
  const colorScheme: 'light' | 'dark' =
    mode === 'system'
      ? (systemScheme ?? 'light')
      : mode;

  const isDark = colorScheme === 'dark';
  const colors = isDark ? Colors.dark : Colors.light;

  const setMode = useCallback(async (newMode: ThemeMode) => {
    setModeState(newMode);
    try {
      await SecureStore.setItemAsync(THEME_STORAGE_KEY, newMode);
    } catch (e) {
      console.error('Error saving theme preference:', e);
    }
  }, []);

  const toggleTheme = useCallback(() => {
    const next = isDark ? 'light' : 'dark';
    setMode(next);
  }, [isDark, setMode]);

  if (!isLoaded) return null;

  return (
    <ThemeContext.Provider value={{ mode, colorScheme, colors, isDark, setMode, toggleTheme }}>
      {children}
    </ThemeContext.Provider>
  );
};

export const useTheme = () => useContext(ThemeContext);
