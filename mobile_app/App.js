import './global.css';
import { StatusBar } from 'expo-status-bar';
import { SafeAreaProvider } from 'react-native-safe-area-context';
import AppNavigator from './navigation/AppNavigator';
import { useState } from 'react';
import AnimatedSplashScreen from './screens/AnimatedSplashScreen';
import { useFonts, Outfit_400Regular, Outfit_500Medium, Outfit_600SemiBold, Outfit_700Bold } from '@expo-google-fonts/outfit';

export default function App() {
  const [showSplash, setShowSplash] = useState(true);
  
  const [fontsLoaded] = useFonts({
    Outfit_400Regular,
    Outfit_500Medium,
    Outfit_600SemiBold,
    Outfit_700Bold,
  });

  if (!fontsLoaded) {
    return null;
  }

  return (
    <SafeAreaProvider>
      {showSplash ? (
        <AnimatedSplashScreen onFinish={() => setShowSplash(false)} />
      ) : (
        <AppNavigator />
      )}
      <StatusBar style="auto" />
    </SafeAreaProvider>
  );
}