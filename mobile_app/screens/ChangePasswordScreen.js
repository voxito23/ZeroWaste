import React, { useState } from 'react';
import { ActivityIndicator, Text, TextInput, TouchableOpacity, View } from 'react-native';
import { ArrowLeft, LockKeyhole } from 'lucide-react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { useNavigation } from '@react-navigation/native';

import { api } from '../api/axios';
import KeyboardAwareScreen from '../components/ui/KeyboardAwareScreen';
import { useZeroWasteDialog } from '../components/ui/ZeroWasteDialog';

export default function ChangePasswordScreen() {
  const navigation = useNavigation();
  const { showDialog } = useZeroWasteDialog();
  const [form, setForm] = useState({ current: '', next: '', confirmation: '' });
  const [error, setError] = useState('');
  const [saving, setSaving] = useState(false);
  const update = (key) => (value) => { setForm((current) => ({ ...current, [key]: value })); setError(''); };

  const save = async () => {
    if (saving) return;
    if (!form.current || form.next.length < 6) {
      setError('Escribe tu contraseña actual y una nueva de al menos 6 caracteres.');
      return;
    }
    if (form.next !== form.confirmation) {
      setError('La confirmación no coincide con la nueva contraseña.');
      return;
    }
    setSaving(true);
    try {
      const body = new FormData();
      body.append('password_actual', form.current);
      body.append('password_nueva', form.next);
      await api.put('/usuarios/perfil/password', body);
      setForm({ current: '', next: '', confirmation: '' });
      showDialog({ type: 'success', title: 'Contraseña actualizada', message: 'Tu nueva contraseña ya está activa.', primaryLabel: 'Continuar', onPrimary: () => navigation.goBack() });
    } catch (requestError) {
      setError(requestError.response?.data?.detail || requestError.userMessage || 'No se pudo actualizar la contraseña.');
    } finally {
      setSaving(false);
    }
  };

  const canSave = Boolean(form.current && form.next.length >= 6 && form.confirmation);
  return (
    <SafeAreaView className="flex-1 bg-slate-50" edges={['top', 'bottom']}>
      <View className="flex-row items-center border-b border-slate-100 bg-white px-5 py-4"><TouchableOpacity onPress={() => navigation.goBack()} className="h-11 w-11 items-center justify-center rounded-full bg-slate-100" accessibilityLabel="Volver"><ArrowLeft color="#111827" size={20} /></TouchableOpacity><View className="ml-4"><Text className="text-xl font-black text-slate-950">Cambiar contraseña</Text><Text className="text-xs font-semibold text-slate-500">Protege el acceso a tu cuenta</Text></View></View>
      <KeyboardAwareScreen
        contentContainerStyle={{ padding: 20 }}
        footer={<View className="border-t border-slate-100 bg-white px-5 py-3"><TouchableOpacity disabled={!canSave || saving} onPress={save} className="items-center rounded-2xl bg-emerald-700 py-4 disabled:opacity-50">{saving ? <ActivityIndicator color="white" /> : <Text className="font-black text-white">Guardar contraseña</Text>}</TouchableOpacity></View>}
      >
        <View className="rounded-3xl border border-emerald-100 bg-emerald-50 p-5"><LockKeyhole color="#047857" size={24} /><Text className="mt-3 font-black text-emerald-950">Usa una contraseña exclusiva</Text><Text className="mt-1 text-sm leading-5 text-emerald-900">Evita reutilizar la misma contraseña de tu correo u otros servicios.</Text></View>
        {error ? <View className="mt-4 rounded-2xl border border-red-200 bg-red-50 p-4"><Text className="font-bold text-red-700">{error}</Text></View> : null}
        {[
          ['Contraseña actual', 'current'],
          ['Nueva contraseña', 'next'],
          ['Confirmar nueva contraseña', 'confirmation'],
        ].map(([label, key]) => <View key={key} className="mt-5"><Text className="mb-2 font-bold text-slate-700">{label}</Text><TextInput value={form[key]} onChangeText={update(key)} secureTextEntry autoCapitalize="none" autoCorrect={false} returnKeyType={key === 'confirmation' ? 'done' : 'next'} onSubmitEditing={key === 'confirmation' && canSave ? save : undefined} className="min-h-14 rounded-2xl border border-slate-200 bg-white px-4 text-slate-950" /></View>)}
      </KeyboardAwareScreen>
    </SafeAreaView>
  );
}
