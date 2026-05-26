import React from 'react';
import { View, Text, ScrollView, Image, TouchableOpacity, SafeAreaView, Dimensions } from 'react-native';
import { MaterialIcons } from '@expo/vector-icons';
import { useRouter } from 'expo-router';

const { width } = Dimensions.get('window');

// Mock data based on Flask templates
const campaigns = [
  {
    id: 1,
    nombre: 'Limpieza Queretana',
    fecha_inicio: '15 de Ene, 2026',
    descripcion: 'Únete a la gran brigada de limpieza en el centro histórico. Trae tus guantes y bolsas reutilizables.',
    lugar: 'Centro Histórico',
    tipo_etiqueta: 'Campaña',
    imagen_url: require('../../assets/images/composta.png')
  },
  {
    id: 2,
    nombre: 'Reciclatón Lidera',
    fecha_inicio: '20 de Feb, 2026',
    descripcion: 'Recolecta de electrónicos y electrodomésticos en desuso.',
    lugar: 'Parque Bicentenario',
    tipo_etiqueta: 'Recolección',
    imagen_url: require('../../assets/images/solar.png')
  }
];

export default function Inicio() {
  const router = useRouter();

  return (
    <SafeAreaView className="flex-1 bg-white dark:bg-[#062C25]">
      <ScrollView className="flex-1" showsVerticalScrollIndicator={false}>
        
        {/* Hero Section */}
        <View className="relative w-full h-[400px] bg-[#022C22] justify-center overflow-hidden">
          <Image 
            source={require('../../assets/images/plasticos.png')} 
            className="absolute inset-0 w-full h-full opacity-40"
            resizeMode="cover"
          />
          <View className="absolute inset-0 bg-[#022C22]/60" />
          
          <View className="px-6 relative z-10">
            <View className="self-start px-4 py-1.5 rounded-full border border-emerald-400/50 bg-[#10B981]/20 mb-4 flex-row items-center">
              <View className="w-2 h-2 rounded-full bg-[#10B981] mr-2 shadow-[0_0_8px_#10B981]" />
              <Text className="text-white text-xs font-bold tracking-wider">TENDENCIA EN ECOLOGÍA</Text>
            </View>
            
            <Text className="text-4xl font-extrabold text-white mb-4">
              Reciclar plástico: {'\n'}
              <Text className="text-[#34D399]">10 consejos</Text> para reducir.
            </Text>
            
            <Text className="text-emerald-50 text-base mb-6 leading-relaxed">
              Pequeños cambios diarios generan un impacto global masivo. Descubre cómo tu hogar puede ser parte de la solución Zero Waste hoy mismo.
            </Text>

            <TouchableOpacity className="flex-row items-center">
              <Text className="text-[#10B981] font-bold text-lg mr-2 border-b-2 border-[#10B981]">Leer más</Text>
              <MaterialIcons name="arrow-right-alt" size={24} color="#10B981" />
            </TouchableOpacity>
          </View>
        </View>

        {/* Noticia Local Section */}
        <View className="py-10 px-6 bg-white dark:bg-[#062C25]">
          <View className="rounded-3xl overflow-hidden shadow-lg mb-6 bg-white dark:bg-[#022C22]">
            <Image 
              source={require('../../assets/images/qrocapita.jpg')} 
              className="w-full h-48"
              resizeMode="cover"
            />
            <View className="absolute top-4 left-4 bg-[#059669] px-4 py-1.5 rounded-full flex-row items-center">
              <View className="w-2 h-2 rounded-full bg-[#022C22] mr-2" />
              <Text className="text-[#022C22] text-xs font-bold uppercase tracking-wider">Noticia Local</Text>
            </View>
          </View>
          
          <View className="flex-row items-center mb-3">
            <MaterialIcons name="calendar-today" size={16} color="#059669" />
            <Text className="text-[#059669] text-sm font-bold ml-2 uppercase tracking-wide">Lunes 8 de Ene, 2024</Text>
          </View>
          
          <Text className="text-3xl font-extrabold text-[#064E3B] dark:text-white mb-4">
            Querétaro recicla {'\n'}
            <Text className="text-[#059669] underline">2.4 kg per cápita</Text> al día
          </Text>
          
          <Text className="text-gray-600 dark:text-emerald-100/80 text-base leading-relaxed mb-6">
            En Querétaro se ha incrementado el porcentaje de reciclaje de los residuos hasta llegar al 30% de los 2.4 kilos per cápita que se generan diariamente, afirmó el presidente de la Asociación Nacional de Reciclaje.
          </Text>

          <TouchableOpacity className="flex-row items-center">
            <Text className="text-[#064E3B] dark:text-[#10B981] font-bold text-base mr-2 border-b-2 border-[#064E3B] dark:border-[#10B981]">Leer artículo completo</Text>
            <MaterialIcons name="arrow-right-alt" size={24} color="#064E3B" />
          </TouchableOpacity>
        </View>

        {/* Campañas Activas */}
        <View className="py-10 bg-[#F0FDF4] dark:bg-[#022C22]">
          <Text className="text-3xl font-extrabold text-center text-[#064E3B] dark:text-white mb-8">
            Campañas <Text className="text-[#059669]">Activas</Text>
          </Text>

          <ScrollView horizontal showsHorizontalScrollIndicator={false} className="pl-6">
            {campaigns.map((camp) => (
              <View key={camp.id} style={{ width: width * 0.8 }} className="mr-6 bg-white dark:bg-[#062C25] rounded-3xl overflow-hidden shadow-sm border border-gray-100 dark:border-emerald-900 mb-4">
                <View className="h-40 relative">
                  <Image source={camp.imagen_url} className="w-full h-full" resizeMode="cover" />
                  <View className="absolute inset-0 bg-black/20" />
                  <View className="absolute top-4 left-4 bg-yellow-400 px-3 py-1 rounded-full">
                    <Text className="text-yellow-900 text-xs font-bold uppercase">{camp.tipo_etiqueta}</Text>
                  </View>
                </View>
                <View className="p-5">
                  <View className="flex-row items-center mb-2">
                    <MaterialIcons name="calendar-today" size={16} color="#059669" />
                    <Text className="text-gray-500 dark:text-emerald-100/70 text-xs font-bold ml-2">{camp.fecha_inicio}</Text>
                  </View>
                  <Text className="text-xl font-black text-[#064E3B] dark:text-white mb-2">{camp.nombre}</Text>
                  <Text className="text-gray-500 dark:text-gray-400 text-sm mb-4 line-clamp-2">{camp.descripcion}</Text>
                  
                  <View className="flex-row items-center border-t border-gray-100 dark:border-emerald-800/50 pt-4 mb-4">
                    <MaterialIcons name="location-on" size={16} color="#9CA3AF" />
                    <Text className="text-gray-500 dark:text-gray-400 text-xs ml-2 flex-1 truncate">{camp.lugar}</Text>
                  </View>

                  <TouchableOpacity className="bg-[#10B981] flex-row items-center justify-center py-3 rounded-xl shadow-sm">
                    <MaterialIcons name="rocket-launch" size={16} color="white" />
                    <Text className="text-white font-bold ml-2">Unirse a Campaña</Text>
                  </TouchableOpacity>
                </View>
              </View>
            ))}
          </ScrollView>
        </View>

      </ScrollView>
    </SafeAreaView>
  );
}
