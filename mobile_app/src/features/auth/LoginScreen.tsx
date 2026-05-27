import React, { useState } from 'react';
import { View, Text, KeyboardAvoidingView, Platform, ScrollView, Alert } from 'react-native';
import CustomInput from '../../components/ui/CustomInput';
import CustomButton from '../../components/ui/CustomButton';
import { api } from '../../api/axios';
import { useAuth } from '../../store/useAuth';

export default function LoginScreen({ navigation }: any) {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [loading, setLoading] = useState(false);
  const { login } = useAuth();

  const handleLogin = async () => {
    if (!email || !password) {
      Alert.alert('Error', 'Completa todos los campos');
      return;
    }
    
    setLoading(true);
    try {
      const response = await api.post('/auth/mobile/login', { email, password });
      // Asumiendo que FastAPI devuelve un JWT access_token y un objeto user
      if (response.data.success && response.data.access_token) {
        await login(response.data.user, response.data.access_token);
      } else {
        Alert.alert('Error', response.data.error || 'Credenciales inválidas');
      }
    } catch (error: any) {
      const msg = error.response?.data?.detail || 'Error de conexión. Verifica tu internet o el estado del servidor.';
      Alert.alert('Error', msg);
    } finally {
      setLoading(false);
    }
  };

  return (
    <KeyboardAvoidingView 
      behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
      className="flex-1 bg-background"
    >
      <ScrollView contentContainerStyle={{ flexGrow: 1, justifyContent: 'center', padding: 24 }}>
        <View className="mb-10 items-center">
          <Text className="text-4xl font-extrabold text-primary mb-2 tracking-tight">ZeroWaste</Text>
          <Text className="text-subtext text-base text-center">Inicia sesión para continuar tu impacto ambiental</Text>
        </View>

        <View className="bg-surface p-6 rounded-3xl shadow-lg shadow-black/5 elevation-3">
          <CustomInput 
            label="Correo electrónico" 
            placeholder="ejemplo@correo.com"
            keyboardType="email-address"
            autoCapitalize="none"
            value={email}
            onChangeText={setEmail}
          />
          <CustomInput 
            label="Contraseña" 
            placeholder="••••••••"
            secureTextEntry
            value={password}
            onChangeText={setPassword}
          />
          <Text className="text-primary text-right font-medium mb-6 mt-1">¿Olvidaste tu contraseña?</Text>
          
          <CustomButton 
            title="Iniciar Sesión" 
            onPress={handleLogin} 
            loading={loading}
          />
        </View>

        <View className="flex-row justify-center mt-8">
          <Text className="text-subtext">¿No tienes cuenta? </Text>
          <Text 
            className="text-primary font-bold"
            onPress={() => navigation.navigate('Register')}
          >
            Regístrate
          </Text>
        </View>
      </ScrollView>
    </KeyboardAvoidingView>
  );
}
