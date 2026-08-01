import React, { useEffect, useMemo, useRef, useState } from 'react';
import { ActivityIndicator, Animated, Text, View } from 'react-native';

import { normalizeMediaUrl } from '../../utils/media';


const initialFor = (name) => {
  const value = typeof name === 'string' ? name.trim() : '';
  return value ? Array.from(value)[0].toLocaleUpperCase('es-MX') : '?';
};

export default function UserAvatar({
  uri,
  name,
  size = 40,
  style,
  accessibilityLabel,
}) {
  const normalizedUri = useMemo(() => normalizeMediaUrl(uri, 'perfiles'), [uri]);
  const [loading, setLoading] = useState(Boolean(normalizedUri));
  const [failed, setFailed] = useState(false);
  const opacity = useRef(new Animated.Value(0)).current;

  useEffect(() => {
    setLoading(Boolean(normalizedUri));
    setFailed(false);
    opacity.setValue(0);
  }, [normalizedUri, opacity]);

  const showFallback = !normalizedUri || failed;
  const frame = {
    width: size,
    height: size,
    borderRadius: size / 2,
    overflow: 'hidden',
  };

  return (
    <View
      style={[frame, { backgroundColor: '#D1FAE5' }, style]}
      accessibilityRole="image"
      accessibilityLabel={accessibilityLabel || `Avatar de ${name || 'usuario'}`}
    >
      {showFallback ? (
        <View className="h-full w-full items-center justify-center bg-emerald-100">
          <Text style={{ fontSize: Math.max(13, size * 0.4), color: '#065F46', fontWeight: '800' }}>
            {initialFor(name)}
          </Text>
        </View>
      ) : (
        <>
          <Animated.Image
            source={{ uri: normalizedUri }}
            resizeMode="cover"
            style={[frame, { opacity }]}
            onLoadStart={() => setLoading(true)}
            onLoad={() => {
              setLoading(false);
              Animated.timing(opacity, { toValue: 1, duration: 180, useNativeDriver: true }).start();
            }}
            onError={() => {
              setLoading(false);
              setFailed(true);
            }}
          />
          {loading ? (
            <View className="absolute inset-0 items-center justify-center bg-emerald-50">
              <ActivityIndicator size="small" color="#059669" />
            </View>
          ) : null}
        </>
      )}
    </View>
  );
}
