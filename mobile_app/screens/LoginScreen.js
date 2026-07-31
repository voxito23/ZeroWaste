import React, { useEffect, useState } from 'react';
import { View, Text, KeyboardAvoidingView, Platform, ScrollView, Alert, Image, TouchableOpacity, TouchableWithoutFeedback, Keyboard } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { StatusBar } from 'expo-status-bar';
import { Mail, Lock, Eye, EyeOff, Check } from 'lucide-react-native';
import CustomInput from '../components/ui/CustomInput';
import CustomButton from '../components/ui/CustomButton';
import { api } from '../api/axios';
import { useAuth } from '../store/useAuth';

export default function LoginScreen({ navigation }) {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [loading, setLoading] = useState(false);
  const [showPassword, setShowPassword] = useState(false);
  const [rememberMe, setRememberMe] = useState(false);
  const [retryAfter, setRetryAfter] = useState(0);
  
  const { login } = useAuth();

  useEffect(() => {
    if (retryAfter <= 0) return undefined;
    const timer = setInterval(() => {
      setRetryAfter((seconds) => Math.max(seconds - 1, 0));
    }, 1000);
    return () => clearInterval(timer);
  }, [retryAfter > 0]);

  const handleLogin = async () => {
    if (loading || retryAfter > 0) return;
    if (!email || !password) {
      Alert.alert('Error', 'Completa todos los campos');
      return;
    }

    // Validación básica de email
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
      Alert.alert('Error', 'Ingresa un correo electrónico válido');
      return;
    }
    
    setLoading(true);
    try {
      const response = await api.post('/auth/mobile/login', { email, password });
      if (response.data.success && response.data.access_token) {
        await login(response.data.user, response.data.access_token);
      } else {
        Alert.alert('Error', response.data.error || 'Credenciales inválidas');
      }
    } catch (error) {
      setPassword('');
      if (error.response?.status === 429) {
        const bodyRetry = Number(error.response?.data?.retry_after);
        const headerRetry = Number(error.response?.headers?.['retry-after']);
        const seconds = Math.max(bodyRetry || headerRetry || 60, 1);
        setRetryAfter(seconds);
        Alert.alert('Espera un momento', 'Demasiados intentos. Espera un minuto antes de volver a intentarlo.');
        return;
      }
      if (error.response?.data?.need_verification) {
        Alert.alert('Verificación requerida', 'Tu cuenta requiere verificación previa por el administrador.');
        return;
      }
      const msg = error.userMessage || error.response?.data?.detail || 'Error de conexión. Verifica tu internet o el estado del servidor.';
      Alert.alert('Error', msg);
    } finally {
      setLoading(false);
    }
  };

  return (
    <SafeAreaView style={{ flex: 1, backgroundColor: 'white' }}>
      <StatusBar style="dark" />
      <KeyboardAvoidingView 
        behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
        style={{ flex: 1 }}
      >
        <TouchableWithoutFeedback onPress={Keyboard.dismiss}>
          <ScrollView 
            contentContainerStyle={{ flexGrow: 1, justifyContent: 'center' }}
            keyboardShouldPersistTaps="handled"
            showsVerticalScrollIndicator={false}
            bounces={false}
          >
            <View style={{ flex: 1, justifyContent: 'center', padding: 24 }}>
        
        {/* Header - Logo & Title */}
        <View className="mb-8 items-center mt-4">
          <View className="bg-[#E8F5E9] p-3 rounded-full mb-6">
            <Image 
              source={require('../assets/images/logo.png')} 
              style={{ width: 40, height: 40 }}
              resizeMode="contain"
            />
          </View>
          <Text className="text-[28px] font-extrabold text-primary mb-2 text-center">Bienvenido de nuevo</Text>
          <Text className="text-gray-500 text-base text-center">Ingresa tus credenciales para continuar</Text>
        </View>

        {/* Inputs */}
        <View className="mb-2">
          <CustomInput 
            label="Email" 
            placeholder="nombre@ejemplo.com"
            keyboardType="email-address"
            autoCapitalize="none"
            value={email}
            onChangeText={setEmail}
            leftIcon={<Mail color="#9CA3AF" size={20} />}
          />
          <CustomInput 
            label="Contraseña" 
            placeholder="••••••••"
            secureTextEntry={!showPassword}
            value={password}
            onChangeText={setPassword}
            leftIcon={<Lock color="#9CA3AF" size={20} />}
            rightIcon={showPassword ? <EyeOff color="#9CA3AF" size={20} /> : <Eye color="#9CA3AF" size={20} />}
            onRightIconPress={() => setShowPassword(!showPassword)}
          />
        </View>

        {/* Options: Remember me & Forgot password */}
        <View className="flex-row justify-between items-center mb-8 px-1">
          <TouchableOpacity 
            className="flex-row items-center" 
            onPress={() => setRememberMe(!rememberMe)}
            activeOpacity={0.7}
          >
            <View className={`w-5 h-5 rounded-md border items-center justify-center mr-2 ${rememberMe ? 'bg-primary border-primary' : 'bg-gray-50 border-gray-300'}`}>
              {rememberMe && <Check color="white" size={14} strokeWidth={3.5} />}
            </View>
            <Text className="text-gray-600 font-medium">Recordarme</Text>
          </TouchableOpacity>
          <TouchableOpacity>
            <Text className="text-primary font-bold">¿Olvidaste tu contraseña?</Text>
          </TouchableOpacity>
        </View>
        
        {/* Login Button */}
        <CustomButton 
          title={retryAfter > 0 ? `Espera ${retryAfter}s` : 'Iniciar sesión'}
          onPress={handleLogin} 
          loading={loading}
          disabled={loading || retryAfter > 0}
          className="rounded-xl py-4"
        />

        {/* Footer */}
        <View className="flex-row justify-center mt-auto mb-4">
          <Text className="text-gray-500 font-medium">¿No tienes cuenta? </Text>
          <TouchableOpacity onPress={() => navigation.navigate('Register')}>
            <Text className="text-primary font-bold">Regístrate</Text>
          </TouchableOpacity>
        </View>

            </View>
          </ScrollView>
        </TouchableWithoutFeedback>
      </KeyboardAvoidingView>
    </SafeAreaView>
  );
}
