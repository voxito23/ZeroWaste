import './global.css';
import { StatusBar } from 'expo-status-bar';
import { initialWindowMetrics, SafeAreaProvider, useSafeAreaInsets } from 'react-native-safe-area-context';
import AppNavigator from './navigation/AppNavigator';
import { useEffect, useState } from 'react';
import { Keyboard, Platform, View } from 'react-native';
import * as NavigationBar from 'expo-navigation-bar';
import AnimatedSplashScreen from './screens/AnimatedSplashScreen';
import { useFonts, Outfit_400Regular, Outfit_500Medium, Outfit_600SemiBold, Outfit_700Bold } from '@expo-google-fonts/outfit';
import { HAS_VALID_MAPBOX_TOKEN, initializeMapbox } from './utils/mapbox';
import { ZeroWasteDialogProvider } from './components/ui/ZeroWasteDialog';

export default function App() {
  const [showSplash, setShowSplash] = useState(true);
  
  const [fontsLoaded] = useFonts({
    Outfit_400Regular,
    Outfit_500Medium,
    Outfit_600SemiBold,
    Outfit_700Bold,
  });

  useEffect(() => {
    NavigationBar.setStyle('light');
    if (HAS_VALID_MAPBOX_TOKEN) {
      void initializeMapbox().catch(() => {
        if (typeof __DEV__ !== 'undefined' && __DEV__) {
          console.warn('[map] No fue posible inicializar el SDK de Mapbox.');
        }
      });
    }
  }, []);

  if (!fontsLoaded) {
    return null;
  }

  return (
    <SafeAreaProvider initialMetrics={initialWindowMetrics}>
      <ZeroWasteDialogProvider>
        {showSplash ? (
          <AnimatedSplashScreen onFinish={() => setShowSplash(false)} />
        ) : (
          <View className="flex-1 bg-white"><AppNavigator /></View>
        )}
        <SystemNavigationBackdrop />
        <StatusBar style="dark" />
      </ZeroWasteDialogProvider>
    </SafeAreaProvider>
  );
}

function SystemNavigationBackdrop() {
  const insets = useSafeAreaInsets();
  const [keyboardVisible, setKeyboardVisible] = useState(false);
  useEffect(() => {
    const show = Keyboard.addListener(Platform.OS === 'ios' ? 'keyboardWillShow' : 'keyboardDidShow', () => setKeyboardVisible(true));
    const hide = Keyboard.addListener(Platform.OS === 'ios' ? 'keyboardWillHide' : 'keyboardDidHide', () => setKeyboardVisible(false));
    return () => { show.remove(); hide.remove(); };
  }, []);
  if (keyboardVisible) return null;
  const height = insets.bottom > 0 ? insets.bottom : Platform.OS === 'android' ? 48 : 0;
  if (!height) return null;
  return <View pointerEvents="none" className="absolute bottom-0 left-0 right-0 bg-white" style={{ height, zIndex: 10000, elevation: 10000 }} />;
}
