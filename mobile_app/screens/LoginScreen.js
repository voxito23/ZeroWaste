import React, { useEffect, useState } from 'react';
import { View, Text, KeyboardAvoidingView, Platform, ScrollView, Alert, Image, TouchableOpacity, TouchableWithoutFeedback, Keyboard, Modal, ActivityIndicator, Linking, TextInput } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { StatusBar } from 'expo-status-bar';
import { Mail, Lock, Eye, EyeOff, Check } from 'lucide-react-native';
import CustomInput from '../components/ui/CustomInput';
import CustomButton from '../components/ui/CustomButton';
import { api } from '../api/axios';
import { useAuth } from '../store/useAuth';
import Svg, { Path } from 'react-native-svg';

export default function LoginScreen({ navigation }) {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [loading, setLoading] = useState(false);
  const [showPassword, setShowPassword] = useState(false);
  const [rememberMe, setRememberMe] = useState(false);
  const [retryAfter, setRetryAfter] = useState(0);
  const [googleConfirmVisible, setGoogleConfirmVisible] = useState(false);
  const [googleLinkVisible, setGoogleLinkVisible] = useState(false);
  const [googleLoading, setGoogleLoading] = useState(false);
  const [googleHandoff, setGoogleHandoff] = useState('');
  const [linkPassword, setLinkPassword] = useState('');
  
  const { login } = useAuth();

  useEffect(() => {
    if (retryAfter <= 0) return undefined;
    const timer = setInterval(() => {
      setRetryAfter((seconds) => Math.max(seconds - 1, 0));
    }, 1000);
    return () => clearInterval(timer);
  }, [retryAfter > 0]);

  useEffect(() => {
    const handleUrl = ({ url }) => {
      if (!url?.startsWith('zerowaste://auth/google')) return;
      const code = decodeURIComponent((url.match(/[?&]code=([^&]+)/) || [])[1] || '');
      const oauthError = (url.match(/[?&]error=([^&]+)/) || [])[1];
      if (oauthError || !code) {
        setGoogleLoading(false);
        Alert.alert('Acceso cancelado', 'No se completó el inicio de sesión con Google.');
        return;
      }
      void completeGoogle(code);
    };
    const subscription = Linking.addEventListener('url', handleUrl);
    Linking.getInitialURL().then((url) => { if (url) handleUrl({ url }); });
    return () => subscription.remove();
  }, []);

  const completeGoogle = async (code) => {
    setGoogleLoading(true);
    try {
      const { data } = await api.post('/auth/google/complete', { code });
      if (data.link_required) {
        setGoogleHandoff(code);
        setGoogleLinkVisible(true);
        return;
      }
      if (data.success && data.access_token) await login(data.user, data.access_token);
    } catch (error) {
      Alert.alert('No fue posible continuar', error.response?.data?.detail || 'Revisa tu conexión e inténtalo nuevamente.');
    } finally {
      setGoogleLoading(false);
    }
  };

  const startGoogle = async () => {
    setGoogleConfirmVisible(false);
    setGoogleLoading(true);
    try {
      const { data } = await api.post('/auth/google/start');
      if (!data.authorization_url?.startsWith('https://accounts.google.com/')) throw new Error('invalid_auth_url');
      await Linking.openURL(data.authorization_url);
    } catch (error) {
      setGoogleLoading(false);
      Alert.alert('Google no está disponible', error.response?.data?.detail || 'No fue posible abrir el acceso seguro de Google.');
    }
  };

  const linkGoogle = async () => {
    if (!linkPassword || googleLoading) return;
    setGoogleLoading(true);
    try {
      const { data } = await api.post('/auth/google/link', { code: googleHandoff, password: linkPassword });
      setGoogleLinkVisible(false);
      setLinkPassword('');
      if (data.success && data.access_token) await login(data.user, data.access_token);
    } catch (error) {
      Alert.alert('No fue posible enlazar', error.response?.data?.detail || 'Revisa la contraseña de tu cuenta ZeroWaste.');
    } finally {
      setGoogleLoading(false);
    }
  };

  const resendVerification = async () => {
    try {
      await api.post('/auth/email/reenviar', { email });
      Alert.alert('Correo solicitado', 'Si la cuenta requiere verificación, recibirás un correo en unos minutos.');
    } catch (error) {
      Alert.alert('No fue posible enviar', error.response?.data?.detail || 'Inténtalo nuevamente más tarde.');
    }
  };

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
      if (error.response?.status === 403 && String(error.response?.data?.detail || '').includes('Verifica tu correo')) {
        Alert.alert('Verificación requerida', 'Verifica tu correo antes de iniciar sesión.', [{ text: 'Cancelar', style: 'cancel' }, { text: 'Enviar un nuevo correo', onPress: resendVerification }]);
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

        <TouchableOpacity disabled={googleLoading} onPress={() => setGoogleConfirmVisible(true)} accessibilityRole="button" accessibilityLabel="Continuar con Google" className="h-14 flex-row items-center justify-center rounded-xl border border-gray-200 bg-white disabled:opacity-60">
          {googleLoading ? <ActivityIndicator color="#374151" /> : <><GoogleMark /><Text className="ml-3 text-base font-bold text-gray-800">Continuar con Google</Text></>}
        </TouchableOpacity>
        <View className="my-6 flex-row items-center"><View className="h-px flex-1 bg-gray-200" /><Text className="mx-4 text-sm font-medium text-gray-400">o continúa con tu correo</Text><View className="h-px flex-1 bg-gray-200" /></View>

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

      <Modal visible={googleConfirmVisible} transparent animationType="slide" onRequestClose={() => setGoogleConfirmVisible(false)}><View className="flex-1 justify-end bg-black/40"><View className="rounded-t-3xl bg-white px-6 pb-8 pt-6"><View className="items-center"><GoogleMark size={34} /><Text className="mt-4 text-xl font-black text-gray-900">Inicia sesión con tu cuenta de Google.</Text><Text className="mt-2 text-center leading-6 text-gray-500">Abriremos el selector seguro de Google en el navegador del sistema. ZeroWaste nunca verá tu contraseña de Google.</Text></View><TouchableOpacity onPress={startGoogle} className="mt-7 h-14 items-center justify-center rounded-xl bg-emerald-700"><Text className="font-black text-white">Continuar con Google</Text></TouchableOpacity><TouchableOpacity onPress={() => setGoogleConfirmVisible(false)} className="mt-3 h-12 items-center justify-center"><Text className="font-bold text-gray-600">Cancelar</Text></TouchableOpacity></View></View></Modal>

      <Modal visible={googleLinkVisible} transparent animationType="slide" onRequestClose={() => setGoogleLinkVisible(false)}><View className="flex-1 justify-end bg-black/40"><View className="rounded-t-3xl bg-white px-6 pb-8 pt-6"><Text className="text-xl font-black text-gray-900">Enlazar cuenta existente</Text><Text className="mt-2 leading-6 text-gray-500">Ya existe una cuenta ZeroWaste con este correo. Confirma tu contraseña de ZeroWaste; no escribas aquí tu contraseña de Google.</Text><TextInput value={linkPassword} onChangeText={setLinkPassword} secureTextEntry placeholder="Contraseña de ZeroWaste" className="mt-5 h-14 rounded-xl border border-gray-200 px-4 text-gray-900" /><TouchableOpacity disabled={!linkPassword || googleLoading} onPress={linkGoogle} className="mt-5 h-14 items-center justify-center rounded-xl bg-emerald-700 disabled:opacity-50">{googleLoading ? <ActivityIndicator color="white" /> : <Text className="font-black text-white">Confirmar y enlazar</Text>}</TouchableOpacity><TouchableOpacity onPress={() => { setGoogleLinkVisible(false); setLinkPassword(''); }} className="mt-3 h-12 items-center justify-center"><Text className="font-bold text-gray-600">Cancelar</Text></TouchableOpacity></View></View></Modal>
    </SafeAreaView>
  );
}

