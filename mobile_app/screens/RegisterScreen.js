import React, { useState } from 'react';
import { View, Text, KeyboardAvoidingView, Platform, ScrollView, Alert, Image, TouchableOpacity, Modal, TouchableWithoutFeedback, Keyboard } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { StatusBar } from 'expo-status-bar';
import { Mail, Lock, Eye, EyeOff, User, Check } from 'lucide-react-native';
import CustomInput from '../components/ui/CustomInput';
import CustomButton from '../components/ui/CustomButton';
import { api } from '../api/axios';
import Svg, { Path } from 'react-native-svg';

const getPasswordStrength = (pass) => {
  let score = 0;
  if (!pass) return 0;
  if (pass.length >= 8) score += 1;
  if (/[A-Z]/.test(pass) && /[a-z]/.test(pass)) score += 1;
  if (/\d/.test(pass)) score += 1;
  if (/[!@#$%^&*(),.?":{}|<>]/.test(pass)) score += 1;
  return score;
};

const getStrengthConfig = (score) => {
  switch (score) {
    case 0: return { label: 'Muy débil', color: '#E5E7EB', segments: 0 };
    case 1: return { label: 'Débil', color: '#EF4444', segments: 1 };
    case 2: return { label: 'Regular', color: '#F59E0B', segments: 2 };
    case 3: return { label: 'Buena', color: '#34D399', segments: 3 };
    case 4: return { label: 'Excelente', color: '#059669', segments: 4 };
    default: return { label: '', color: '#E5E7EB', segments: 0 };
  }
};

export default function RegisterScreen({ navigation }) {
  const [nombre, setNombre] = useState('');
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [loading, setLoading] = useState(false);
  const [showPassword, setShowPassword] = useState(false);
  const [acceptTerms, setAcceptTerms] = useState(false);
  const [showTerms, setShowTerms] = useState(false);
  const [termsType, setTermsType] = useState('terms'); // 'terms' or 'privacy'

  const strengthScore = getPasswordStrength(password);
  const strengthConfig = getStrengthConfig(strengthScore);
  
  const reqLength = password.length >= 8;
  const reqNum = /\d/.test(password);
  const reqUpperLower = /[A-Z]/.test(password) && /[a-z]/.test(password);
  const reqSpecial = /[!@#$%^&*(),.?":{}|<>]/.test(password);

  const handleRegister = async () => {
    if (!nombre || !email || !password) {
      Alert.alert('Error', 'Completa todos los campos');
      return;
    }
    
    if (strengthScore < 4) {
      Alert.alert('Contraseña débil', 'La contraseña debe cumplir con todos los requisitos de seguridad (8+ caracteres, número, mayúscula, minúscula y carácter especial).');
      return;
    }
    
    if (!acceptTerms) {
      Alert.alert('Aviso', 'Debes aceptar los Términos y Condiciones para registrarte.');
      return;
    }

    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
      Alert.alert('Error', 'Ingresa un correo electrónico válido');
      return;
    }

    setLoading(true);
    try {
      const response = await api.post('/auth/mobile/registro', { nombre, email, password });
      if (response.data.success) {
        // En lugar de ir directo, redirigimos a VerifyScreen
        navigation.navigate('verify', { email });
      } else {
        Alert.alert('Error', response.data.error || 'Error en el registro');
      }
    } catch (error) {
      const msg = error.response?.data?.detail || 'Error de conexión. Verifica tu internet o el estado del servidor.';
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
        
        {/* Header Title */}
        <View className="mb-6 items-center mt-4">
          <Text className="text-[28px] font-extrabold text-primary mb-2 text-center">Crea tu cuenta</Text>
          <Text className="text-gray-500 text-base text-center">Únete a nosotros para empezar a reciclar</Text>
        </View>

        {/* Profile Avatar Picker */}
        <View className="items-center mb-8">
          <TouchableOpacity activeOpacity={0.7} className="items-center">
            <View className="bg-[#5C6B89] w-20 h-20 rounded-full items-center justify-center mb-2 overflow-hidden border-4 border-[#E8F5E9]">
              <User color="white" size={40} />
            </View>
            <Text className="text-gray-500 text-sm font-medium">Añadir foto de perfil</Text>
          </TouchableOpacity>
        </View>

        {/* Inputs */}
        <View className="mb-2">
          <CustomInput 
            label="Nombre completo" 
            placeholder="Tu Nombre Completo"
            value={nombre}
            onChangeText={setNombre}
            leftIcon={<User color="#9CA3AF" size={20} />}
          />
          <CustomInput 
            label="Email" 
            placeholder="correo@ejemplo.com"
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

          {/* Password Strength Indicator PRO */}
          {password.length > 0 && (
            <View className="mt-1 mb-4 px-1">
              <View className="flex-row items-center justify-between mb-2">
                <Text className="text-xs font-bold text-gray-500">Seguridad de la contraseña</Text>
                <Text className="text-xs font-black" style={{ color: strengthConfig.color }}>
                  {strengthConfig.label}
                </Text>
              </View>
              
              <View className="flex-row gap-1.5 mb-3">
                {[1, 2, 3, 4].map((index) => (
                  <View 
                    key={index} 
                    className="h-1.5 flex-1 rounded-full" 
                    style={{ 
                      backgroundColor: index <= strengthConfig.segments ? strengthConfig.color : '#E5E7EB',
                      opacity: index <= strengthConfig.segments ? 1 : 0.5 
                    }} 
                  />
                ))}
              </View>
              
              <View className="flex-row flex-wrap gap-y-2">
                <View className="w-1/2 flex-row items-center gap-1.5">
                  <View className={`w-3.5 h-3.5 rounded-full items-center justify-center ${reqLength ? 'bg-emerald-500' : 'bg-gray-200'}`}>
                    <Check color="white" size={8} strokeWidth={4} />
                  </View>
                  <Text className={`text-[11px] font-medium ${reqLength ? 'text-gray-700' : 'text-gray-400'}`}>8+ caracteres</Text>
                </View>
                
                <View className="w-1/2 flex-row items-center gap-1.5">
                  <View className={`w-3.5 h-3.5 rounded-full items-center justify-center ${reqNum ? 'bg-emerald-500' : 'bg-gray-200'}`}>
                    <Check color="white" size={8} strokeWidth={4} />
                  </View>
                  <Text className={`text-[11px] font-medium ${reqNum ? 'text-gray-700' : 'text-gray-400'}`}>Un número</Text>
                </View>
                
                <View className="w-1/2 flex-row items-center gap-1.5">
                  <View className={`w-3.5 h-3.5 rounded-full items-center justify-center ${reqUpperLower ? 'bg-emerald-500' : 'bg-gray-200'}`}>
                    <Check color="white" size={8} strokeWidth={4} />
                  </View>
                  <Text className={`text-[11px] font-medium ${reqUpperLower ? 'text-gray-700' : 'text-gray-400'}`}>Mayúsculas y minúsculas</Text>
                </View>
                
                <View className="w-1/2 flex-row items-center gap-1.5">
                  <View className={`w-3.5 h-3.5 rounded-full items-center justify-center ${reqSpecial ? 'bg-emerald-500' : 'bg-gray-200'}`}>
                    <Check color="white" size={8} strokeWidth={4} />
                  </View>
                  <Text className={`text-[11px] font-medium ${reqSpecial ? 'text-gray-700' : 'text-gray-400'}`}>Carácter especial</Text>
                </View>
              </View>
            </View>
          )}
        </View>

        {/* Terms Checkbox */}
        <View className="flex-row items-start mb-6 px-1">
          <TouchableOpacity 
            className="mt-1"
            onPress={() => setAcceptTerms(!acceptTerms)}
            activeOpacity={0.7}
          >
            <View className={`w-5 h-5 rounded-md border items-center justify-center mr-3 ${acceptTerms ? 'bg-primary border-primary' : 'bg-gray-50 border-gray-300'}`}>
              {acceptTerms && <Check color="white" size={14} strokeWidth={3.5} />}
            </View>
          </TouchableOpacity>
          <Text className="text-gray-500 text-sm flex-1 leading-5">
            Al registrarte, aceptas nuestro{' '}
            <Text 
              className="text-primary font-bold"
              onPress={() => { setTermsType('privacy'); setShowTerms(true); }}
            >Aviso de Privacidad</Text>{' '}
            y los{' '}
            <Text 
              className="text-primary font-bold"
              onPress={() => { setTermsType('terms'); setShowTerms(true); }}
            >Términos y Condiciones</Text>{' '}
            del servicio de ZeroWaste.
          </Text>
        </View>
        
        {/* Register Button */}
        <CustomButton 
          title="Registrarse" 
          onPress={handleRegister} 
          loading={loading}
          className="rounded-xl py-4"
        />

        {/* Divider */}
        <View className="flex-row items-center my-6">
          <View className="flex-1 h-[1px] bg-gray-200" />
          <Text className="text-gray-400 font-bold px-4">o</Text>
          <View className="flex-1 h-[1px] bg-gray-200" />
        </View>

        {/* Google Button */}
        <TouchableOpacity 
          className="flex-row items-center justify-center bg-white border border-gray-200 rounded-xl py-3.5 mb-6 shadow-sm"
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
          <Text className="text-gray-500 font-medium">¿Ya tienes cuenta? </Text>
          <TouchableOpacity onPress={() => navigation.navigate('login')}>
            <Text className="text-primary font-bold">Inicia sesión</Text>
          </TouchableOpacity>
        </View>

            </View>
          </ScrollView>
        </TouchableWithoutFeedback>
      </KeyboardAvoidingView>

      {/* Terms and Privacy Modal */}
      <Modal
        visible={showTerms}
        animationType="slide"
        presentationStyle="pageSheet"
        onRequestClose={() => setShowTerms(false)}
      >
        <SafeAreaView className="flex-1 bg-white pt-2">
          <View className="px-5 mb-4 mt-2 flex-row items-center justify-between">
            <Text className="text-xl font-black text-gray-800">
              {termsType === 'terms' ? 'Términos y Condiciones' : 'Aviso de Privacidad'}
            </Text>
            <TouchableOpacity onPress={() => setShowTerms(false)} className="bg-gray-100 p-2 rounded-full">
              <Text className="text-gray-600 font-bold">X</Text>
            </TouchableOpacity>
          </View>
          
          <ScrollView className="flex-1 px-5" showsVerticalScrollIndicator={false}>
            {termsType === 'terms' ? (
              <Text className="text-gray-600 text-base leading-7 mb-10" style={{ textAlign: 'justify' }}>
                <Text className="font-bold text-gray-800 mb-2">1. Aceptación de los Términos{"\n"}</Text>
                Al acceder y utilizar la aplicación ZeroWaste, usted acepta estar sujeto a estos Términos y Condiciones. Si no está de acuerdo con alguna parte de estos términos, no podrá utilizar nuestro servicio.{"\n\n"}
                
                <Text className="font-bold text-gray-800 mb-2">2. Uso del Servicio{"\n"}</Text>
                Nuestra aplicación fomenta la sostenibilidad y el reciclaje. Usted se compromete a usarla de forma responsable, proporcionando información veraz sobre el material reciclable que reporta.{"\n\n"}
                
                <Text className="font-bold text-gray-800 mb-2">3. Conducta del Usuario{"\n"}</Text>
                Queda prohibido utilizar la plataforma para actividades ilegales o publicar contenido ofensivo en el foro de la comunidad.{"\n\n"}
                
                <Text className="font-bold text-gray-800 mb-2">4. Modificaciones{"\n"}</Text>
                Nos reservamos el derecho de modificar estos términos en cualquier momento. El uso continuo de la aplicación constituirá su aceptación de dichas modificaciones.
              </Text>
            ) : (
              <Text className="text-gray-600 text-base leading-7 mb-10" style={{ textAlign: 'justify' }}>
                <Text className="font-bold text-gray-800 mb-2">1. Información que recopilamos{"\n"}</Text>
                ZeroWaste recopila información personal básica (nombre, correo electrónico) para crear su cuenta, así como información de ubicación para la funcionalidad del mapa y puntos de recolección.{"\n\n"}
                
                <Text className="font-bold text-gray-800 mb-2">2. Uso de la Información{"\n"}</Text>
                Usamos sus datos para mejorar su experiencia, conectarlo con recolectores y administrar el foro comunitario. Nunca venderemos sus datos a terceros.{"\n\n"}
                
                <Text className="font-bold text-gray-800 mb-2">3. Seguridad de Datos{"\n"}</Text>
                Implementamos medidas de seguridad para proteger su información personal, como el cifrado de contraseñas.{"\n\n"}
                
                <Text className="font-bold text-gray-800 mb-2">4. Sus Derechos{"\n"}</Text>
                Usted puede solicitar la eliminación de su cuenta y sus datos asociados en cualquier momento desde los ajustes de su perfil.
              </Text>
            )}
          </ScrollView>
        </SafeAreaView>
      </Modal>

    </SafeAreaView>
  );
}