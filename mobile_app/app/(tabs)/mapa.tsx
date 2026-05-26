import React, { useState } from 'react';
import { View, Text, TextInput, TouchableOpacity, ScrollView, SafeAreaView, Dimensions, StyleSheet } from 'react-native';
import { MaterialIcons, FontAwesome5 } from '@expo/vector-icons';
import { Platform } from 'react-native';

let MapView: any = View;
let Marker: any = View;
let Callout: any = View;

if (Platform.OS !== 'web') {
  const Maps = require('react-native-maps');
  MapView = Maps.default;
  Marker = Maps.Marker;
  Callout = Maps.Callout;
}

const { width, height } = Dimensions.get('window');

const mockPuntos = [
  {
    id: 1,
    nombre: 'Centro de Acopio Bicentenario',
    latitud: 20.612,
    longitud: -100.412,
    materiales: 'Plástico, Cartón, Electrónicos',
    promedio: 4.8,
    total_reviews: 12
  },
  {
    id: 2,
    nombre: 'Recicladora El Sol',
    latitud: 20.598,
    longitud: -100.388,
    materiales: 'Vidrio, Metal',
    promedio: 4.2,
    total_reviews: 5
  }
];

export default function Mapa() {
  const [search, setSearch] = useState('');

  return (
    <SafeAreaView className="flex-1 bg-[#F0FDF4] dark:bg-[#062C25]">
      {/* Top Search & Filters Panel */}
      <View className="bg-white dark:bg-[#022C22] p-4 shadow-sm z-10">
        <Text className="text-2xl font-extrabold text-[#064E3B] dark:text-white mb-3">Puntos de Reciclaje</Text>
        
        <View className="flex-row items-center bg-gray-50 dark:bg-emerald-900/40 rounded-full px-4 py-2 border border-emerald-100 dark:border-emerald-800 mb-3">
          <MaterialIcons name="search" size={20} color="#10B981" />
          <TextInput
            className="flex-1 ml-2 text-[#064E3B] dark:text-white font-medium h-8"
            placeholder="Buscar ubicación..."
            placeholderTextColor="#9CA3AF"
            value={search}
            onChangeText={setSearch}
          />
        </View>

        <TouchableOpacity className="flex-row items-center justify-center bg-white dark:bg-[#062C25] border border-emerald-200 dark:border-emerald-800 rounded-xl py-2 shadow-sm">
          <MaterialIcons name="tune" size={20} color="#064E3B" />
          <Text className="ml-2 font-bold text-[#064E3B] dark:text-emerald-100">Filtros</Text>
        </TouchableOpacity>
      </View>

      {/* Map View */}
      <View className="h-2/5 w-full relative">
        <MapView
          style={StyleSheet.absoluteFillObject}
          initialRegion={{
            latitude: 20.588,
            longitude: -100.389,
            latitudeDelta: 0.0922,
            longitudeDelta: 0.0421,
          }}
        >
          {mockPuntos.map(punto => (
            <Marker
              key={punto.id}
              coordinate={{ latitude: punto.latitud, longitude: punto.longitud }}
            >
              <View className="w-10 h-10 bg-[#064E3B] rounded-full items-center justify-center border-4 border-[#00E096] shadow-md">
                <MaterialIcons name="recycling" size={20} color="#00E096" />
              </View>
              <Callout>
                <View className="p-2 min-w-[150px]">
                  <Text className="font-bold text-base text-[#064E3B]">{punto.nombre}</Text>
                  <Text className="text-xs text-gray-500 mb-1">{punto.materiales}</Text>
                  <Text className="text-xs font-bold text-[#10B981]">⭐ {punto.promedio} ({punto.total_reviews} reseñas)</Text>
                </View>
              </Callout>
            </Marker>
          ))}
        </MapView>
      </View>

      {/* Points List */}
      <ScrollView className="flex-1 p-4 bg-[#F0FDF4] dark:bg-[#062C25]">
        {mockPuntos.map((punto) => (
          <TouchableOpacity 
            key={punto.id} 
            className="flex-row items-start p-4 bg-white dark:bg-[#022C22] rounded-2xl mb-3 shadow-sm border border-emerald-100 dark:border-emerald-800"
          >
            <View className="w-12 h-12 rounded-full border-2 border-[#10B981]/20 bg-emerald-100 dark:bg-emerald-800 items-center justify-center mr-3">
              <MaterialIcons name="recycling" size={24} color="#10B981" />
            </View>
            <View className="flex-1">
              <Text className="font-bold text-[#064E3B] dark:text-white text-base leading-tight">{punto.nombre}</Text>
              <Text className="text-xs text-gray-500 dark:text-gray-400 mt-1">{punto.materiales}</Text>
              <Text className="text-xs font-bold text-[#10B981] mt-2">⭐ {punto.promedio} ({punto.total_reviews} reseñas)</Text>
            </View>
          </TouchableOpacity>
        ))}
        
        <View className="items-center mt-4 mb-8">
          <TouchableOpacity className="flex-row items-center">
            <Text className="text-[#10B981] font-bold text-lg mr-2 border-b border-[#10B981]">Recomendaciones</Text>
            <MaterialIcons name="arrow-forward" size={24} color="#10B981" />
          </TouchableOpacity>
        </View>
      </ScrollView>
    </SafeAreaView>
  );
}
