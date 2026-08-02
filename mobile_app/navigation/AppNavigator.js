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
import ImpactStatsScreen from '../screens/ImpactStatsScreen';
import RewardsStoreScreen from '../screens/RewardsStoreScreen';
import RewardDetailScreen from '../screens/RewardDetailScreen';
import MyRedemptionsScreen from '../screens/MyRedemptionsScreen';
import PointsHistoryScreen from '../screens/PointsHistoryScreen';
import NotificationsScreen from '../screens/NotificationsScreen';
import SearchScreen from '../screens/SearchScreen';
import SettingsScreen from '../screens/SettingsScreen';
import EditProfileScreen from '../screens/EditProfileScreen';
import ArticleDetailScreen from '../screens/ArticleDetailScreen';
import LocationDetailScreen from '../screens/LocationDetailScreen';
import CreateCollectionScreen from '../screens/CreateCollectionScreen';
import PointDetailScreen from '../screens/PointDetailScreen';


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
      <Stack.Navigator screenOptions={{ headerShown: false, animation: 'fade_from_bottom', animationDuration: 220 }}>
        {token ? (
          <>
            <Stack.Screen name="Main" component={MainTabNavigator} />
            <Stack.Screen name="MisRecolecciones" component={MisRecoleccionesScreen} />
            <Stack.Screen name="PostDetail" component={PostScreen} />
            <Stack.Screen name="CreatePost" component={CreatePostScreen} />
            <Stack.Screen name="ImpactStats" component={ImpactStatsScreen} />
            <Stack.Screen name="RewardsStore" component={RewardsStoreScreen} />
            <Stack.Screen name="RewardDetail" component={RewardDetailScreen} />
            <Stack.Screen name="MyRedemptions" component={MyRedemptionsScreen} />
            <Stack.Screen name="PointsHistory" component={PointsHistoryScreen} />
            <Stack.Screen name="Notifications" component={NotificationsScreen} />
            <Stack.Screen name="Search" component={SearchScreen} />
            <Stack.Screen name="Settings" component={SettingsScreen} />
            <Stack.Screen name="EditProfile" component={EditProfileScreen} />
            <Stack.Screen name="ArticleDetail" component={ArticleDetailScreen} />
            <Stack.Screen name="LocationDetail" component={LocationDetailScreen} />
            <Stack.Screen name="CreateCollection" component={CreateCollectionScreen} />
            <Stack.Screen name="PointDetail" component={PointDetailScreen} />
          </>
        ) : (
          <Stack.Screen name="Auth" component={AuthNavigator} />
        )}
      </Stack.Navigator>
    </NavigationContainer>
  );
}
