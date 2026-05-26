import React, { useState } from 'react';
import { View, Text, ScrollView, SafeAreaView, TouchableOpacity, TextInput } from 'react-native';
import { MaterialIcons } from '@expo/vector-icons';

// Mock Data
const iaRecommendations = [
  "Notamos que se está reciclando más vidrio últimamente. ¡Excelente!",
  "El centro 'El Sol' tiene alta demanda hoy, te sugerimos ir por la tarde."
];

const topPoints = [
  { id: 1, nombre: 'Centro Bicentenario', tipo: 'Centro de Acopio', promedio: 4.8, direccion: 'Parque Bicentenario' },
  { id: 2, nombre: 'Recicladora El Sol', tipo: 'Privado', promedio: 4.5, direccion: 'Av. Universidad' }
];

export default function Recomendaciones() {
  const [search, setSearch] = useState('');

  return (
    <SafeAreaView className="flex-1 bg-white dark:bg-[#062C25]">
      <ScrollView className="flex-1" showsVerticalScrollIndicator={false}>
        
        {/* Header */}
        <View className="p-6 items-center">
          <View className="px-4 py-1.5 rounded-full bg-[#10B981]/10 border border-[#10B981]/30 mb-4 flex-row items-center">
            <View className="w-2 h-2 rounded-full bg-[#10B981] mr-2" />
            <Text className="text-[#10B981] text-xs font-bold tracking-wider">IMPULSADO POR IA + COMUNIDAD</Text>
          </View>
          <Text className="text-3xl font-extrabold text-[#064E3B] dark:text-white text-center mb-2">
            Recomendaciones de la <Text className="text-[#10B981]">Comunidad</Text>
          </Text>
          <Text className="text-gray-500 dark:text-emerald-100/80 text-center text-sm px-4">
            Análisis inteligente del sentimiento colectivo y los puntos de reciclaje mejor valorados.
          </Text>
        </View>

        {/* Pulso de la Comunidad */}
        <View className="px-6 py-4 bg-[#F0FDF4] dark:bg-[#022C22] mx-4 rounded-3xl shadow-sm border border-emerald-100 dark:border-emerald-900 mb-8">
          <View className="flex-row items-center mb-4">
            <MaterialIcons name="psychology" size={24} color="#10B981" />
            <Text className="text-xl font-bold text-[#064E3B] dark:text-white ml-2">Pulso de la Comunidad</Text>
          </View>
          
          <Text className="text-sm font-bold text-gray-500 dark:text-emerald-100/70 mb-3 text-center">SENTIMIENTO GLOBAL NLP</Text>
          
          {/* Mock Sentiment Bar */}
          <View className="h-4 rounded-full flex-row overflow-hidden mb-3">
            <View className="bg-[#10B981] h-full" style={{ width: '65%' }} />
            <View className="bg-gray-400 h-full" style={{ width: '20%' }} />
            <View className="bg-red-400 h-full" style={{ width: '15%' }} />
          </View>

          <View className="flex-row justify-between mb-6">
            <Text className="text-xs font-bold text-[#10B981]">65% Positivo</Text>
            <Text className="text-xs font-bold text-gray-500">20% Neutro</Text>
            <Text className="text-xs font-bold text-red-500">15% Negativo</Text>
          </View>

          <Text className="font-bold text-[#064E3B] dark:text-white mb-2">Recomendaciones del Algoritmo</Text>
          {iaRecommendations.map((rec, index) => (
            <View key={index} className="bg-white dark:bg-[#062C25] rounded-2xl p-4 mb-2 flex-row items-center border border-gray-100 dark:border-emerald-800">
              <MaterialIcons name="electric-bolt" size={20} color="#10B981" />
              <Text className="text-gray-700 dark:text-gray-300 text-xs ml-3 flex-1">{rec}</Text>
            </View>
          ))}
        </View>

        {/* Puntos Recomendados */}
        <View className="px-6 mb-8">
          <View className="flex-row items-center bg-gray-50 dark:bg-emerald-900/40 rounded-full px-4 py-2 border border-emerald-100 dark:border-emerald-800 mb-6">
            <MaterialIcons name="search" size={20} color="#10B981" />
            <TextInput
              className="flex-1 ml-2 text-[#064E3B] dark:text-white font-medium h-8"
              placeholder="Buscar puntos..."
              placeholderTextColor="#9CA3AF"
              value={search}
              onChangeText={setSearch}
            />
          </View>

          <Text className="text-xl font-extrabold text-[#064E3B] dark:text-white mb-4">Top Recomendados</Text>

          {topPoints.map((punto, index) => (
            <TouchableOpacity 
              key={punto.id}
              className="bg-white dark:bg-[#022C22] rounded-3xl p-4 mb-3 flex-row items-center shadow-sm border border-emerald-50 dark:border-emerald-900"
            >
              <View className={`w-8 h-8 rounded-full items-center justify-center mr-4 ${index === 0 ? 'bg-yellow-400' : 'bg-gray-300'}`}>
                <Text className="text-white font-black">{index + 1}</Text>
              </View>
              <View className="flex-1">
                <Text className="font-bold text-[#064E3B] dark:text-white">{punto.nombre}</Text>
                <Text className="text-xs text-gray-500">{punto.direccion}</Text>
              </View>
              <View className="flex-row items-center">
                <MaterialIcons name="star" size={16} color="#FBBF24" />
                <Text className="text-sm font-bold text-gray-600 dark:text-gray-300 ml-1">{punto.promedio}</Text>
              </View>
            </TouchableOpacity>
          ))}
        </View>

        {/* CTA */}
        <View className="px-6 mb-10">
          <TouchableOpacity className="bg-[#107050] rounded-3xl p-6 items-center flex-row justify-center shadow-md">
            <MaterialIcons name="add-location-alt" size={24} color="white" />
            <Text className="text-white font-black text-lg ml-3">¿Conoces un nuevo punto?</Text>
          </TouchableOpacity>
        </View>

      </ScrollView>
    </SafeAreaView>
  );
}
