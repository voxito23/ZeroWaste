import './global.css';
import { StatusBar } from 'expo-status-bar';
import { initialWindowMetrics, SafeAreaProvider } from 'react-native-safe-area-context';
import AppNavigator from './navigation/AppNavigator';
import { useEffect, useState } from 'react';
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
          <AppNavigator />
        )}
        <StatusBar style="auto" />
      </ZeroWasteDialogProvider>
    </SafeAreaProvider>
  );
}
