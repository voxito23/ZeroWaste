import React, { useEffect, useMemo, useRef, useState } from 'react';
import { ActivityIndicator, Image, Modal, Pressable, ScrollView, Text, TextInput, TouchableOpacity, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import {
  AlignLeft,
  ArrowLeft,
  BookOpen,
  Camera,
  Check,
  ChevronDown,
  GraduationCap,
  Globe2,
  HeartHandshake,
  Leaf,
  MapPin,
  Mountain,
  Recycle,
  Rocket,
  UserRound,
  Users,
  Wrench,
  X,
} from 'lucide-react-native';
import { useNavigation } from '@react-navigation/native';
import * as ImagePicker from 'expo-image-picker';

import { api } from '../api/axios';
import { useAuth } from '../store/useAuth';
import { normalizeMediaUrl } from '../utils/media';
import { PROFILE_TITLE_OPTIONS, validateProfile } from '../utils/profileValidation';
import UserAvatar from '../components/ui/UserAvatar';
import { useZeroWasteDialog } from '../components/ui/ZeroWasteDialog';
import KeyboardAwareScreen from '../components/ui/KeyboardAwareScreen';

const TITLE_ICONS = {
  leaf: Leaf,
  globe: Globe2,
  mountain: Mountain,
  wrench: Wrench,
  graduation: GraduationCap,
  recycle: Recycle,
  book: BookOpen,
  heart: HeartHandshake,
  rocket: Rocket,
  users: Users,
};

const FieldShell = ({ icon: Icon, label, helper, error, children }) => (
  <View className={`rounded-[22px] border bg-white px-4 py-3 ${error ? 'border-red-400' : 'border-slate-200'}`}>
    <View className="flex-row items-start">
      <View className={`mt-0.5 h-10 w-10 items-center justify-center rounded-full ${error ? 'bg-red-50' : 'bg-emerald-50'}`}>
        <Icon color={error ? '#DC2626' : '#047857'} size={19} />
      </View>
      <View className="ml-3 min-w-0 flex-1">
        <Text className={`text-xs font-black uppercase tracking-wider ${error ? 'text-red-600' : 'text-slate-500'}`}>{label}</Text>
        {children}
        {error ? <Text accessibilityLiveRegion="polite" className="mt-1 text-xs font-bold leading-4 text-red-600">{error}</Text> : helper ? <Text className="mt-1 text-xs leading-4 text-slate-400">{helper}</Text> : null}
      </View>
    </View>
  </View>
);

export default function EditProfileScreen() {
  const navigation = useNavigation();
  const { user, updateUser } = useAuth();
  const { showDialog } = useZeroWasteDialog();
  const [name, setName] = useState(user?.nombre || '');
  const [location, setLocation] = useState(user?.ubicacion || '');
  const [profileTitle, setProfileTitle] = useState(user?.titulo_perfil || '');
  const [bio, setBio] = useState(user?.biografia || '');
  const [selectedImage, setSelectedImage] = useState(null);
  const [saving, setSaving] = useState(false);
  const [titlePickerVisible, setTitlePickerVisible] = useState(false);
  const [errors, setErrors] = useState({});
  const [formError, setFormError] = useState('');
  const savedRef = useRef(false);

  const initialValues = useMemo(() => ({
    name: user?.nombre || '',
    location: user?.ubicacion || '',
    profileTitle: user?.titulo_perfil || '',
    bio: user?.biografia || '',
  }), [user?.biografia, user?.nombre, user?.titulo_perfil, user?.ubicacion]);
  const hasChanges = Boolean(selectedImage)
    || name !== initialValues.name
    || location !== initialValues.location
    || profileTitle !== initialValues.profileTitle
    || bio !== initialValues.bio;

  useEffect(() => navigation.addListener('beforeRemove', (event) => {
    if (!hasChanges || savedRef.current) return;
    event.preventDefault();
    showDialog({
      type: 'warning',
      title: 'Cambios sin guardar',
      message: 'Si sales ahora, perderás los cambios de tu perfil.',
      primaryLabel: 'Salir sin guardar',
      secondaryLabel: 'Seguir editando',
      onPrimary: () => {
        savedRef.current = true;
        navigation.dispatch(event.data.action);
      },
    });
  }), [hasChanges, navigation, showDialog]);

  const currentAvatar = normalizeMediaUrl(user?.avatar_url ?? user?.foto_perfil, 'perfiles');
  const selectedTitle = PROFILE_TITLE_OPTIONS.find((option) => option.value === profileTitle);
  const SelectedTitleIcon = TITLE_ICONS[selectedTitle?.icon] || Leaf;
  const clearError = (field) => setErrors((current) => ({ ...current, [field]: '' }));

  const pickImage = async () => {
    const permission = await ImagePicker.requestMediaLibraryPermissionsAsync();
    if (!permission.granted) {
      showDialog({ type: 'permission', title: 'Permiso requerido', message: 'Permite el acceso a tus fotografías para cambiar tu perfil.' });
      return;
    }
    const result = await ImagePicker.launchImageLibraryAsync({ mediaTypes: ['images'], quality: 0.85 });
    if (result.canceled || !result.assets?.[0]) return;
    const asset = result.assets[0];
    if (asset.fileSize && asset.fileSize > 5 * 1024 * 1024) {
      showDialog({ type: 'warning', title: 'Imagen demasiado grande', message: 'La imagen debe pesar como máximo 5 MB.' });
      return;
    }
    setSelectedImage(asset);
  };

  const save = async () => {
    const validationError = validateProfile({ name, location, profileTitle, bio });
    if (validationError) {
      setErrors({ [validationError.field]: validationError.message });
      showDialog({ type: 'error', title: validationError.title, message: validationError.message });
      return;
    }
    setErrors({});
    setSaving(true);
    setFormError('');
    try {
      const form = new FormData();
      form.append('nombre', name.trim());
      form.append('ubicacion', location.trim());
      form.append('titulo_perfil', profileTitle.trim());
      form.append('biografia', bio.trim());
      if (selectedImage) {
        form.append('foto_perfil', {
          uri: selectedImage.uri,
          name: selectedImage.fileName || `perfil-${Date.now()}.jpg`,
          type: selectedImage.mimeType || 'image/jpeg',
        });
      }
      const { data } = await api.put('/usuarios/perfil/actualizar', form);
      savedRef.current = true;
      await updateUser(data.perfil);
      showDialog({ type: 'success', title: 'Perfil actualizado', message: 'Tu información y fotografía quedaron sincronizadas.', primaryLabel: 'Continuar', onPrimary: () => navigation.goBack() });
    } catch (error) {
      const message = error.userMessage || 'Intenta nuevamente.';
      setFormError(message);
      showDialog({ type: 'error', title: 'No se pudo guardar', message });
    } finally {
      setSaving(false);
    }
  };

  return (
    <SafeAreaView className="flex-1 bg-slate-50" edges={['top', 'bottom']}>
      <View className="flex-row items-center border-b border-slate-100 bg-white px-4 py-3">
        <TouchableOpacity onPress={() => navigation.goBack()} className="h-11 w-11 items-center justify-center rounded-full bg-slate-100" accessibilityLabel="Volver">
          <ArrowLeft color="#111827" size={20} />
        </TouchableOpacity>
        <View className="ml-4 flex-1"><Text className="text-xl font-black text-slate-950">Editar perfil</Text><Text className="text-xs font-semibold text-slate-500">Información visible para la comunidad</Text></View>
      </View>

      <KeyboardAwareScreen
        contentContainerStyle={{ padding: 20, gap: 12, paddingBottom: 28 }}
        footer={<View className="border-t border-slate-100 bg-white px-5 py-3"><TouchableOpacity disabled={saving || !hasChanges} onPress={save} className="min-h-12 items-center justify-center rounded-2xl bg-emerald-700 disabled:opacity-50" accessibilityState={{ busy: saving, disabled: saving || !hasChanges }}>{saving ? <ActivityIndicator color="white" /> : <Text className="font-black text-white">Guardar cambios</Text>}</TouchableOpacity></View>}
      >
        {formError ? <View className="rounded-2xl border border-red-200 bg-red-50 p-4"><Text accessibilityLiveRegion="polite" className="font-bold text-red-700">{formError}</Text></View> : null}

        <View className="items-center rounded-[28px] border border-slate-100 bg-white px-5 py-6">
          <TouchableOpacity onPress={pickImage} className="relative" accessibilityLabel="Cambiar foto de perfil">
            {selectedImage?.uri ? <Image source={{ uri: selectedImage.uri }} className="h-32 w-32 rounded-full border-4 border-emerald-100 bg-white" resizeMode="cover" /> : <UserAvatar uri={currentAvatar} name={user?.nombre} size={128} style={{ borderWidth: 4, borderColor: '#D1FAE5' }} accessibilityLabel="Avatar actual" />}
            <View className="absolute bottom-1 right-1 h-11 w-11 items-center justify-center rounded-full border-4 border-white bg-emerald-700"><Camera color="white" size={19} /></View>
          </TouchableOpacity>
          <Text className="mt-4 text-lg font-black text-slate-950">{name.trim() || 'Tu nombre'}</Text>
          <Text className="mt-1 text-sm font-semibold text-emerald-700">Cambiar fotografía</Text>
          <Text className="mt-2 text-center text-xs leading-5 text-slate-400">Elige una fotografía nítida que te represente en la comunidad.</Text>
        </View>

        <Text className="mt-3 px-1 text-xs font-black uppercase tracking-widest text-slate-500">Acerca de ti</Text>

        <FieldShell icon={UserRound} label="Nombre completo" helper={`${name.trim().length}/50 · mínimo 10`} error={errors.name}>
          <TextInput value={name} onChangeText={(value) => { setName(value); clearError('name'); }} placeholder="Nombre y apellidos" placeholderTextColor="#94A3B8" maxLength={50} returnKeyType="next" className="min-h-10 py-1 text-[16px] font-semibold text-slate-900" accessibilityLabel="Nombre completo" />
        </FieldShell>

        <FieldShell icon={MapPin} label="Ubicación" helper={`${location.trim().length}/50 · ejemplo: Querétaro, Qro.`} error={errors.location}>
          <TextInput value={location} onChangeText={(value) => { setLocation(value); clearError('location'); }} placeholder="Querétaro, Qro., México" placeholderTextColor="#94A3B8" maxLength={50} returnKeyType="next" className="min-h-10 py-1 text-[16px] font-semibold text-slate-900" accessibilityLabel="Ubicación" />
        </FieldShell>

        <FieldShell icon={SelectedTitleIcon} label="Título o profesión" error={errors.profileTitle}>
          <TouchableOpacity onPress={() => setTitlePickerVisible(true)} className="min-h-11 flex-row items-center justify-between py-1" accessibilityLabel="Elegir título de perfil">
            <Text className={`mr-3 flex-1 text-[15px] font-semibold ${selectedTitle ? 'text-slate-900' : 'text-slate-400'}`}>{selectedTitle?.value || 'Selecciona un título'}</Text>
            <ChevronDown color="#64748B" size={19} />
          </TouchableOpacity>
        </FieldShell>

        <FieldShell icon={AlignLeft} label="Biografía" helper={`${bio.trim().length}/100`} error={errors.bio}>
          <TextInput value={bio} onChangeText={(value) => { setBio(value); clearError('bio'); }} placeholder="Cuéntale algo a la comunidad ZeroWaste" placeholderTextColor="#94A3B8" multiline maxLength={100} textAlignVertical="top" className="min-h-24 py-2 text-[15px] leading-5 text-slate-900" accessibilityLabel="Biografía" />
        </FieldShell>
      </KeyboardAwareScreen>

      <Modal visible={titlePickerVisible} transparent animationType="slide" statusBarTranslucent onRequestClose={() => setTitlePickerVisible(false)}>
        <View className="flex-1 justify-end bg-slate-950/45">
          <Pressable className="flex-1" onPress={() => setTitlePickerVisible(false)} accessibilityLabel="Cerrar selector" />
          <SafeAreaView className="max-h-[78%] rounded-t-[30px] bg-white" edges={['bottom']}>
            <View className="flex-row items-center border-b border-slate-100 px-5 py-4">
              <View className="flex-1"><Text className="text-xl font-black text-slate-950">Elige tu título</Text><Text className="mt-1 text-xs font-semibold text-slate-500">Se mostrará debajo de tu nombre</Text></View>
              <TouchableOpacity onPress={() => setTitlePickerVisible(false)} className="h-11 w-11 items-center justify-center rounded-full bg-slate-100" accessibilityLabel="Cerrar"><X color="#334155" size={20} /></TouchableOpacity>
            </View>
            <ScrollView contentContainerStyle={{ padding: 16, paddingBottom: 24 }}>
              {PROFILE_TITLE_OPTIONS.map((option) => {
                const Icon = TITLE_ICONS[option.icon] || Leaf;
                const selected = option.value === profileTitle;
                return <TouchableOpacity key={option.value} onPress={() => { setProfileTitle(option.value); clearError('profileTitle'); setTitlePickerVisible(false); }} className={`mb-2 min-h-14 flex-row items-center rounded-2xl border px-4 py-3 ${selected ? 'border-emerald-400 bg-emerald-50' : 'border-slate-100 bg-white'}`} accessibilityState={{ selected }}><View className="h-10 w-10 items-center justify-center rounded-full bg-emerald-50"><Icon color="#047857" size={19} /></View><Text className="ml-3 flex-1 font-bold leading-5 text-slate-800">{option.value}</Text>{selected ? <Check color="#047857" size={20} /> : null}</TouchableOpacity>;
              })}
            </ScrollView>
          </SafeAreaView>
        </View>
      </Modal>
    </SafeAreaView>
  );
}
