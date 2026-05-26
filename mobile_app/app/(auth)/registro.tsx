import React, { useState } from 'react';
import { View, Text, TextInput, TouchableOpacity, SafeAreaView, KeyboardAvoidingView, Platform, ScrollView, Image } from 'react-native';
import { MaterialIcons, FontAwesome } from '@expo/vector-icons';
import { useRouter } from 'expo-router';
import Constants from 'expo-constants';

export default function Registro() {
  const [nombre, setNombre] = useState('');
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [showPassword, setShowPassword] = useState(false);
  const [privacyAccepted, setPrivacyAccepted] = useState(false);
  const router = useRouter();

  const handleRegister = () => {
    // Add logic here later
    router.replace('/(tabs)');
  };

  return (
    <SafeAreaView className="flex-1 bg-[#F0FDF4] dark:bg-[#062C25]">
      <KeyboardAvoidingView 
        behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
        className="flex-1"
      >
        <ScrollView contentContainerStyle={{ flexGrow: 1, padding: 24, paddingTop: Constants.statusBarHeight + 20 }}>
          
          <TouchableOpacity 
            onPress={() => router.back()} 
            className="w-10 h-10 bg-white dark:bg-emerald-900/30 rounded-full items-center justify-center mb-6 shadow-sm"
          >
            <MaterialIcons name="arrow-back" size={24} color="#064E3B" />
          </TouchableOpacity>

          <View className="mb-6">
            <Text className="text-3xl font-extrabold text-[#064E3B] dark:text-white mb-2">Crea tu cuenta</Text>
            <Text className="text-gray-500 dark:text-gray-400 text-sm">Únete a nosotros para empezar a reciclar</Text>
          </View>

          {/* Profile Picture Upload Placeholder */}
          <View className="items-center mb-6">
            <TouchableOpacity className="w-24 h-24 bg-gray-100 dark:bg-emerald-900/20 rounded-full border-2 border-emerald-100 items-center justify-center overflow-hidden">
              <MaterialIcons name="photo-camera" size={32} color="#9CA3AF" />
            </TouchableOpacity>
            <Text className="text-xs text-gray-500 font-medium mt-2">Añadir foto de perfil</Text>
          </View>

          <View className="space-y-4">
            <View className="mb-4">
              <Text className="text-sm font-bold text-[#064E3B] dark:text-emerald-100 mb-2 ml-1">Nombre completo</Text>
              <View className="flex-row items-center bg-gray-50 dark:bg-emerald-900/20 rounded-xl border border-gray-200 dark:border-emerald-800 px-4 h-14">
                <MaterialIcons name="person-outline" size={20} color="#9CA3AF" />
                <TextInput
                  className="flex-1 ml-3 text-[#064E3B] dark:text-white font-medium"
                  placeholder="Tu Nombre Completo"
                  placeholderTextColor="#9CA3AF"
                  value={nombre}
                  onChangeText={setNombre}
                />
              </View>
            </View>

            <View className="mb-4">
              <Text className="text-sm font-bold text-[#064E3B] dark:text-emerald-100 mb-2 ml-1">Email</Text>
              <View className="flex-row items-center bg-gray-50 dark:bg-emerald-900/20 rounded-xl border border-gray-200 dark:border-emerald-800 px-4 h-14">
                <MaterialIcons name="mail-outline" size={20} color="#9CA3AF" />
                <TextInput
                  className="flex-1 ml-3 text-[#064E3B] dark:text-white font-medium"
                  placeholder="correo@ejemplo.com"
                  placeholderTextColor="#9CA3AF"
                  value={email}
                  onChangeText={setEmail}
                  keyboardType="email-address"
                  autoCapitalize="none"
                />
              </View>
            </View>

            <View className="mb-4">
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

            <View className="flex-row items-start mb-6 pr-4">
              <TouchableOpacity 
                className={`w-5 h-5 mt-0.5 rounded border items-center justify-center mr-3 ${privacyAccepted ? 'bg-[#00E096] border-[#00E096]' : 'border-gray-300'}`}
                onPress={() => setPrivacyAccepted(!privacyAccepted)}
              >
                {privacyAccepted && <MaterialIcons name="check" size={14} color="white" />}
              </TouchableOpacity>
              <Text className="text-xs text-gray-500 leading-tight flex-1">
                Al registrarte, aceptas nuestro <Text className="font-bold text-teal-700">Aviso de Privacidad</Text> y los <Text className="font-bold text-teal-700">Términos y Condiciones</Text> del servicio de ZeroWaste.
              </Text>
            </View>

            <TouchableOpacity 
              className="bg-[#00E096] active:bg-[#00C281] h-14 rounded-xl items-center justify-center shadow-md shadow-emerald-700/25 mb-4"
              onPress={handleRegister}
            >
              <Text className="text-white font-bold text-lg">Registrarse</Text>
            </TouchableOpacity>

            <View className="flex-row items-center mb-4">
              <View className="flex-1 h-[1px] bg-gray-200 dark:bg-emerald-800/60" />
              <Text className="mx-4 text-gray-400 text-xs font-bold">O</Text>
              <View className="flex-1 h-[1px] bg-gray-200 dark:bg-emerald-800/60" />
            </View>

            <TouchableOpacity 
              className="flex-row items-center justify-center bg-white dark:bg-emerald-900/20 h-14 rounded-xl border border-gray-200 dark:border-emerald-800/60 shadow-sm mb-6"
            >
              <FontAwesome name="google" size={20} color="#4285F4" />
              <Text className="ml-3 text-gray-700 dark:text-gray-200 font-bold text-sm">Continuar con Google</Text>
            </TouchableOpacity>
            
            <View className="flex-row justify-center pb-6">
              <Text className="text-gray-600 dark:text-gray-400 text-sm">¿Ya tienes cuenta? </Text>
              <TouchableOpacity onPress={() => router.push('/(auth)/login')}>
                <Text className="text-[#064E3B] dark:text-emerald-200 font-bold text-sm">Inicia sesión</Text>
              </TouchableOpacity>
            </View>
            
          </View>
        </ScrollView>
      </KeyboardAvoidingView>
    </SafeAreaView>
  );
}
