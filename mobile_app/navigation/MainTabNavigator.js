import React from 'react';
import { createBottomTabNavigator } from '@react-navigation/bottom-tabs';
import FloatingTabBar from '../components/nav/FloatingTabBar';
import { ScrollProvider } from '../context/ScrollContext';

import MapScreen from '../screens/MapScreen';
import HomeScreen from '../screens/HomeScreen';
import ScannerScreen from '../screens/ScannerScreen';
import ForumScreen from '../screens/ForumScreen';
import ProfileScreen from '../screens/ProfileScreen';


const Tab = createBottomTabNavigator();

export default function MainTabNavigator() {
  return (
    <ScrollProvider>
      <Tab.Navigator
        tabBar={(props) => (
          props.state.routes[props.state.index]?.name === 'Scanner'
            ? null
            : <FloatingTabBar {...props} />
        )}
        screenOptions={{ headerShown: false, lazy: true }}
      >
        <Tab.Screen name="Home" component={HomeScreen} />
        <Tab.Screen name="Forum" component={ForumScreen} />
        <Tab.Screen name="Scanner" component={ScannerScreen} />
        <Tab.Screen name="Map" component={MapScreen} />
        <Tab.Screen name="Profile" component={ProfileScreen} />
      </Tab.Navigator>
    </ScrollProvider>
  );
}
