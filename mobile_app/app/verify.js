import React from 'react';
import { View, Text, TouchableOpacity } from 'react-native';
import { useRouter, useLocalSearchParams } from 'expo-router';
import { SafeAreaView } from 'react-native-safe-area-context';

export default function Verify() {
  const router = useRouter();
  const { email } = useLocalSearchParams();

  return (
    <SafeAreaView style={{ flex: 1, backgroundColor: 'white', justifyContent: 'center', alignItems: 'center', padding: 24 }}>
      <Text style={{ fontSize: 24, fontWeight: 'bold', marginBottom: 12, textAlign: 'center', color: '#064E3B' }}>
        Verificación de cuenta
      </Text>
      <Text style={{ fontSize: 16, textAlign: 'center', color: '#6B7280', marginBottom: 24 }}>
        Hemos enviado un código o instrucciones de verificación a tu correo: {email || 'registrado'}.
      </Text>
      <TouchableOpacity
        onPress={() => router.replace('/login')}
        style={{ backgroundColor: '#064E3B', paddingVertical: 14, paddingHorizontal: 28, borderRadius: 12 }}
      >
        <Text style={{ color: 'white', fontWeight: 'bold', fontSize: 16 }}>Volver a Iniciar Sesión</Text>
      </TouchableOpacity>
    </SafeAreaView>
  );
}