function GoogleMark({ size = 22 }) {
  return <Svg width={size} height={size} viewBox="0 0 24 24" accessibilityLabel="Google"><Path fill="#4285F4" d="M21.35 12.2c0-.74-.07-1.45-.19-2.13H12v4.03h5.24a4.48 4.48 0 0 1-1.94 2.94v2.62h3.14c1.84-1.69 2.91-4.19 2.91-7.46Z"/><Path fill="#34A853" d="M12 21.7c2.62 0 4.82-.87 6.43-2.35l-3.14-2.62c-.87.58-1.98.93-3.29.93-2.53 0-4.67-1.71-5.44-4.01H3.31v2.7A9.71 9.71 0 0 0 12 21.7Z"/><Path fill="#FBBC05" d="M6.56 13.65A5.83 5.83 0 0 1 6.26 12c0-.57.1-1.13.3-1.65v-2.7H3.31A9.7 9.7 0 0 0 2.3 12c0 1.56.37 3.03 1.01 4.35l3.25-2.7Z"/><Path fill="#EA4335" d="M12 6.34c1.43 0 2.71.49 3.72 1.45l2.79-2.79A9.36 9.36 0 0 0 12 2.3a9.71 9.71 0 0 0-8.69 5.35l3.25 2.7C7.33 8.05 9.47 6.34 12 6.34Z"/></Svg>;
}
