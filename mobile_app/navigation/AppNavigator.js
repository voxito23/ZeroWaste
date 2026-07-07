import React, { useEffect } from 'react';
import { NavigationContainer } from '@react-navigation/native';
import { createNativeStackNavigator } from '@react-navigation/native-stack';
import MainTabNavigator from './MainTabNavigator';
import AuthNavigator from './AuthNavigator';
import { useAuth } from '../store/useAuth';
import { ActivityIndicator, View } from 'react-native';

import MisRecoleccionesScreen from '../screens/MisRecoleccionesScreen';
import PostScreen from '../screens/PostScreen';
import CreatePostScreen from '../screens/CreatePostScreen';


const Stack = createNativeStackNavigator();

export default function AppNavigator() {
  const { user, token, isLoading, restoreToken } = useAuth();

  useEffect(() => {
    restoreToken();
  }, []);

  if (isLoading) {
    return (
      <View className="flex-1 justify-center items-center bg-background">
        <ActivityIndicator size="large" color="#064E3B" />
      </View>
    );
  }

  return (
    <NavigationContainer>
      <Stack.Navigator screenOptions={{ headerShown: false }}>
        {token ? (
          <>
            <Stack.Screen name="Main" component={MainTabNavigator} />
            <Stack.Screen name="MisRecolecciones" component={MisRecoleccionesScreen} />
            <Stack.Screen name="PostDetail" component={PostScreen} />
            <Stack.Screen name="CreatePost" component={CreatePostScreen} />
          </>
        ) : (
          <Stack.Screen name="Auth" component={AuthNavigator} />
        )}
      </Stack.Navigator>
    </NavigationContainer>
  );
}
