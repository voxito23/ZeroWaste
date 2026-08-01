import React, { useCallback, useEffect, useRef, useState } from 'react';
import {
  AccessibilityInfo,
  ActivityIndicator,
  Animated,
  Linking,
  Modal,
  StyleSheet,
  Text,
  TouchableOpacity,
  View,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { useFocusEffect, useIsFocused, useNavigation } from '@react-navigation/native';
import { CameraView, useCameraPermissions } from 'expo-camera';
import { CheckCircle2, Flashlight, RotateCcw, ScanLine, XCircle } from 'lucide-react-native';
import { StatusBar } from 'expo-status-bar';

import { api } from '../api/axios';

const EXTERNAL_QR_ERROR = 'Error: este código QR no pertenece a ZeroWaste.';
const OWN_QR_PATTERN = /^(https:\/\/(www\.)?zerowaste-qro\.com\/q\/[pc]\/zw1[pc]_[A-Za-z0-9_-]{40,}|zerowaste:\/\/qr\/zw1[pc]_[A-Za-z0-9_-]{40,})$/;

const ERROR_MESSAGES = {
  NOT_ZEROWASTE_QR: EXTERNAL_QR_ERROR,
  QR_TAMPERED: 'Este código QR no es válido o fue modificado.',
  QR_REVOKED: 'Este código QR ya no está activo.',
  QR_EXPIRED: 'Este código QR ha vencido.',
  QR_ALREADY_USED: 'Esta recolección ya fue confirmada anteriormente.',
  COLLECTION_MISMATCH: 'Este código no corresponde a la recolección seleccionada.',
  FORBIDDEN: 'No tienes permiso para confirmar esta recolección.',
};

export default function ScannerScreen() {
  const navigation = useNavigation();
  const focused = useIsFocused();
  const [permission, requestPermission] = useCameraPermissions();
  const [locked, setLocked] = useState(false);
  const [torch, setTorch] = useState(false);
  const [result, setResult] = useState(null);
  const [reduceMotion, setReduceMotion] = useState(false);
  const lastCode = useRef({ value: '', at: 0 });
  const scanLine = useRef(new Animated.Value(0)).current;

  useEffect(() => {
    AccessibilityInfo.isReduceMotionEnabled().then(setReduceMotion);
    const subscription = AccessibilityInfo.addEventListener('reduceMotionChanged', setReduceMotion);
    return () => subscription.remove();
  }, []);

  useEffect(() => {
    if (reduceMotion || locked || !focused) return undefined;
    const animation = Animated.loop(Animated.sequence([
      Animated.timing(scanLine, { toValue: 1, duration: 1600, useNativeDriver: true }),
      Animated.timing(scanLine, { toValue: 0, duration: 1600, useNativeDriver: true }),
    ]));
    animation.start();
    return () => animation.stop();
  }, [focused, locked, reduceMotion, scanLine]);

  useFocusEffect(useCallback(() => {
    setLocked(false);
    setResult(null);
    return () => setLocked(true);
  }, []));

  const showError = (message) => setResult({ status: 'error', message });

  const validate = async (content) => {
    const value = String(content || '').trim();
    const now = Date.now();
    if (lastCode.current.value === value && now - lastCode.current.at < 3000) return;
    lastCode.current = { value, at: now };
    setLocked(true);

    if (!OWN_QR_PATTERN.test(value)) {
      showError(EXTERNAL_QR_ERROR);
      return;
    }

    setResult({ status: 'processing', message: 'Validando código de forma segura…' });
    try {
      const { data } = await api.post('/qr/validar', { contenido: value });
      if (data?.valid && data.type === 'recycling_point' && data.point) {
        setResult({ status: 'success', message: 'Punto de reciclaje verificado.' });
        setTimeout(() => {
          setResult(null);
          navigation.navigate('PointDetail', { point: data.point });
        }, reduceMotion ? 0 : 450);
        return;
      }
      if (data?.valid && data.type === 'collection') {
        setResult({ status: 'success', message: 'Recolección verificada correctamente.' });
        return;
      }
      showError(EXTERNAL_QR_ERROR);
    } catch (error) {
      if (!error.response) {
        showError('No fue posible validar el código. Revisa tu conexión e inténtalo nuevamente.');
        return;
      }
      const body = error.response?.data || {};
      showError(ERROR_MESSAGES[body.code] || body.detail || 'Este código QR no es válido o fue modificado.');
    }
  };

  const retry = () => {
    setResult(null);
    setLocked(false);
  };

  if (!permission) return <View className="flex-1 bg-black" />;
  if (!permission.granted) {
    return (
      <SafeAreaView className="flex-1 items-center justify-center bg-slate-950 px-8">
        <ScanLine color="#34D399" size={44} />
        <Text className="mt-6 text-center text-lg font-bold text-white">Permite el acceso a la cámara para escanear códigos QR de ZeroWaste.</Text>
        <TouchableOpacity onPress={permission.canAskAgain ? requestPermission : Linking.openSettings} className="mt-7 rounded-2xl bg-emerald-500 px-7 py-4">
          <Text className="font-black text-emerald-950">{permission.canAskAgain ? 'Permitir cámara' : 'Abrir Ajustes'}</Text>
        </TouchableOpacity>
      </SafeAreaView>
    );
  }

  return (
    <View className="flex-1 bg-black">
      <StatusBar style="light" />
      {focused && <CameraView
        style={StyleSheet.absoluteFill}
        facing="back"
        enableTorch={torch}
        barcodeScannerSettings={{ barcodeTypes: ['qr'] }}
        onBarcodeScanned={locked ? undefined : ({ data }) => validate(data)}
      />}
      <SafeAreaView className="flex-1 justify-between px-5 py-4">
        <View className="flex-row items-center justify-between">
          <TouchableOpacity onPress={() => navigation.goBack()} className="h-12 w-12 items-center justify-center rounded-full bg-black/65" accessibilityLabel="Cerrar escáner"><XCircle color="white" size={25} /></TouchableOpacity>
          <View className="rounded-full bg-black/65 px-5 py-3"><Text className="font-black text-white">Escáner ZeroWaste</Text></View>
          <TouchableOpacity onPress={() => setTorch((value) => !value)} className={`h-12 w-12 items-center justify-center rounded-full ${torch ? 'bg-emerald-400' : 'bg-black/65'}`} accessibilityLabel={torch ? 'Apagar linterna' : 'Encender linterna'}><Flashlight color={torch ? '#052E25' : 'white'} size={23} /></TouchableOpacity>
        </View>

        <View className="items-center">
          <View className="h-72 w-72 overflow-hidden rounded-[32px] border-2 border-emerald-300 bg-transparent">
            {!reduceMotion && <Animated.View style={{ transform: [{ translateY: scanLine.interpolate({ inputRange: [0, 1], outputRange: [8, 276] }) }] }} className="h-0.5 bg-emerald-300 shadow-lg" />}
          </View>
          <Text className="mt-7 max-w-xs text-center text-base font-semibold leading-6 text-white">Coloca el código dentro del marco. Solo se procesan códigos oficiales de ZeroWaste.</Text>
        </View>
        <View className="h-16" />
      </SafeAreaView>

      <Modal visible={Boolean(result)} transparent animationType={reduceMotion ? 'none' : 'fade'} onRequestClose={retry}>
        <View className="flex-1 items-center justify-center bg-black/75 px-6">
          <View className="w-full max-w-sm items-center rounded-3xl bg-white p-7">
            {result?.status === 'processing' && <ActivityIndicator size="large" color="#059669" />}
            {result?.status === 'success' && <CheckCircle2 color="#059669" size={48} />}
            {result?.status === 'error' && <XCircle color="#DC2626" size={48} />}
            <Text accessibilityLiveRegion="polite" className="mt-5 text-center text-lg font-black leading-7 text-slate-900">{result?.message}</Text>
            {result?.status === 'error' && <TouchableOpacity onPress={retry} className="mt-7 flex-row items-center rounded-2xl bg-emerald-700 px-6 py-4"><RotateCcw color="white" size={19} /><Text className="ml-2 font-black text-white">Volver a escanear</Text></TouchableOpacity>}
          </View>
        </View>
      </Modal>
    </View>
  );
}
