import React, { useState } from 'react';
import { View, Text, TextInput, TouchableOpacity, KeyboardAvoidingView, Platform, ActivityIndicator, ScrollView, Alert, Image } from 'react-native';
import { useNavigation } from '@react-navigation/native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { StatusBar } from 'expo-status-bar';
import { ArrowLeft, ImagePlus, Send, Trash2 } from 'lucide-react-native';
import * as ImagePicker from 'expo-image-picker';
import { api } from '../api/axios';
import { useAuth } from '../store/useAuth';

export default function CreatePostScreen() {
  const navigation = useNavigation();
  const { user } = useAuth();
  
  const [title, setTitle] = useState('');
  const [content, setContent] = useState('');
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [categoryId, setCategoryId] = useState(null);
  const [categories, setCategories] = useState([]);
  const [error, setError] = useState('');
  const [selectedImage, setSelectedImage] = useState(null);

  const openImageSource = () => {
    Alert.alert('Agregar imagen', 'Elige el origen de la imagen.', [
      { text: 'Galería', onPress: pickFromLibrary },
      { text: 'Cámara', onPress: takePhoto },
      { text: 'Cancelar', style: 'cancel' },
    ]);
  };

  const usePickerResult = (result) => {
    if (result.canceled || !result.assets?.[0]) return;
    const asset = result.assets[0];
    if (asset.fileSize && asset.fileSize > 5 * 1024 * 1024) {
      setError('La imagen debe pesar como máximo 5 MB.');
      return;
    }
    setSelectedImage(asset);
    setError('');
  };

  const pickFromLibrary = async () => {
    const permission = await ImagePicker.requestMediaLibraryPermissionsAsync();
    if (!permission.granted) {
      setError('Permite el acceso a tus fotografías para elegir una imagen.');
      return;
    }
    usePickerResult(await ImagePicker.launchImageLibraryAsync({ mediaTypes: ['images'], quality: 0.85 }));
  };

  const takePhoto = async () => {
    const permission = await ImagePicker.requestCameraPermissionsAsync();
    if (!permission.granted) {
      setError('Permite el acceso a la cámara para tomar una fotografía.');
      return;
    }
    usePickerResult(await ImagePicker.launchCameraAsync({ mediaTypes: ['images'], quality: 0.85 }));
  };

  React.useEffect(() => {
    api.get('/foro/categorias')
      .then(({ data }) => setCategories(Array.isArray(data) ? data : []))
      .catch((requestError) => setError(requestError.userMessage || 'No se pudieron cargar las categorías.'));
  }, []);

  const handleSubmit = async () => {
    if (!title.trim() || !content.trim() || !categoryId) {
      setError('Completa título, contenido y categoría.');
      return;
    }
    setIsSubmitting(true);
    try {
      if (selectedImage) {
        const form = new FormData();
        form.append('titulo', title.trim());
        form.append('contenido', content.trim());
        form.append('categoria_id', String(categoryId));
        form.append('imagen', {
          uri: selectedImage.uri,
          name: selectedImage.fileName || `foro-${Date.now()}.jpg`,
          type: selectedImage.mimeType || 'image/jpeg',
        });
        await api.post('/foro/posts/con-imagen', form);
      } else {
        await api.post('/foro/posts', {
          titulo: title.trim(), contenido: content.trim(), categoria_id: categoryId,
        });
      }
      navigation.goBack();
    } catch (e) {
      setError(e.userMessage || 'No se pudo crear la publicación.');
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <SafeAreaView className="flex-1 bg-[#FAFAFA]" edges={['top']}>
      <StatusBar style="dark" />
      <KeyboardAvoidingView behavior={Platform.OS === 'ios' ? 'padding' : undefined} style={{ flex: 1 }}>
        <View className="px-5 pt-4 pb-4 flex-row items-center border-b border-gray-100 bg-white shadow-sm z-10">
        <TouchableOpacity onPress={() => navigation.goBack()} className="w-10 h-10 rounded-full bg-gray-50 items-center justify-center">
          <ArrowLeft color="#111827" size={20} />
        </TouchableOpacity>
        <Text className="text-[18px] font-black text-gray-900 ml-4 flex-1">Crear publicación</Text>
        <TouchableOpacity 
          onPress={handleSubmit}
          disabled={!title.trim() || !content.trim() || !categoryId || isSubmitting}
          className={`px-5 py-2 rounded-full flex-row items-center gap-2 ${(!title.trim() || !content.trim() || !categoryId) ? 'bg-gray-200' : 'bg-[#059669]'}`}
        >
          {isSubmitting ? (
            <ActivityIndicator size="small" color="#fff" />
          ) : (
            <>
              <Text className={`font-bold ${(!title.trim() || !content.trim() || !categoryId) ? 'text-gray-400' : 'text-white'}`}>Publicar</Text>
              <Send color={(!title.trim() || !content.trim() || !categoryId) ? '#9CA3AF' : '#fff'} size={14} />
            </>
          )}
        </TouchableOpacity>
      </View>

      <ScrollView className="flex-1" contentContainerStyle={{ paddingHorizontal: 20, paddingVertical: 24, paddingBottom: 40 }} keyboardShouldPersistTaps="handled">
        {error ? <Text className="mb-4 text-red-700 font-bold">{error}</Text> : null}
        <ScrollView horizontal showsHorizontalScrollIndicator={false} className="mb-5">
          {categories.map((category) => (
            <TouchableOpacity key={category.id} onPress={() => setCategoryId(category.id)} className={`mr-2 rounded-full px-4 py-2 ${categoryId === category.id ? 'bg-emerald-700' : 'bg-gray-100'}`}>
              <Text className={categoryId === category.id ? 'text-white font-bold' : 'text-gray-700 font-bold'}>{category.nombre}</Text>
            </TouchableOpacity>
          ))}
        </ScrollView>
        <TextInput
          placeholder="Título de tu publicación..."
          placeholderTextColor="#9CA3AF"
          value={title}
          onChangeText={setTitle}
          className="font-black text-[22px] text-gray-900 mb-4"
          autoFocus
        />
        <TextInput
          placeholder="¿Qué quieres compartir con la comunidad?"
          placeholderTextColor="#9CA3AF"
          value={content}
          onChangeText={setContent}
          multiline
          className="text-[16px] text-gray-600 leading-relaxed min-h-[200px]"
          textAlignVertical="top"
        />
        {selectedImage ? (
          <View className="mt-5 overflow-hidden rounded-2xl border border-gray-200 bg-white">
            <Image source={{ uri: selectedImage.uri }} className="h-56 w-full bg-gray-100" resizeMode="cover" />
            <TouchableOpacity onPress={() => setSelectedImage(null)} className="m-3 flex-row items-center justify-center rounded-xl bg-red-50 py-3">
              <Trash2 color="#DC2626" size={18} />
              <Text className="ml-2 font-bold text-red-600">Eliminar imagen</Text>
            </TouchableOpacity>
          </View>
        ) : (
          <TouchableOpacity onPress={openImageSource} className="mt-5 flex-row items-center justify-center rounded-2xl border border-dashed border-emerald-400 bg-emerald-50 py-5">
            <ImagePlus color="#047857" size={22} />
            <Text className="ml-2 font-black text-emerald-700">Agregar imagen</Text>
          </TouchableOpacity>
        )}
      </ScrollView>
      </KeyboardAvoidingView>
    </SafeAreaView>
  );
}
