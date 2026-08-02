import React, { createContext, useCallback, useContext, useEffect, useMemo, useRef, useState } from 'react';
import { AccessibilityInfo, Animated, Modal, Pressable, Text, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { AlertTriangle, Camera, CheckCircle2, CircleHelp, Info, ShieldAlert, XCircle } from 'lucide-react-native';

const ICONS = {
  success: CheckCircle2,
  error: XCircle,
  warning: AlertTriangle,
  restriction: ShieldAlert,
  permission: Camera,
  confirmation: CircleHelp,
  info: Info,
};
const COLORS = {
  success: '#059669',
  error: '#DC2626',
  warning: '#D97706',
  restriction: '#047857',
  permission: '#047857',
  confirmation: '#047857',
  info: '#047857',
};

const DialogContext = createContext(null);

export function ZeroWasteDialogProvider({ children }) {
  const [options, setOptions] = useState(null);
  const showDialog = useCallback((nextOptions) => setOptions(nextOptions), []);
  const dismissDialog = useCallback(() => setOptions(null), []);
  const handlePrimary = useCallback(() => {
    const callback = options?.onPrimary;
    setOptions(null);
    callback?.();
  }, [options]);
  const handleSecondary = useCallback(() => {
    const callback = options?.onSecondary;
    setOptions(null);
    callback?.();
  }, [options]);
  const value = useMemo(() => ({ showDialog, dismissDialog }), [dismissDialog, showDialog]);

  return (
    <DialogContext.Provider value={value}>
      {children}
      <ZeroWasteDialog
        visible={Boolean(options)}
        type={options?.type}
        title={options?.title}
        message={options?.message}
        primaryLabel={options?.primaryLabel}
        secondaryLabel={options?.secondaryLabel}
        onPrimary={handlePrimary}
        onSecondary={handleSecondary}
      />
    </DialogContext.Provider>
  );
}

export function useZeroWasteDialog() {
  const context = useContext(DialogContext);
  if (!context) throw new Error('useZeroWasteDialog debe usarse dentro de ZeroWasteDialogProvider.');
  return context;
}

export default function ZeroWasteDialog({ visible, type = 'info', title, message, primaryLabel = 'Entendido', onPrimary, secondaryLabel, onSecondary, busy = false }) {
  const progress = useRef(new Animated.Value(0)).current;
  const Icon = ICONS[type] || Info;
  const color = COLORS[type] || COLORS.info;

  useEffect(() => {
    if (!visible) { progress.setValue(0); return; }
    AccessibilityInfo.isReduceMotionEnabled().then((reduced) => Animated.timing(progress, { toValue: 1, duration: reduced ? 0 : 200, useNativeDriver: true }).start());
  }, [progress, visible]);

  return <Modal visible={visible} transparent animationType="none" statusBarTranslucent onRequestClose={busy ? undefined : (onSecondary || onPrimary)}>
    <SafeAreaView className="flex-1 items-center justify-center bg-slate-950/45 px-6" edges={['top', 'bottom']}>
      <Animated.View
        accessibilityViewIsModal
        accessibilityLabel={`${title}. ${message}`}
        style={{
          opacity: progress,
          transform: [{ scale: progress.interpolate({ inputRange: [0, 1], outputRange: [0.96, 1] }) }],
          shadowColor: '#0F172A',
          shadowOffset: { width: 0, height: 14 },
          shadowOpacity: 0.16,
          shadowRadius: 28,
          elevation: 18,
        }}
        className="w-full max-w-sm rounded-[26px] border border-slate-100 bg-white p-6"
      >
        <View className="h-12 w-12 items-center justify-center rounded-full" style={{ backgroundColor: `${color}14` }}><Icon color={color} size={25} /></View>
        <Text accessibilityRole="header" className="mt-5 text-[22px] font-black leading-7 text-slate-950">{title}</Text>
        <Text accessibilityLiveRegion="polite" className="mt-2 text-[15px] leading-6 text-slate-600">{message}</Text>
        <View className="mt-6 gap-2">
          <Pressable disabled={busy} onPress={onPrimary} accessibilityRole="button" accessibilityState={{ busy, disabled: busy }} className="min-h-12 items-center justify-center rounded-2xl bg-emerald-700 px-5" style={({ pressed }) => ({ opacity: busy ? 0.55 : pressed ? 0.82 : 1 })}><Text className="font-black text-white">{busy ? 'Procesando…' : primaryLabel}</Text></Pressable>
          {secondaryLabel ? <Pressable disabled={busy} onPress={onSecondary} accessibilityRole="button" className="min-h-12 items-center justify-center rounded-2xl border border-slate-200 bg-white px-5" style={({ pressed }) => ({ opacity: pressed ? 0.7 : 1 })}><Text className="font-black text-slate-700">{secondaryLabel}</Text></Pressable> : null}
        </View>
      </Animated.View>
    </SafeAreaView>
  </Modal>;
}
