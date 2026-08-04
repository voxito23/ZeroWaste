import React, { useEffect, useRef } from 'react';
import { Animated, Platform, Pressable, View } from 'react-native';
import { Camera, Home, Map as MapIcon, MessageSquare, User } from 'lucide-react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';

import { useScrollContext } from '../../context/ScrollContext';
import { colors, motion } from '../../theme/tokens';


export const TAB_BAR_VISUAL_HEIGHT = 68;
const TAB_BAR_MARGIN = 12;
const ANDROID_GESTURE_FALLBACK = 20;

export const getTabBarBottomSpacing = (bottomInset, platform = Platform.OS) => {
  const reportedInset = Number.isFinite(bottomInset) ? Math.max(bottomInset, 0) : 0;
  const systemInset = platform === 'android'
    ? Math.max(reportedInset, ANDROID_GESTURE_FALLBACK)
    : Math.max(reportedInset, 8);
  return systemInset + TAB_BAR_MARGIN;
};

function AnimatedTabButton({ children, disabled, onLongPress, onPress, accessibilityLabel, selected, reduceMotion, style }) {
  const scale = useRef(new Animated.Value(1)).current;
  const animate = (toValue) => Animated.timing(scale, {
    toValue,
    duration: reduceMotion ? 0 : motion.press,
    useNativeDriver: true,
  }).start();

  return (
    <Pressable
      accessibilityLabel={accessibilityLabel}
      accessibilityRole="button"
      accessibilityState={{ disabled: Boolean(disabled), selected }}
      disabled={disabled}
      onLongPress={onLongPress}
      onPress={onPress}
      onPressIn={() => animate(0.9)}
      onPressOut={() => animate(1)}
      style={style}
    >
      <Animated.View style={{ transform: [{ scale }] }}>{children}</Animated.View>
    </Pressable>
  );
}

export default function FloatingTabBar({ state, descriptors, navigation }) {
  const insets = useSafeAreaInsets();
  const { tabY, showTabBar, isTabVisible, reduceMotion } = useScrollContext();
  const activeRoute = state.routes[state.index]?.name;
  const bottomSpacing = getTabBarBottomSpacing(insets.bottom);
  const totalHeight = TAB_BAR_VISUAL_HEIGHT + bottomSpacing;

  useEffect(() => {
    showTabBar();
  }, [activeRoute, showTabBar]);

  return (
    <Animated.View
      pointerEvents={isTabVisible ? 'box-none' : 'none'}
      style={{
        position: 'absolute',
        bottom: 0,
        left: 0,
        right: 0,
        height: totalHeight,
        paddingBottom: bottomSpacing,
        paddingHorizontal: 16,
        justifyContent: 'flex-end',
        opacity: tabY.interpolate({ inputRange: [0, 1], outputRange: [1, 0.15] }),
        transform: [{ translateY: tabY.interpolate({ inputRange: [0, 1], outputRange: [0, totalHeight + 12] }) }],
      }}
    >
      <View
        className="flex-row items-center rounded-full border border-slate-100 bg-white px-2"
        style={{
          height: TAB_BAR_VISUAL_HEIGHT,
          shadowColor: colors.forestDeep,
          shadowOffset: { width: 0, height: 8 },
          shadowOpacity: 0.14,
          shadowRadius: 18,
          elevation: 12,
        }}
      >
        {state.routes.map((route, index) => {
          const { options } = descriptors[route.key];
          if (options.href === null) return null;
          const isFocused = state.index === index;
          const routeName = route.name.toLowerCase();
          const icons = { home: Home, forum: MessageSquare, scanner: Camera, map: MapIcon, profile: User };
          const IconComponent = icons[routeName] || Home;
          const color = isFocused ? colors.green : '#94A3B8';

          const onPress = () => {
            const event = navigation.emit({ type: 'tabPress', target: route.key, canPreventDefault: true });
            if (!isFocused && !event.defaultPrevented) navigation.navigate(route.name, route.params);
          };
          const onLongPress = () => navigation.emit({ type: 'tabLongPress', target: route.key });

          if (routeName === 'scanner') {
            return (
              <AnimatedTabButton
                key={route.key}
                accessibilityLabel="Abrir escáner QR"
                selected={isFocused}
                reduceMotion={reduceMotion}
                onLongPress={onLongPress}
                onPress={onPress}
                style={{ flex: 1, height: TAB_BAR_VISUAL_HEIGHT, alignItems: 'center', justifyContent: 'center' }}
              >
                <View
                  className="h-16 w-16 items-center justify-center rounded-full border-4 border-white bg-emerald-600"
                  style={{ marginTop: -26, shadowColor: colors.forest, shadowOpacity: 0.24, shadowRadius: 10, elevation: 9 }}
                >
                  <IconComponent color={colors.white} size={28} strokeWidth={2.5} />
                </View>
              </AnimatedTabButton>
            );
          }

          return (
            <AnimatedTabButton
              key={route.key}
              accessibilityLabel={options.tabBarAccessibilityLabel || route.name}
              selected={isFocused}
              reduceMotion={reduceMotion}
              onLongPress={onLongPress}
              onPress={onPress}
              style={{ flex: 1, height: TAB_BAR_VISUAL_HEIGHT, alignItems: 'center', justifyContent: 'center' }}
            >
              <View className="h-12 w-12 items-center justify-center rounded-full">
                <IconComponent color={color} size={24} strokeWidth={isFocused ? 2.6 : 2.1} />
                {isFocused ? <View className="absolute bottom-0 h-1.5 w-1.5 rounded-full bg-emerald-600" /> : null}
              </View>
            </AnimatedTabButton>
          );
        })}
      </View>
    </Animated.View>
  );
}
