import '../global.css';
import React, { useEffect, useState } from 'react';
import { Stack, useRouter, useSegments } from 'expo-router';
import { StatusBar } from 'expo-status-bar';
import { SafeAreaProvider } from 'react-native-safe-area-context';
import { View, ActivityIndicator } from 'react-native';
import {
  useFonts,
  Outfit_400Regular,
  Outfit_500Medium,
  Outfit_600SemiBold,
  Outfit_700Bold,
} from '@expo-google-fonts/outfit';
import { useAuth } from '../store/useAuth';
import AnimatedSplashScreen from '../screens/AnimatedSplashScreen';

export default function RootLayout() {
  const [showSplash, setShowSplash] = useState(true);
  const { token, isLoading, restoreToken } = useAuth();
  const segments = useSegments();
  const router = useRouter();

  const [fontsLoaded] = useFonts({
    Outfit_400Regular,
    Outfit_500Medium,
    Outfit_600SemiBold,
    Outfit_700Bold,
  });

  useEffect(() => {
    restoreToken();
  }, []);

  useEffect(() => {
    if (isLoading || showSplash) return;

    const inAuthGroup = segments[0] === 'login' || segments[0] === 'register' || segments[0] === 'verify';

    if (!token && !inAuthGroup) {
      router.replace('/login');
    } else if (token && inAuthGroup) {
      router.replace('/(tabs)/home');
    }
  }, [token, isLoading, showSplash, segments]);

  if (!fontsLoaded) {
    return null;
  }

  if (showSplash) {
    return (
      <SafeAreaProvider>
        <AnimatedSplashScreen onFinish={() => setShowSplash(false)} />
        <StatusBar style="auto" />
      </SafeAreaProvider>
    );
  }

  if (isLoading) {
    return (
      <SafeAreaProvider>
        <View className="flex-1 justify-center items-center bg-background">
          <ActivityIndicator size="large" color="#064E3B" />
        </View>
        <StatusBar style="auto" />
      </SafeAreaProvider>
    );
  }

  return (
    <SafeAreaProvider>
      <Stack screenOptions={{ headerShown: false }}>
        <Stack.Screen name="(tabs)" />
        <Stack.Screen name="login" />
        <Stack.Screen name="register" />
        <Stack.Screen name="verify" />
        <Stack.Screen name="mis-recolecciones" />
        <Stack.Screen name="post-detail" />
        <Stack.Screen name="create-post" />
      </Stack>
      <StatusBar style="auto" />
    </SafeAreaProvider>
  );
}
