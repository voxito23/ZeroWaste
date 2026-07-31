import React from 'react';
import { Alert, TouchableOpacity, View } from 'react-native';
import { Home, MessageSquare, Map as MapIcon, User, Camera } from 'lucide-react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { useAuth } from '../../store/useAuth';

export const TAB_BAR_VISUAL_HEIGHT = 68;

export default function FloatingTabBar({ state, descriptors, navigation }) {
  const insets = useSafeAreaInsets();
  const { user } = useAuth();

  return (
    <View
      className="bg-white border-t border-gray-200"
      style={{ height: TAB_BAR_VISUAL_HEIGHT + insets.bottom, paddingBottom: insets.bottom }}
    >
      <View className="flex-1 flex-row items-center justify-around px-2">
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
          const canScan = user?.rol === 'recolector' || user?.rol === 'admin' || user?.is_admin;
          return (
            <TouchableOpacity
              key={route.key}
              onPress={canScan ? onPress : () => Alert.alert('Acceso restringido', 'El escáner de validación está disponible para recolectores autorizados.')}
              onLongPress={onLongPress}
              activeOpacity={0.8}
              accessibilityRole="button"
              accessibilityState={isFocused ? { selected: true } : {}}
              accessibilityLabel="Abrir escáner QR"
              className={`${canScan ? 'bg-primary' : 'bg-gray-400'} h-16 w-16 rounded-full items-center justify-center -mt-7 border-4 border-white shadow-xl elevation-8`}
            >
              <IconComponent color="#ffffff" size={28} />
            </TouchableOpacity>
          );
        }

        return (
          <TouchableOpacity
            key={route.key}
            accessibilityRole="button"
            accessibilityState={isFocused ? { selected: true } : {}}
            accessibilityLabel={options.tabBarAccessibilityLabel}
            testID={options.tabBarTestID}
            onPress={onPress}
            onLongPress={onLongPress}
            className="flex-1 h-full items-center justify-center"
          >
            {isFocused ? <View className="absolute top-0 h-1 w-8 rounded-b-full bg-emerald-700" /> : null}
            <IconComponent color={color} size={24} />
          </TouchableOpacity>
        );
        })}
      </View>
    </View>
  );
}
