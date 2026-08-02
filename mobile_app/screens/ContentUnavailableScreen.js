import React from 'react';
import { Text, TouchableOpacity, View } from 'react-native';
import { ArrowLeft, FileQuestion } from 'lucide-react-native';
import { useNavigation } from '@react-navigation/native';
import { SafeAreaView } from 'react-native-safe-area-context';

export default function ContentUnavailableScreen() {
  const navigation = useNavigation();
  return (
    <SafeAreaView className="flex-1 bg-slate-50 px-7" edges={['top', 'bottom']}>
      <View className="flex-1 items-center justify-center">
        <View className="h-20 w-20 items-center justify-center rounded-full bg-emerald-50"><FileQuestion color="#047857" size={34} /></View>
        <Text className="mt-6 text-center text-2xl font-black text-slate-950">Contenido no disponible</Text>
        <Text className="mt-3 text-center leading-6 text-slate-500">El enlace no es válido, el contenido ya no está publicado o no pertenece a una ruta móvil de ZeroWaste.</Text>
        <TouchableOpacity onPress={() => navigation.canGoBack() ? navigation.goBack() : navigation.navigate('Main')} className="mt-7 min-h-12 flex-row items-center justify-center rounded-2xl bg-emerald-700 px-6"><ArrowLeft color="white" size={18} /><Text className="ml-2 font-black text-white">Volver a ZeroWaste</Text></TouchableOpacity>
      </View>
    </SafeAreaView>
  );
}
