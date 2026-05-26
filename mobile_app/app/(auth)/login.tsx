import React, { useState } from 'react';
import { View, Text, TextInput, TouchableOpacity, Image, SafeAreaView, KeyboardAvoidingView, Platform, ScrollView, ActivityIndicator, Alert } from 'react-native';
import { MaterialIcons, FontAwesome } from '@expo/vector-icons';
import { useRouter } from 'expo-router';
import Constants from 'expo-constants';
import { useAuth } from '../context/AuthContext';
import { API_URL } from '../../config';

export default function Login() {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [showPassword, setShowPassword] = useState(false);
  const [rememberMe, setRememberMe] = useState(false);
  const [loading, setLoading] = useState(false);
  const router = useRouter();
  const { login } = useAuth();

  const handleLogin = async () => {
    if (!email || !password) {
      Alert.alert('Error', 'Por favor ingresa tu email y contraseña.');
      return;
    }
    
    setLoading(true);
    try {
      const response = await fetch(`${API_URL}/auth/login`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({ email, password })
      });
      
      const data = await response.json();
      
      if (response.ok && data.success) {
        await login(data.token, data.user);
        // InitialLayout will auto-redirect, or we can force:
        router.replace('/(tabs)');
      } else {
        Alert.alert('Error', data.error || 'Credenciales inválidas');
      }
    } catch (error) {
      console.error(error);
      Alert.alert('Error de conexión', 'No se pudo conectar al servidor. Verifica que Flask esté corriendo y la IP en config.ts sea correcta.');
    } finally {
      setLoading(false);
    }
  };

  return (
    <SafeAreaView className="flex-1 bg-[#F0FDF4] dark:bg-[#062C25]">
      <KeyboardAvoidingView 
        behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
        className="flex-1"
      >
        <ScrollView contentContainerStyle={{ flexGrow: 1, justifyContent: 'center', padding: 24, paddingTop: Constants.statusBarHeight + 20 }}>
          {/* Logo Section */}
          <View className="items-center mb-8">
            <View className="p-4 rounded-full bg-white dark:bg-emerald-900/30 shadow-sm mb-4">
              <Image 
                source={require('../../assets/images/logo.png')} 
                className="w-16 h-16"
                resizeMode="contain"
              />
            </View>
            <Text className="text-3xl font-extrabold text-[#064E3B] dark:text-white mb-2 text-center">Bienvenido de nuevo</Text>
            <Text className="text-gray-500 dark:text-gray-400 text-sm text-center">Ingresa tus credenciales para continuar</Text>
          </View>

          {/* Form Section */}
          <View className="space-y-4">
            <View className="mb-4">
              <Text className="text-sm font-bold text-[#064E3B] dark:text-emerald-100 mb-2 ml-1">Email</Text>
              <View className="flex-row items-center bg-gray-50 dark:bg-emerald-900/20 rounded-xl border border-gray-200 dark:border-emerald-800 px-4 h-14">
                <MaterialIcons name="mail-outline" size={20} color="#9CA3AF" />
                <TextInput
                  className="flex-1 ml-3 text-[#064E3B] dark:text-white font-medium"
                  placeholder="nombre@ejemplo.com"
                  placeholderTextColor="#9CA3AF"
                  value={email}
                  onChangeText={setEmail}
                  keyboardType="email-address"
                  autoCapitalize="none"
                />
              </View>
            </View>

            <View className="mb-2">
              <Text className="text-sm font-bold text-[#064E3B] dark:text-emerald-100 mb-2 ml-1">Contraseña</Text>
              <View className="flex-row items-center bg-gray-50 dark:bg-emerald-900/20 rounded-xl border border-gray-200 dark:border-emerald-800 px-4 h-14">
                <MaterialIcons name="lock-outline" size={20} color="#9CA3AF" />
                <TextInput
                  className="flex-1 ml-3 text-[#064E3B] dark:text-white font-medium"
                  placeholder="••••••••"
                  placeholderTextColor="#9CA3AF"
                  value={password}
                  onChangeText={setPassword}
                  secureTextEntry={!showPassword}
                />
                <TouchableOpacity onPress={() => setShowPassword(!showPassword)} className="p-2 -mr-2">
                  <MaterialIcons name={showPassword ? "visibility-off" : "visibility"} size={20} color="#9CA3AF" />
                </TouchableOpacity>
              </View>
            </View>

            <View className="flex-row items-center justify-between mb-6">
              <TouchableOpacity 
                className="flex-row items-center" 
                onPress={() => setRememberMe(!rememberMe)}
              >
                <View className={`w-5 h-5 rounded border items-center justify-center mr-2 ${rememberMe ? 'bg-[#00E096] border-[#00E096]' : 'border-gray-300'}`}>
                  {rememberMe && <MaterialIcons name="check" size={14} color="white" />}
                </View>
                <Text className="text-sm text-gray-600 dark:text-gray-400">Recordarme</Text>
              </TouchableOpacity>
              <TouchableOpacity>
                <Text className="text-sm font-bold text-[#00E096]">¿Olvidaste tu contraseña?</Text>
              </TouchableOpacity>
            </View>

            <TouchableOpacity 
              className={`bg-[#00E096] active:bg-[#00C281] h-14 rounded-xl items-center justify-center shadow-md shadow-emerald-700/25 mb-4 ${loading ? 'opacity-70' : ''}`}
              onPress={handleLogin}
              disabled={loading}
            >
              {loading ? (
                <ActivityIndicator color="white" />
              ) : (
                <Text className="text-white font-bold text-lg">Iniciar sesión</Text>
              )}
            </TouchableOpacity>

            <View className="flex-row items-center mb-4">
              <View className="flex-1 h-[1px] bg-gray-200 dark:bg-emerald-800/60" />
              <Text className="mx-4 text-gray-400 text-xs font-bold">O</Text>
              <View className="flex-1 h-[1px] bg-gray-200 dark:bg-emerald-800/60" />
            </View>

            <TouchableOpacity 
              className="flex-row items-center justify-center bg-white dark:bg-emerald-900/20 h-14 rounded-xl border border-gray-200 dark:border-emerald-800/60 shadow-sm"
            >
              <FontAwesome name="google" size={20} color="#4285F4" />
              <Text className="ml-3 text-gray-700 dark:text-gray-200 font-bold text-sm">Continuar con Google</Text>
            </TouchableOpacity>

            <View className="flex-row justify-center mt-6">
              <Text className="text-gray-600 dark:text-gray-400 text-sm">¿No tienes cuenta? </Text>
              <TouchableOpacity onPress={() => router.push('/(auth)/registro')}>
                <Text className="text-[#064E3B] dark:text-emerald-200 font-bold text-sm">Regístrate</Text>
              </TouchableOpacity>
            </View>
          </View>
        </ScrollView>
      </KeyboardAvoidingView>
    </SafeAreaView>
  );
}
