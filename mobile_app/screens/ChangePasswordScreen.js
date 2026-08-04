import React, { useMemo, useRef, useState } from 'react';
import { ActivityIndicator, Pressable, Text, TextInput, TouchableOpacity, View } from 'react-native';
import { ArrowLeft, CheckCircle2, Circle, Eye, EyeOff, KeyRound, ShieldCheck } from 'lucide-react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { useNavigation } from '@react-navigation/native';

import { api } from '../api/axios';
import KeyboardAwareScreen from '../components/ui/KeyboardAwareScreen';
import { useZeroWasteDialog } from '../components/ui/ZeroWasteDialog';

const strengthLabel = ['Muy corta', 'Básica', 'Aceptable', 'Segura', 'Muy segura'];

export default function ChangePasswordScreen() {
  const navigation = useNavigation();
  const { showDialog } = useZeroWasteDialog();
  const nextRef = useRef(null);
  const confirmationRef = useRef(null);
  const [form, setForm] = useState({ current: '', next: '', confirmation: '' });
  const [visible, setVisible] = useState({ current: false, next: false, confirmation: false });
  const [error, setError] = useState('');
  const [saving, setSaving] = useState(false);
  const update = (key) => (value) => { setForm((current) => ({ ...current, [key]: value })); setError(''); };
  const toggleVisibility = (key) => setVisible((current) => ({ ...current, [key]: !current[key] }));

  const strength = useMemo(() => [
    form.next.length >= 6,
    form.next.length >= 10,
    /[A-ZÁÉÍÓÚÑ]/.test(form.next) && /[a-záéíóúñ]/.test(form.next),
    /\d/.test(form.next) && /[^A-Za-zÁÉÍÓÚáéíóúÑñ0-9]/.test(form.next),
  ].filter(Boolean).length, [form.next]);
  const rules = [
    { label: 'Al menos 6 caracteres', valid: form.next.length >= 6 },
    { label: 'Diferente de la contraseña actual', valid: Boolean(form.next && form.next !== form.current) },
    { label: 'La confirmación coincide', valid: Boolean(form.confirmation && form.confirmation === form.next) },
  ];
  const canSave = Boolean(form.current) && rules.every((rule) => rule.valid);

  const save = async () => {
    if (saving) return;
    if (!form.current || form.next.length < 6) {
      setError('Escribe tu contraseña actual y una nueva de al menos 6 caracteres.');
      return;
    }
    if (form.current === form.next) {
      setError('La nueva contraseña debe ser diferente de la actual.');
      return;
    }
    if (form.next !== form.confirmation) {
      setError('La confirmación no coincide con la nueva contraseña.');
      return;
    }
    setSaving(true);
    setError('');
    try {
      const body = new FormData();
      body.append('password_actual', form.current);
      body.append('password_nueva', form.next);
      await api.put('/usuarios/perfil/password', body);
      setForm({ current: '', next: '', confirmation: '' });
      showDialog({ type: 'success', title: 'Contraseña actualizada', message: 'Tu nueva contraseña ya protege esta cuenta.', primaryLabel: 'Continuar', onPrimary: () => navigation.goBack() });
    } catch (requestError) {
      setError(requestError.response?.data?.detail || requestError.userMessage || 'No se pudo actualizar la contraseña.');
    } finally {
      setSaving(false);
    }
  };

  return (
    <SafeAreaView className="flex-1 bg-slate-50" edges={['top', 'bottom']}>
      <View className="flex-row items-center border-b border-slate-100 bg-white px-5 py-4">
        <TouchableOpacity onPress={() => navigation.goBack()} className="h-11 w-11 items-center justify-center rounded-full bg-slate-100" accessibilityLabel="Volver"><ArrowLeft color="#111827" size={20} /></TouchableOpacity>
        <View className="ml-4 flex-1"><Text className="text-xl font-black text-slate-950">Contraseña y seguridad</Text><Text className="text-xs font-semibold text-slate-500">Actualiza tus credenciales de acceso</Text></View>
        <View className="h-10 w-10 items-center justify-center rounded-full bg-emerald-50"><ShieldCheck color="#047857" size={20} /></View>
      </View>

      <KeyboardAwareScreen
        contentContainerStyle={{ padding: 20, paddingBottom: 28 }}
        footer={<View className="border-t border-slate-100 bg-white px-5 py-3"><TouchableOpacity disabled={!canSave || saving} onPress={save} className="min-h-14 items-center justify-center rounded-2xl bg-emerald-700 disabled:opacity-40" accessibilityState={{ busy: saving, disabled: !canSave || saving }}>{saving ? <ActivityIndicator color="white" /> : <Text className="font-black text-white">Actualizar contraseña</Text>}</TouchableOpacity></View>}
      >
        <View className="overflow-hidden rounded-[28px] bg-emerald-950 p-6">
          <View className="h-12 w-12 items-center justify-center rounded-full bg-white/10"><KeyRound color="#6EE7B7" size={24} /></View>
          <Text className="mt-5 text-2xl font-black text-white">Protege tu cuenta</Text>
          <Text className="mt-2 text-[15px] leading-6 text-emerald-100" style={{ textAlign: 'justify' }}>Usa una contraseña exclusiva que no compartas con tu correo ni con otros servicios.</Text>
        </View>

        {error ? <View className="mt-4 rounded-2xl border border-red-200 bg-red-50 p-4"><Text accessibilityLiveRegion="polite" className="font-bold leading-5 text-red-700">{error}</Text></View> : null}

        <View className="mt-5 rounded-[28px] border border-slate-100 bg-white p-5">
          <PasswordField label="Contraseña actual" value={form.current} visible={visible.current} onChangeText={update('current')} onToggle={() => toggleVisibility('current')} returnKeyType="next" onSubmitEditing={() => nextRef.current?.focus()} autoFocus />
          <PasswordField inputRef={nextRef} label="Nueva contraseña" value={form.next} visible={visible.next} onChangeText={update('next')} onToggle={() => toggleVisibility('next')} returnKeyType="next" onSubmitEditing={() => confirmationRef.current?.focus()} spacing />

          <View className="mt-3">
            <View className="flex-row gap-1.5">{[1, 2, 3, 4].map((level) => <View key={level} className={`h-1.5 flex-1 rounded-full ${strength >= level ? 'bg-emerald-500' : 'bg-slate-200'}`} />)}</View>
            <Text className="mt-2 text-xs font-bold text-slate-500">Seguridad: <Text className="text-emerald-700">{strengthLabel[strength]}</Text></Text>
          </View>

          <PasswordField inputRef={confirmationRef} label="Confirmar nueva contraseña" value={form.confirmation} visible={visible.confirmation} onChangeText={update('confirmation')} onToggle={() => toggleVisibility('confirmation')} returnKeyType="done" onSubmitEditing={canSave ? save : undefined} spacing />

          <View className="mt-4 rounded-2xl bg-slate-50 p-4">
            {rules.map((rule) => <View key={rule.label} className="mb-2 flex-row items-center last:mb-0">{rule.valid ? <CheckCircle2 color="#059669" size={17} /> : <Circle color="#94A3B8" size={17} />}<Text className={`ml-2 text-xs font-bold ${rule.valid ? 'text-emerald-700' : 'text-slate-500'}`}>{rule.label}</Text></View>)}
          </View>
        </View>
      </KeyboardAwareScreen>
    </SafeAreaView>
  );
}

function PasswordField({ inputRef, label, value, visible, onChangeText, onToggle, spacing, ...inputProps }) {
  const Icon = visible ? EyeOff : Eye;
  return <View className={spacing ? 'mt-5' : ''}><Text className="mb-2 text-sm font-black text-slate-700">{label}</Text><View className="min-h-14 flex-row items-center rounded-2xl border border-slate-200 bg-slate-50 px-4"><TextInput ref={inputRef} value={value} onChangeText={onChangeText} secureTextEntry={!visible} autoCapitalize="none" autoCorrect={false} className="min-h-14 flex-1 text-[16px] font-semibold text-slate-950" placeholder="••••••••" placeholderTextColor="#94A3B8" accessibilityLabel={label} {...inputProps} /><Pressable onPress={onToggle} className="h-11 w-11 items-center justify-center" accessibilityLabel={visible ? `Ocultar ${label.toLowerCase()}` : `Mostrar ${label.toLowerCase()}`}><Icon color="#64748B" size={20} /></Pressable></View></View>;
}
