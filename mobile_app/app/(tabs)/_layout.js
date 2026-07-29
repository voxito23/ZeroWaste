import React from 'react';
import { Tabs } from "expo-router";
import { Home, MessageSquare, Map as MapIcon, User, Camera } from 'lucide-react-native';
import FloatingTabBar from '../../components/nav/FloatingTabBar';
import { ScrollProvider } from '../../context/ScrollContext';

export default function TabsLayout() {
  return (
    <ScrollProvider>
      <Tabs
        tabBar={(props) => <FloatingTabBar {...props} />}
        screenOptions={{ headerShown: false }}
      >
        <Tabs.Screen
          name="index"
          options={{ title: "Inicio", href: null }}
        />
        <Tabs.Screen
          name="home"
          options={{
            title: "Inicio",
            tabBarIcon: ({ color }) => <Home color={color} size={24} />,
          }}
        />
        <Tabs.Screen
          name="forum"
          options={{
            title: "Comunidad",
            tabBarIcon: ({ color }) => <MessageSquare color={color} size={24} />,
          }}
        />
        <Tabs.Screen
          name="scanner"
          options={{
            title: "Escanear",
            tabBarIcon: ({ color }) => <Camera color={color} size={24} />,
          }}
        />
        <Tabs.Screen
          name="map"
          options={{
            title: "Mapa",
            tabBarIcon: ({ color }) => <MapIcon color={color} size={24} />,
          }}
        />
        <Tabs.Screen
          name="profile"
          options={{
            title: "Perfil",
            tabBarIcon: ({ color }) => <User color={color} size={24} />,
          }}
        />
      </Tabs>
    </ScrollProvider>
  );
}
