import React, { useCallback, useEffect, useRef, useState } from 'react';
import {
  AccessibilityInfo,
  Animated,
  Linking,
  StyleSheet,
  Text,
  TouchableOpacity,
  View,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { useFocusEffect, useIsFocused, useNavigation } from '@react-navigation/native';
import { CameraView, useCameraPermissions } from 'expo-camera';
import { Flashlight, ScanLine, XCircle } from 'lucide-react-native';
import { StatusBar } from 'expo-status-bar';

import { api } from '../api/axios';
import ZeroWasteDialog from '../components/ui/ZeroWasteDialog';

const EXTERNAL_QR_ERROR = 'Error: este código QR no pertenece a ZeroWaste.';
const OWN_QR_PATTERN = /^(https:\/\/(www\.)?zerowaste-qro\.com\/q\/[pc]\/zw1[pc]_[A-Za-z0-9_-]{40,}|zerowaste:\/\/qr\/zw1[pc]_[A-Za-z0-9_-]{40,})$/;

const ERROR_DIALOGS = {
  NOT_ZEROWASTE_QR: ['Código no reconocido', EXTERNAL_QR_ERROR],
  QR_TAMPERED: ['Código inválido', 'Este código QR no es válido o fue modificado.'],
  QR_REVOKED: ['Código desactivado', 'Este código QR ya no está activo.'],
  QR_EXPIRED: ['Código vencido', 'Este código QR ha vencido.'],
  QR_ALREADY_USED: ['Código ya utilizado', 'Esta recolección ya fue confirmada anteriormente.'],
  COLLECTION_MISMATCH: ['Código incorrecto', 'Este código no corresponde a la recolección seleccionada.'],
  WRONG_COLLECTION: ['Código incorrecto', 'Este código no corresponde a la recolección seleccionada.'],
  FORBIDDEN: ['Acceso restringido', 'No tienes permiso para confirmar esta recolección.'],
  COLLECTOR_REQUIRED: ['Acceso restringido', 'Este código corresponde a una recolección y solo puede ser confirmado por un recolector autorizado.'],
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

  const showError = (message, title = 'Código no válido', options = {}) => setResult({ status: 'error', type: 'error', title, message, ...options });

  const validate = async (content, { force = false } = {}) => {
    const value = String(content || '').trim();
    const now = Date.now();
    if (!force && lastCode.current.value === value && now - lastCode.current.at < 3000) return;
    lastCode.current = { value, at: now };
    setLocked(true);

    if (!OWN_QR_PATTERN.test(value)) {
      showError(EXTERNAL_QR_ERROR, 'Código no reconocido');
      return;
    }

    setResult({ status: 'processing', type: 'info', title: 'Validando código', message: 'Estamos comprobando que pertenece a ZeroWaste.' });
    try {
      const { data } = await api.post('/qr/validar', { contenido: value });
      if (data?.valid && data.type === 'recycling_point' && data.point) {
        setResult({ status: 'success', type: 'success', title: 'Código reconocido', message: 'Encontramos este punto de reciclaje en ZeroWaste.', point: data.point });
        return;
      }
      if (data?.valid && data.type === 'collection') {
        if (!data.authorized) {
          setResult({ status: 'restriction', type: 'restriction', title: 'Acceso restringido', message: data.detail || 'Este código corresponde a una recolección y solo puede ser confirmado por un recolector autorizado.' });
        } else {
          setResult({ status: 'confirm', type: 'warning', title: 'Confirmar recolección', message: 'Verifica que la entrega haya sido realizada. Esta acción otorgará puntos una sola vez.', content: value, collectionId: data.collection_id });
        }
        return;
      }
      showError(EXTERNAL_QR_ERROR, 'Código no reconocido');
    } catch (error) {
      if (!error.response) {
        showError('Revisa tu conexión e inténtalo nuevamente.', 'No pudimos validar el código', { network: true });
        return;
      }
      const body = error.response?.data || {};
      const known = ERROR_DIALOGS[body.code];
      showError(known?.[1] || body.detail || 'Este código QR no es válido o fue modificado.', known?.[0]);
    }
  };

  const confirmCollection = async () => {
    if (!result?.content || result.status === 'processing') return;
    const pending = result;
    setResult({ ...pending, status: 'processing' });
    try {
      await api.post('/qr/confirmar', { contenido: pending.content, collection_id: Number(pending.collectionId) });
      setResult({ status: 'success_collection', type: 'success', title: 'Recolección confirmada', message: 'La recolección quedó completada y los puntos se registraron una sola vez.' });
    } catch (error) {
      const body = error.response?.data || {};
      const known = ERROR_DIALOGS[body.code];
      showError(known?.[1] || body.detail || 'No fue posible confirmar la recolección.', known?.[0] || 'No pudimos confirmar');
    }
  };

  const primaryAction = () => {
    if (result?.point) {
      const point = result.point;
      setResult(null);
      navigation.navigate('PointDetail', { point });
    } else if (result?.status === 'confirm') void confirmCollection();
    else if (result?.network) void retryNetwork();
    else retry();
  };

  const retryNetwork = async () => {
    const content = lastCode.current.value;
    setResult(null);
    setLocked(false);
    await validate(content, { force: true });
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

      <ZeroWasteDialog visible={Boolean(result)} type={result?.type} title={result?.title} message={result?.message} busy={result?.status === 'processing'} primaryLabel={result?.point ? 'Ver punto' : result?.status === 'confirm' ? 'Confirmar recolección' : result?.network ? 'Reintentar' : result?.status === 'error' ? 'Volver a escanear' : 'Entendido'} onPrimary={primaryAction} secondaryLabel={result?.status === 'confirm' || result?.network ? 'Cancelar' : undefined} onSecondary={retry} />
    </View>
  );
}
