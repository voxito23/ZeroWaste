import React from 'react';
import { Animated, TouchableOpacity, StyleSheet } from 'react-native';
import { Home, MessageSquare, Map as MapIcon, User, Camera } from 'lucide-react-native';
import { useScrollContext } from '../../context/ScrollContext';
import { useSafeAreaInsets } from 'react-native-safe-area-context';

export default function FloatingTabBar({ state, descriptors, navigation }) {
  const { tabY } = useScrollContext();
  const insets = useSafeAreaInsets();
  const bottomInset = Math.max(insets.bottom + 14, 24);

  return (
    <Animated.View 
      className="absolute left-6 right-6 flex-row bg-surface rounded-full py-4 px-6 justify-between items-center shadow-lg shadow-black/10 elevation-5"
      style={{ bottom: bottomInset, transform: [{ translateY: tabY }] }}
    >
      {state.routes.map((route, index) => {
        const { options } = descriptors[route.key];
        if (options.href === null) return null;

        const isFocused = state.index === index;

        const onPress = () => {
          const event = navigation.emit({
            type: 'tabPress',
            target: route.key,
            canPreventDefault: true,
          });

          if (!isFocused && !event.defaultPrevented) {
            navigation.navigate(route.name, route.params);
          }
        };

        const onLongPress = () => {
          navigation.emit({
            type: 'tabLongPress',
            target: route.key,
          });
        };

        const routeName = route.name.toLowerCase();
        let IconComponent = Home;
        if (routeName === 'forum') IconComponent = MessageSquare;
        if (routeName === 'scanner') IconComponent = Camera;
        if (routeName === 'map') IconComponent = MapIcon;
        if (routeName === 'profile') IconComponent = User;

        const color = isFocused ? '#064E3B' : '#9CA3AF'; // primary or gray-400

        // Central elevated button
        if (routeName === 'scanner') {
          return (
            <TouchableOpacity
              key={index}
              onPress={onPress}
              onLongPress={onLongPress}
              activeOpacity={0.8}
              className="bg-primary h-16 w-16 rounded-full flex items-center justify-center -mt-8 border-4 border-background shadow-xl shadow-primary/30 elevation-8"
            >
              <IconComponent color="#ffffff" size={28} />
            </TouchableOpacity>
          );
        }

        return (
          <TouchableOpacity
            key={index}
            accessibilityRole="button"
            accessibilityState={isFocused ? { selected: true } : {}}
            accessibilityLabel={options.tabBarAccessibilityLabel}
            testID={options.tabBarTestID}
            onPress={onPress}
            onLongPress={onLongPress}
            className="flex-1 items-center justify-center"
          >
            <IconComponent color={color} size={24} />
          </TouchableOpacity>
        );
      })}
    </Animated.View>
  );
}