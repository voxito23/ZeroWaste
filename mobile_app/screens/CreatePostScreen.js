import React, { useState } from 'react';
import { View, Text, TextInput, TouchableOpacity, KeyboardAvoidingView, Platform, ActivityIndicator } from 'react-native';
import { useNavigation } from 'expo-router';
import { SafeAreaView } from 'react-native-safe-area-context';
import { StatusBar } from 'expo-status-bar';
import { ArrowLeft, Send } from 'lucide-react-native';
import { api } from '../api/axios';
import { useAuth } from '../store/useAuth';

export default function CreatePostScreen() {
  const navigation = useNavigation();
  const { user } = useAuth();
  
  const [title, setTitle] = useState('');
  const [content, setContent] = useState('');
  const [isSubmitting, setIsSubmitting] = useState(false);

  const handleSubmit = async () => {
    if (!title.trim() || !content.trim()) return;
    setIsSubmitting(true);
    try {
      // Por ahora mandamos categoria 1 (Todo) por defecto o null
      await api.post('/foro/posts', {
        titulo: title,
        contenido: content,
        categoria_id: 1, // Require category id in backend usually
      });
      navigation.goBack();
    } catch (e) {
      console.log('Error creando post:', e);
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
          disabled={!title.trim() || !content.trim() || isSubmitting}
          className={`px-5 py-2 rounded-full flex-row items-center gap-2 ${(!title.trim() || !content.trim()) ? 'bg-gray-200' : 'bg-[#059669]'}`}
        >
          {isSubmitting ? (
            <ActivityIndicator size="small" color="#fff" />
          ) : (
            <>
              <Text className={`font-bold ${(!title.trim() || !content.trim()) ? 'text-gray-400' : 'text-white'}`}>Publicar</Text>
              <Send color={(!title.trim() || !content.trim()) ? '#9CA3AF' : '#fff'} size={14} />
            </>
          )}
        </TouchableOpacity>
      </View>

      <View className="px-5 py-6">
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
      </View>
      </KeyboardAvoidingView>
    </SafeAreaView>
  );
}