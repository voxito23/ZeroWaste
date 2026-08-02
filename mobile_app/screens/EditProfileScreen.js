import React, { useEffect, useMemo, useRef, useState } from 'react';
import { ActivityIndicator, Image, Text, TextInput, TouchableOpacity, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { ArrowLeft, Camera } from 'lucide-react-native';
import { useNavigation } from '@react-navigation/native';
import * as ImagePicker from 'expo-image-picker';

import { api } from '../api/axios';
import { useAuth } from '../store/useAuth';
import { normalizeMediaUrl } from '../utils/media';
import UserAvatar from '../components/ui/UserAvatar';
import { useZeroWasteDialog } from '../components/ui/ZeroWasteDialog';
import KeyboardAwareScreen from '../components/ui/KeyboardAwareScreen';

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
  const [nameError, setNameError] = useState('');
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
    if (!name.trim()) {
      setNameError('Escribe tu nombre.');
      return;
    }
    if (name.trim().length < 2 || name.trim().length > 50) {
      setNameError('El nombre debe tener entre 2 y 50 caracteres.');
      return;
    }
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
      showDialog({ type: 'success', title: 'Perfil actualizado', message: 'Tus datos y tu fotografía quedaron sincronizados.', primaryLabel: 'Continuar', onPrimary: () => navigation.goBack() });
    } catch (error) {
      const message = error.userMessage || 'Intenta nuevamente.';
      setFormError(message);
      showDialog({ type: 'error', title: 'No se pudo guardar', message });
    } finally {
      setSaving(false);
    }
  };

  return (
    <SafeAreaView className="flex-1 bg-gray-50">
      <View className="flex-row items-center bg-white px-5 py-4">
        <TouchableOpacity onPress={() => navigation.goBack()} className="h-10 w-10 items-center justify-center rounded-full bg-gray-100">
          <ArrowLeft color="#111827" size={20} />
        </TouchableOpacity>
        <Text className="ml-4 text-xl font-black">Editar perfil</Text>
      </View>
      <KeyboardAwareScreen
        contentContainerStyle={{ padding: 20, gap: 16, paddingBottom: 28 }}
        footer={<View className="border-t border-slate-100 bg-white px-5 py-3"><TouchableOpacity disabled={saving || !name.trim()} onPress={save} className="items-center rounded-2xl bg-emerald-700 py-4 disabled:opacity-50">{saving ? <ActivityIndicator color="white" /> : <Text className="font-black text-white">Guardar cambios</Text>}</TouchableOpacity></View>}
      >
        {formError ? <View className="rounded-2xl border border-red-200 bg-red-50 p-4"><Text accessibilityLiveRegion="polite" className="font-bold text-red-700">{formError}</Text></View> : null}
        <View className="items-center pb-2">
          <TouchableOpacity onPress={pickImage} className="relative" accessibilityLabel="Cambiar foto de perfil">
            {selectedImage?.uri ? (
              <Image source={{ uri: selectedImage.uri }} className="h-28 w-28 rounded-full border-4 border-emerald-100 bg-white" resizeMode="cover" />
            ) : (
              <UserAvatar uri={currentAvatar} name={user?.nombre} size={112} style={{ borderWidth: 4, borderColor: '#D1FAE5' }} accessibilityLabel="Avatar actual" />
            )}
            <View className="absolute bottom-0 right-0 h-10 w-10 items-center justify-center rounded-full bg-emerald-700">
              <Camera color="white" size={19} />
            </View>
          </TouchableOpacity>
          <Text className="mt-3 font-bold text-emerald-700">Cambiar fotografía</Text>
        </View>
        {[
          ['Nombre', name, setName, 50],
          ['Título de perfil', profileTitle, setProfileTitle, 100],
          ['Ubicación', location, setLocation, 50],
          ['Biografía', bio, setBio, 500],
        ].map(([label, value, setter, maximum]) => (
          <View key={label}>
            <Text className="mb-2 font-bold text-gray-700">{label}</Text>
            <TextInput
              value={value}
              onChangeText={(value) => { setter(value); if (label === 'Nombre') setNameError(''); }}
              multiline={label === 'Biografía'}
              returnKeyType={label === 'Biografía' ? 'default' : 'next'}
              textAlignVertical={label === 'Biografía' ? 'top' : 'center'}
              maxLength={maximum}
              accessibilityLabel={label}
              className={`rounded-2xl border border-gray-200 bg-white px-4 py-3 ${label === 'Biografía' ? 'min-h-28' : ''}`}
            />
            {label === 'Nombre' && nameError ? <Text className="mt-2 text-xs font-bold text-red-600">{nameError}</Text> : null}
            {label === 'Biografía' ? <Text className="mt-2 text-right text-xs font-semibold text-slate-400">{bio.length}/500</Text> : null}
          </View>
        ))}
      </KeyboardAwareScreen>
    </SafeAreaView>
  );
}
