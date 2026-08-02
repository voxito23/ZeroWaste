import React, { useEffect, useRef, useState } from 'react';
import { ActivityIndicator, KeyboardAvoidingView, Platform, Text, TextInput, TouchableOpacity, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { StatusBar } from 'expo-status-bar';
import { ArrowLeft, CheckCircle2, MailCheck, RefreshCw, ShieldCheck } from 'lucide-react-native';

import { api } from '../api/axios';

const RESEND_SECONDS = 60;

export default function EmailVerificationScreen({ navigation, route }) {
  const email = String(route.params?.email || '').trim().toLowerCase();
  const initiallySent = route.params?.sent !== false;
  const inputRef = useRef(null);
  const [code, setCode] = useState('');
  const [loading, setLoading] = useState(false);
  const [resending, setResending] = useState(false);
  const [seconds, setSeconds] = useState(initiallySent ? RESEND_SECONDS : 0);
  const [message, setMessage] = useState(initiallySent ? 'Enviamos un código de 6 dígitos a tu correo.' : 'El correo no pudo enviarse. Puedes intentarlo nuevamente.');
  const [error, setError] = useState('');
  const [verified, setVerified] = useState(false);

  useEffect(() => {
    if (seconds <= 0) return undefined;
    const timer = setInterval(() => setSeconds((value) => Math.max(0, value - 1)), 1000);
    return () => clearInterval(timer);
  }, [seconds > 0]);

  const updateCode = (value) => {
    setCode(value.replace(/\D/g, '').slice(0, 6));
    setError('');
  };

  const verify = async () => {
    if (loading || code.length !== 6) return;
    setLoading(true);
    setError('');
    try {
      await api.post('/auth/email/verificar-otp', { email, code });
      setVerified(true);
    } catch (requestError) {
      const status = requestError.response?.status;
      const detail = requestError.response?.data?.detail;
      if (status === 410) setError('Este código de verificación venció. Solicita uno nuevo.');
      else if (status === 409) setError('Este código ya fue utilizado o reemplazado.');
      else if (status === 429) setError('Demasiados intentos. Espera un minuto antes de intentarlo nuevamente.');
      else setError(detail || requestError.userMessage || 'No fue posible verificar el código. Revisa tu conexión.');
    } finally {
      setLoading(false);
    }
  };

  const resend = async () => {
    if (resending || seconds > 0) return;
    setResending(true);
    setError('');
    try {
      await api.post('/auth/email/reenviar', { email });
      setCode('');
      setSeconds(RESEND_SECONDS);
      setMessage('Enviamos un código nuevo. El anterior dejó de funcionar.');
      inputRef.current?.focus();
    } catch (requestError) {
      if (requestError.response?.status === 429) {
        setSeconds(RESEND_SECONDS);
        setError('Espera un minuto antes de solicitar otro código.');
      } else {
        setError(requestError.response?.data?.detail || requestError.userMessage || 'No fue posible reenviar el código.');
      }
    } finally {
      setResending(false);
    }
  };

  if (verified) {
    return <SafeAreaView className="flex-1 items-center justify-center bg-emerald-50 px-7"><StatusBar style="dark" /><View className="h-24 w-24 items-center justify-center rounded-full bg-emerald-100"><CheckCircle2 color="#059669" size={58} /></View><Text className="mt-7 text-center text-3xl font-black text-emerald-950">Correo verificado</Text><Text className="mt-3 text-center text-base leading-6 text-slate-600">Tu cuenta ZeroWaste está activa. Ya puedes iniciar sesión de forma segura.</Text><TouchableOpacity onPress={() => navigation.navigate('Login')} className="mt-9 h-14 w-full items-center justify-center rounded-2xl bg-emerald-700"><Text className="text-base font-black text-white">Ir a iniciar sesión</Text></TouchableOpacity></SafeAreaView>;
  }

  return (
    <SafeAreaView className="flex-1 bg-slate-50" edges={['top', 'bottom']}>
      <StatusBar style="dark" />
      <View className="flex-row items-center px-5 py-3"><TouchableOpacity onPress={() => navigation.navigate('Login')} className="h-11 w-11 items-center justify-center rounded-full bg-white" accessibilityLabel="Volver al inicio de sesión"><ArrowLeft color="#0F172A" size={21} /></TouchableOpacity></View>
      <KeyboardAvoidingView behavior={Platform.OS === 'ios' ? 'padding' : 'height'} className="flex-1 justify-center px-6 pb-16">
        <View className="items-center"><View className="h-20 w-20 items-center justify-center rounded-3xl bg-emerald-100"><MailCheck color="#047857" size={40} /></View><Text className="mt-6 text-center text-3xl font-black text-slate-900">Verifica tu correo</Text><Text className="mt-3 text-center text-base leading-6 text-slate-500">{message}</Text><Text className="mt-2 text-center font-black text-emerald-800">{email}</Text></View>

        <TouchableOpacity activeOpacity={1} onPress={() => inputRef.current?.focus()} className="mt-9 flex-row justify-between" accessibilityLabel={`Código de verificación: ${code.length} de 6 dígitos capturados`}>
          {Array.from({ length: 6 }, (_, index) => <View key={index} className={`h-16 w-[14.5%] items-center justify-center rounded-2xl border-2 bg-white ${error ? 'border-red-300' : index === code.length ? 'border-emerald-500' : 'border-slate-200'}`}><Text className="text-2xl font-black text-slate-900">{code[index] || ''}</Text></View>)}
        </TouchableOpacity>
        <TextInput ref={inputRef} autoFocus value={code} onChangeText={updateCode} keyboardType="number-pad" maxLength={6} textContentType="oneTimeCode" autoComplete="one-time-code" caretHidden className="absolute h-px w-px opacity-0" accessibilityLabel="Escribe el código de seis dígitos" />

        {error ? <View className="mt-5 rounded-2xl border border-red-200 bg-red-50 p-4"><Text accessibilityLiveRegion="polite" className="text-center font-bold leading-5 text-red-700">{error}</Text></View> : null}

        <TouchableOpacity disabled={loading || code.length !== 6} onPress={verify} className="mt-7 h-14 flex-row items-center justify-center rounded-2xl bg-emerald-700 disabled:opacity-50">{loading ? <ActivityIndicator color="white" /> : <><ShieldCheck color="white" size={20} /><Text className="ml-2 text-base font-black text-white">Verificar correo</Text></>}</TouchableOpacity>
        <TouchableOpacity disabled={resending || seconds > 0} onPress={resend} className="mt-4 h-12 flex-row items-center justify-center disabled:opacity-50">{resending ? <ActivityIndicator color="#047857" /> : <><RefreshCw color="#047857" size={18} /><Text className="ml-2 font-bold text-emerald-700">{seconds > 0 ? `Enviar otro código en ${seconds}s` : 'Enviar un código nuevo'}</Text></>}</TouchableOpacity>
        <Text className="mt-5 text-center text-xs leading-5 text-slate-400">El código vence en 10 minutos y solo puede utilizarse una vez. No lo compartas con nadie.</Text>
      </KeyboardAvoidingView>
    </SafeAreaView>
  );
}
