import React, { useState } from 'react';
import { View, Text, TextInput, TouchableOpacity, ActivityIndicator, ScrollView, Image, Modal, Keyboard, TouchableWithoutFeedback } from 'react-native';
import { useNavigation } from '@react-navigation/native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { StatusBar } from 'expo-status-bar';
import { ArrowLeft, Camera, ImagePlus, Images, Send, Trash2, X } from 'lucide-react-native';
import * as ImagePicker from 'expo-image-picker';
import { api } from '../api/axios';
import { useAuth } from '../store/useAuth';
import UserAvatar from '../components/ui/UserAvatar';
import ZeroWasteDialog from '../components/ui/ZeroWasteDialog';
import { resolveAvatar, resolveDisplayName } from '../utils/user';
import KeyboardAwareScreen from '../components/ui/KeyboardAwareScreen';
import { validatePickedProfileImage } from '../utils/imageUpload';

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
  const [sourceVisible, setSourceVisible] = useState(false);
  const [discardVisible, setDiscardVisible] = useState(false);

  const openImageSource = () => {
    Keyboard.dismiss();
    setSourceVisible(true);
  };

  const hasDraft = Boolean(title.trim() || content.trim() || selectedImage);
  const closeComposer = () => hasDraft ? setDiscardVisible(true) : navigation.goBack();

  const usePickerResult = async (result) => {
    if (result.canceled || !result.assets?.[0]) return;
    const validation = await validatePickedProfileImage(result.assets[0]);
    if (!validation.valid) {
      setError(validation.message);
      return;
    }
    setSelectedImage(validation.asset);
    setError('');
  };

  const pickFromLibrary = async () => {
    const permission = await ImagePicker.requestMediaLibraryPermissionsAsync();
    if (!permission.granted) {
      setError('Permite el acceso a tus fotografías para elegir una imagen.');
      return;
    }
    await usePickerResult(await ImagePicker.launchImageLibraryAsync({ mediaTypes: ['images'], allowsEditing: true, aspect: [16, 10], quality: 0.6 }));
  };

  const takePhoto = async () => {
    const permission = await ImagePicker.requestCameraPermissionsAsync();
    if (!permission.granted) {
      setError('Permite el acceso a la cámara para tomar una fotografía.');
      return;
    }
    await usePickerResult(await ImagePicker.launchCameraAsync({ mediaTypes: ['images'], allowsEditing: true, aspect: [16, 10], quality: 0.6 }));
  };

  React.useEffect(() => {
    api.get('/foro/categorias')
      .then(({ data }) => setCategories(Array.isArray(data) ? data : []))
      .catch((requestError) => setError(requestError.userMessage || 'No se pudieron cargar las categorías.'));
  }, []);

  const handleSubmit = async () => {
    if (isSubmitting) return;
    if (!title.trim() || !content.trim() || !categoryId) {
      setError('Completa título, contenido y categoría.');
      return;
    }
    setIsSubmitting(true);
    try {
      let createdPost;
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
        ({ data: createdPost } = await api.post('/foro/posts/con-imagen', form));
      } else {
        ({ data: createdPost } = await api.post('/foro/posts', {
          titulo: title.trim(), contenido: content.trim(), categoria_id: categoryId,
        }));
      }
      navigation.navigate('Main', { screen: 'Forum', params: { createdPost, updateKey: Date.now() } });
    } catch (e) {
      setError(e.response?.data?.detail || e.userMessage || 'No se pudo crear la publicación.');
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <SafeAreaView className="flex-1 bg-slate-50" edges={['top', 'bottom']}>
      <StatusBar style="dark" />
      <View className="z-10 flex-row items-center border-b border-slate-100 bg-white px-5 py-3">
        <TouchableOpacity onPress={closeComposer} className="h-11 w-11 items-center justify-center rounded-full bg-slate-100" accessibilityLabel="Cerrar publicación">
          <ArrowLeft color="#111827" size={20} />
        </TouchableOpacity>
        <Text className="text-[18px] font-black text-gray-900 ml-4 flex-1">Crear publicación</Text>
        <TouchableOpacity 
          onPress={handleSubmit}
          disabled={!title.trim() || !content.trim() || !categoryId || isSubmitting}
          className={`min-h-11 flex-row items-center gap-2 rounded-full px-5 ${(!title.trim() || !content.trim() || !categoryId) ? 'bg-gray-200' : 'bg-[#059669]'}`}
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

      <KeyboardAwareScreen contentContainerStyle={{ paddingHorizontal: 18, paddingVertical: 20, paddingBottom: 40 }}>
        <View className="rounded-[28px] border border-slate-100 bg-white p-5">
        <View className="mb-5 flex-row items-center"><UserAvatar uri={resolveAvatar(user)} name={resolveDisplayName(user)} size={48} /><View className="ml-3 flex-1"><Text className="text-base font-black text-slate-950">{resolveDisplayName(user)}</Text><Text className="mt-0.5 text-xs font-semibold text-slate-500">Compartir con la comunidad ZeroWaste</Text></View></View>
        {error ? <View className="mb-4 rounded-2xl border border-red-100 bg-red-50 p-3"><Text className="font-bold leading-5 text-red-700">{error}</Text></View> : null}
        <Text className="mb-2 text-xs font-black uppercase tracking-widest text-slate-500">Categoría</Text>
        <ScrollView horizontal showsHorizontalScrollIndicator={false} className="mb-5" contentContainerStyle={{ paddingRight: 12 }}>
          {categories.map((category) => (
            <TouchableOpacity key={category.id} onPress={() => setCategoryId(category.id)} className={`mr-2 min-h-11 justify-center rounded-full px-4 ${categoryId === category.id ? 'bg-emerald-700' : 'border border-slate-200 bg-white'}`} accessibilityState={{ selected: categoryId === category.id }}>
              <Text className={categoryId === category.id ? 'text-white font-bold' : 'text-gray-700 font-bold'}>{category.nombre}</Text>
            </TouchableOpacity>
          ))}
        </ScrollView>
        <TextInput
          placeholder="Título de tu publicación..."
          placeholderTextColor="#9CA3AF"
          value={title}
          onChangeText={setTitle}
          className="mb-1 text-[22px] font-black leading-7 text-gray-900"
          maxLength={200}
          autoFocus
        />
        <Text className="mb-4 text-right text-[11px] font-bold text-slate-400">{title.length}/200</Text><View className="h-px bg-slate-100" /><TextInput
          placeholder="¿Qué quieres compartir con la comunidad?"
          placeholderTextColor="#9CA3AF"
          value={content}
          onChangeText={setContent}
          multiline
          className="min-h-[180px] pt-4 text-[16px] leading-6 text-gray-700"
          textAlignVertical="top"
          maxLength={5000}
        />
        {selectedImage ? (
          <View className="mt-3 overflow-hidden rounded-2xl border border-gray-200 bg-white">
            <Image source={{ uri: selectedImage.uri }} className="w-full bg-gray-100" style={{ aspectRatio: 16 / 10 }} resizeMode="cover" />
            <TouchableOpacity onPress={() => setSelectedImage(null)} className="absolute right-3 top-3 h-11 w-11 items-center justify-center rounded-full bg-slate-950/75" accessibilityLabel="Eliminar imagen">
              <Trash2 color="white" size={18} />
            </TouchableOpacity>
          </View>
        ) : (
          <TouchableOpacity onPress={openImageSource} className="mt-3 min-h-14 flex-row items-center justify-center rounded-2xl border border-emerald-200 bg-emerald-50">
            <ImagePlus color="#047857" size={22} />
            <Text className="ml-2 font-black text-emerald-700">Agregar imagen</Text>
          </TouchableOpacity>
        )}<Text className="mt-3 text-right text-[11px] font-bold text-slate-400">{content.length}/5000</Text></View>
      </KeyboardAwareScreen>
      <Modal visible={sourceVisible} transparent animationType="slide" onRequestClose={() => setSourceVisible(false)}><TouchableWithoutFeedback onPress={() => setSourceVisible(false)}><View className="flex-1 justify-end bg-slate-950/45"><TouchableWithoutFeedback><View className="rounded-t-[30px] bg-white p-6"><View className="flex-row items-center justify-between"><View><Text className="text-xl font-black text-slate-950">Agregar imagen</Text><Text className="mt-1 text-sm text-slate-500">Elige una fotografía clara y relacionada.</Text></View><TouchableOpacity onPress={() => setSourceVisible(false)} className="h-11 w-11 items-center justify-center rounded-full bg-slate-100"><X color="#334155" size={20} /></TouchableOpacity></View><TouchableOpacity onPress={() => { setSourceVisible(false); void pickFromLibrary(); }} className="mt-6 min-h-14 flex-row items-center rounded-2xl bg-emerald-700 px-5"><Images color="white" size={21} /><Text className="ml-3 font-black text-white">Elegir de la galería</Text></TouchableOpacity><TouchableOpacity onPress={() => { setSourceVisible(false); void takePhoto(); }} className="mt-3 min-h-14 flex-row items-center rounded-2xl border border-slate-200 px-5"><Camera color="#047857" size={21} /><Text className="ml-3 font-black text-slate-800">Tomar fotografía</Text></TouchableOpacity></View></TouchableWithoutFeedback></View></TouchableWithoutFeedback></Modal>
      <ZeroWasteDialog visible={discardVisible} type="warning" title="¿Descartar publicación?" message="El texto y la imagen que agregaste no se guardarán." primaryLabel="Descartar" onPrimary={() => { setDiscardVisible(false); navigation.goBack(); }} secondaryLabel="Seguir editando" onSecondary={() => setDiscardVisible(false)} />
    </SafeAreaView>
  );
}
