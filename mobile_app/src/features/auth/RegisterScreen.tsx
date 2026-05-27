import React, { useState } from 'react';
import { View, Text, KeyboardAvoidingView, Platform, ScrollView, Alert } from 'react-native';
import CustomInput from '../../components/ui/CustomInput';
import CustomButton from '../../components/ui/CustomButton';
import { api } from '../../api/axios';
import { useAuth } from '../../store/useAuth';

export default function RegisterScreen({ navigation }: any) {
  const [nombre, setNombre] = useState('');
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [loading, setLoading] = useState(false);
  const { login } = useAuth();

  const handleRegister = async () => {
    if (!nombre || !email || !password) {
      Alert.alert('Error', 'Completa todos los campos');
      return;
    }
    
    setLoading(true);
    try {
      const response = await api.post('/auth/mobile/registro', { nombre, email, password });
      // Asumiendo que el registro de FastAPI auto loguea o devuelve éxito para redirigir
      if (response.data.success) {
        Alert.alert('Éxito', 'Cuenta creada correctamente. Inicia sesión para continuar.', [
          { text: 'OK', onPress: () => navigation.navigate('Login') }
        ]);
      } else {
        Alert.alert('Error', response.data.error || 'No se pudo crear la cuenta');
      }
    } catch (error: any) {
      const msg = error.response?.data?.detail || 'Error de conexión.';
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
          <Text className="text-3xl font-extrabold text-primary mb-2 tracking-tight">Crea tu cuenta</Text>
          <Text className="text-subtext text-base text-center">Únete a la comunidad ZeroWaste</Text>
        </View>

        <View className="bg-surface p-6 rounded-3xl shadow-lg shadow-black/5 elevation-3">
          <CustomInput 
            label="Nombre completo" 
            placeholder="Juan Pérez"
            value={nombre}
            onChangeText={setNombre}
          />
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
            placeholder="Mínimo 6 caracteres"
            secureTextEntry
            value={password}
            onChangeText={setPassword}
          />
          
          <View className="mt-4">
            <CustomButton 
              title="Registrarse" 
              onPress={handleRegister} 
              loading={loading}
            />
          </View>
        </View>

        <View className="flex-row justify-center mt-8">
          <Text className="text-subtext">¿Ya tienes cuenta? </Text>
          <Text 
            className="text-primary font-bold"
            onPress={() => navigation.navigate('Login')}
          >
            Inicia Sesión
          </Text>
        </View>
      </ScrollView>
    </KeyboardAvoidingView>
  );
}
