import React, { useState } from 'react';
import { ActivityIndicator, Alert, Image, ScrollView, Text, TextInput, TouchableOpacity, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { ArrowLeft, Camera } from 'lucide-react-native';
import { useNavigation } from '@react-navigation/native';
import * as ImagePicker from 'expo-image-picker';

import { api } from '../api/axios';
import { useAuth } from '../store/useAuth';
import { normalizeMediaUrl } from '../utils/media';
import UserAvatar from '../components/ui/UserAvatar';

export default function EditProfileScreen() {
  const navigation = useNavigation();
  const { user, updateUser } = useAuth();
  const [name, setName] = useState(user?.nombre || '');
  const [location, setLocation] = useState(user?.ubicacion || '');
  const [bio, setBio] = useState(user?.biografia || '');
  const [selectedImage, setSelectedImage] = useState(null);
  const [saving, setSaving] = useState(false);

  const currentAvatar = normalizeMediaUrl(user?.avatar_url ?? user?.foto_perfil, 'perfiles');

  const pickImage = async () => {
    const permission = await ImagePicker.requestMediaLibraryPermissionsAsync();
    if (!permission.granted) {
      Alert.alert('Permiso requerido', 'Permite el acceso a tus fotografías para cambiar tu perfil.');
      return;
    }
    const result = await ImagePicker.launchImageLibraryAsync({ mediaTypes: ['images'], quality: 0.85 });
    if (result.canceled || !result.assets?.[0]) return;
    const asset = result.assets[0];
    if (asset.fileSize && asset.fileSize > 5 * 1024 * 1024) {
      Alert.alert('Imagen demasiado grande', 'La imagen debe pesar como máximo 5 MB.');
      return;
    }
    setSelectedImage(asset);
  };

  const save = async () => {
    if (!name.trim()) return;
    setSaving(true);
    try {
      const form = new FormData();
      form.append('nombre', name.trim());
      form.append('ubicacion', location.trim());
      form.append('biografia', bio.trim());
      if (selectedImage) {
        form.append('foto_perfil', {
          uri: selectedImage.uri,
          name: selectedImage.fileName || `perfil-${Date.now()}.jpg`,
          type: selectedImage.mimeType || 'image/jpeg',
        });
      }
      const { data } = await api.put('/usuarios/perfil/actualizar', form);
      await updateUser(data.perfil);
      Alert.alert('Perfil actualizado', 'Tus datos y tu fotografía quedaron sincronizados.');
      navigation.goBack();
    } catch (error) {
      Alert.alert('No se pudo guardar', error.userMessage || 'Intenta nuevamente.');
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
      <ScrollView contentContainerStyle={{ padding: 20, gap: 16 }} keyboardShouldPersistTaps="handled">
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
          ['Nombre', name, setName],
          ['Ubicación', location, setLocation],
          ['Biografía', bio, setBio],
        ].map(([label, value, setter]) => (
          <View key={label}>
            <Text className="mb-2 font-bold text-gray-700">{label}</Text>
            <TextInput
              value={value}
              onChangeText={setter}
              multiline={label === 'Biografía'}
              className={`rounded-2xl border border-gray-200 bg-white px-4 py-3 ${label === 'Biografía' ? 'min-h-28' : ''}`}
            />
          </View>
        ))}
        <TouchableOpacity disabled={saving || !name.trim()} onPress={save} className="mt-3 items-center rounded-2xl bg-emerald-700 py-4">
          {saving ? <ActivityIndicator color="white" /> : <Text className="font-black text-white">Guardar cambios</Text>}
        </TouchableOpacity>
      </ScrollView>
    </SafeAreaView>
  );
}
