import React, { useState } from 'react';
import { View, Text, KeyboardAvoidingView, Platform, ScrollView, Alert, Image, TouchableOpacity, TouchableWithoutFeedback, Keyboard } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { StatusBar } from 'expo-status-bar';
import Svg, { Path } from 'react-native-svg';
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
  
  const { login } = useAuth();

  const handleLogin = async () => {
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
          title="Iniciar sesión" 
          onPress={handleLogin} 
          loading={loading}
          className="rounded-xl py-4"
        />

        {/* Divider */}
        <View className="flex-row items-center my-8">
          <View className="flex-1 h-[1px] bg-gray-200" />
          <Text className="text-gray-400 font-bold px-4">o</Text>
          <View className="flex-1 h-[1px] bg-gray-200" />
        </View>

        {/* Google Button */}
        <TouchableOpacity 
          className="flex-row items-center justify-center bg-white border border-gray-200 rounded-xl py-3.5 mb-8 shadow-sm"
          activeOpacity={0.7}
          onPress={() => Alert.alert('Próximamente', 'Inicio de sesión con Google estará disponible pronto.')}
        >
          <Svg width="24" height="24" viewBox="0 0 24 24" className="mr-3">
            <Path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4" />
            <Path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853" />
            <Path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05" />
            <Path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335" />
          </Svg>
          <Text className="text-[#374151] font-bold text-base">Continuar con Google</Text>
        </TouchableOpacity>

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
