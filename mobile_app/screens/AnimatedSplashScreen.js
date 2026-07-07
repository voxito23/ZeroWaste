import React, { useEffect } from 'react';
import { View, Text, StyleSheet, Dimensions, Image } from 'react-native';
import { StatusBar } from 'expo-status-bar';
import { SafeAreaView } from 'react-native-safe-area-context';
import Animated, { useSharedValue, useAnimatedStyle, withTiming, withRepeat, Easing } from 'react-native-reanimated';
import { LinearGradient } from 'expo-linear-gradient';

const { width, height } = Dimensions.get('window');

export default function AnimatedSplashScreen({ onFinish }) {
  const opacity = useSharedValue(0);
  const scale = useSharedValue(0.8);
  
  // Orbs animations
  const orb1X = useSharedValue(-100);
  const orb1Y = useSharedValue(-50);
  
  const orb2X = useSharedValue(width);
  const orb2Y = useSharedValue(height);

  useEffect(() => {
    // Logo reveal
    opacity.value = withTiming(1, { duration: 1500 });
    scale.value = withTiming(1, { duration: 1500, easing: Easing.out(Easing.exp) });

    // Orbs floating
    orb1X.value = withRepeat(withTiming(100, { duration: 4000, easing: Easing.inOut(Easing.ease) }), -1, true);
    orb1Y.value = withRepeat(withTiming(200, { duration: 5000, easing: Easing.inOut(Easing.ease) }), -1, true);

    orb2X.value = withRepeat(withTiming(width - 150, { duration: 4500, easing: Easing.inOut(Easing.ease) }), -1, true);
    orb2Y.value = withRepeat(withTiming(height - 250, { duration: 3500, easing: Easing.inOut(Easing.ease) }), -1, true);

    // Timeout to finish
    const timer = setTimeout(() => {
      // Fade out everything
      opacity.value = withTiming(0, { duration: 800 });
      setTimeout(onFinish, 800);
    }, 4200); // 4.2s + 0.8s fade out = 5s

    return () => clearTimeout(timer);
  }, [onFinish, opacity, scale, orb1X, orb1Y, orb2X, orb2Y]);

  const logoStyle = useAnimatedStyle(() => ({
    opacity: opacity.value,
    transform: [{ scale: scale.value }]
  }));

  const orb1Style = useAnimatedStyle(() => ({
    transform: [{ translateX: orb1X.value }, { translateY: orb1Y.value }]
  }));

  const orb2Style = useAnimatedStyle(() => ({
    transform: [{ translateX: orb2X.value }, { translateY: orb2Y.value }]
  }));

  return (
    <SafeAreaView style={styles.container} edges={['top', 'bottom']}>
      <StatusBar style="dark" />
      {/* Background */}
      <View style={[StyleSheet.absoluteFillObject, { backgroundColor: '#ffffff' }]} />

      {/* Floating Orbs (adjusted for white bg) */}
      <Animated.View style={[styles.orb, { backgroundColor: '#D1FAE5', width: 350, height: 350 }, orb1Style]} />
      <Animated.View style={[styles.orb, { backgroundColor: '#A7F3D0', width: 250, height: 250 }, orb2Style]} />

      {/* Glassmorphism Overlay */}
      <View style={styles.glassOverlay} />

      {/* Main Logo Content */}
      <Animated.View style={[styles.content, logoStyle]}>
        <View style={styles.iconContainer}>
          <Image 
            source={require('../assets/images/logo.png')} 
            style={{ width: 80, height: 80 }}
            resizeMode="contain"
          />
        </View>
        <Text style={styles.title}>ZeroWaste</Text>
        <Text style={styles.subtitle}>Cuidando el planeta, juntos</Text>
      </Animated.View>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    backgroundColor: '#ffffff',
  },
  orb: {
    position: 'absolute',
    borderRadius: 999,
    opacity: 0.6,
    shadowColor: '#10B981',
    shadowOffset: { width: 0, height: 0 },
    shadowOpacity: 0.5,
    shadowRadius: 50,
    elevation: 20,
  },
  glassOverlay: {
    ...StyleSheet.absoluteFillObject,
    backgroundColor: 'rgba(255, 255, 255, 0.5)', 
  },
  content: {
    alignItems: 'center',
    zIndex: 10,
  },
  iconContainer: {
    width: 110,
    height: 110,
    backgroundColor: '#E8F5E9',
    borderRadius: 55,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 24,
    borderWidth: 1,
    borderColor: '#A7F3D0',
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.1,
    shadowRadius: 10,
    elevation: 5,
  },
  title: {
    fontSize: 52,
    fontWeight: '900',
    color: '#064E3B',
    letterSpacing: 1.5,
    marginBottom: 10,
  },
  subtitle: {
    fontSize: 18,
    color: '#059669',
    fontWeight: '500',
    letterSpacing: 0.5,
  }
});
