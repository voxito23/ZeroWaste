import React, { useEffect, useRef, useState } from 'react';
import { createNavigationContainerRef, NavigationContainer } from '@react-navigation/native';
import { createNativeStackNavigator } from '@react-navigation/native-stack';
import MainTabNavigator from './MainTabNavigator';
import AuthNavigator from './AuthNavigator';
import { useAuth } from '../store/useAuth';
import { AccessibilityInfo, ActivityIndicator, Linking, View } from 'react-native';

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
import RouteNavigationScreen from '../screens/RouteNavigationScreen';
import ChangePasswordScreen from '../screens/ChangePasswordScreen';
import NewsDetailScreen from '../screens/NewsDetailScreen';
import ContentUnavailableScreen from '../screens/ContentUnavailableScreen';
import HelpSupportScreen from '../screens/HelpSupportScreen';
import InfoDocumentScreen from '../screens/InfoDocumentScreen';
import { deepLinkTarget } from './linking';


const Stack = createNativeStackNavigator();
const navigationRef = createNavigationContainerRef();

export default function AppNavigator() {
  const { user, token, isLoading, restoreToken } = useAuth();
  const [reduceMotion, setReduceMotion] = useState(false);
  const [pendingLink, setPendingLink] = useState(null);
  const [navigationReady, setNavigationReady] = useState(false);
  const handledLinkRef = useRef(new Set());

  useEffect(() => {
    restoreToken();
  }, []);

  useEffect(() => {
    let active = true;
    const receiveUrl = ({ url } = {}) => {
      if (!url || handledLinkRef.current.has(url)) return;
      const target = deepLinkTarget(url);
      if (!target) return;
      handledLinkRef.current.add(url);
      setPendingLink(target);
    };
    const subscription = Linking.addEventListener('url', receiveUrl);
    Linking.getInitialURL().then((url) => { if (active && url) receiveUrl({ url }); });
    return () => { active = false; subscription.remove(); };
  }, []);

  useEffect(() => {
    if (!token || isLoading || !navigationReady || !pendingLink || !navigationRef.isReady()) return;
    navigationRef.navigate(pendingLink.name, pendingLink.params);
    setPendingLink(null);
  }, [isLoading, navigationReady, pendingLink, token]);

  useEffect(() => {
    AccessibilityInfo.isReduceMotionEnabled().then(setReduceMotion);
    const subscription = AccessibilityInfo.addEventListener('reduceMotionChanged', setReduceMotion);
    return () => subscription.remove();
  }, []);

  if (isLoading) {
    return (
      <View className="flex-1 justify-center items-center bg-background">
        <ActivityIndicator size="large" color="#064E3B" />
      </View>
    );
  }

  return (
    <NavigationContainer ref={navigationRef} onReady={() => setNavigationReady(true)}>
      <Stack.Navigator screenOptions={{ headerShown: false, animation: reduceMotion ? 'none' : 'slide_from_right', animationDuration: reduceMotion ? 0 : 220, contentStyle: { backgroundColor: '#ECFDF5' } }}>
        {token ? (
          <>
            <Stack.Screen name="Main" component={MainTabNavigator} />
            <Stack.Screen name="MisRecolecciones" component={MisRecoleccionesScreen} />
            <Stack.Screen name="PostDetail" component={PostScreen} options={{ animation: reduceMotion ? 'none' : 'slide_from_right' }} />
            <Stack.Screen name="CreatePost" component={CreatePostScreen} options={{ animation: reduceMotion ? 'none' : 'slide_from_bottom', presentation: 'modal' }} />
            <Stack.Screen name="ImpactStats" component={ImpactStatsScreen} />
            <Stack.Screen name="RewardsStore" component={RewardsStoreScreen} />
            <Stack.Screen name="RewardDetail" component={RewardDetailScreen} />
            <Stack.Screen name="MyRedemptions" component={MyRedemptionsScreen} />
            <Stack.Screen name="PointsHistory" component={PointsHistoryScreen} />
            <Stack.Screen name="Notifications" component={NotificationsScreen} />
            <Stack.Screen name="Search" component={SearchScreen} />
            <Stack.Screen name="Settings" component={SettingsScreen} />
            <Stack.Screen name="EditProfile" component={EditProfileScreen} />
            <Stack.Screen name="ChangePassword" component={ChangePasswordScreen} />
            <Stack.Screen name="ArticleDetail" component={ArticleDetailScreen} />
            <Stack.Screen name="NewsDetail" component={NewsDetailScreen} />
            <Stack.Screen name="LocationDetail" component={LocationDetailScreen} />
            <Stack.Screen name="CreateCollection" component={CreateCollectionScreen} />
            <Stack.Screen name="PointDetail" component={PointDetailScreen} />
            <Stack.Screen name="RouteNavigation" component={RouteNavigationScreen} options={{ animation: reduceMotion ? 'none' : 'fade', animationDuration: reduceMotion ? 0 : 180 }} />
            <Stack.Screen name="ContentUnavailable" component={ContentUnavailableScreen} />
            <Stack.Screen name="HelpSupport" component={HelpSupportScreen} />
            <Stack.Screen name="InfoDocument" component={InfoDocumentScreen} />
          </>
        ) : (
          <Stack.Screen name="Auth" component={AuthNavigator} />
        )}
      </Stack.Navigator>
    </NavigationContainer>
  );
}
