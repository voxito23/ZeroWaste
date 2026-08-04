import React, { useEffect, useMemo, useRef, useState } from 'react';
import { Animated, Text, View } from 'react-native';

import { normalizeMediaUrl } from '../../utils/media';
import Skeleton from './Skeleton';

const loadedAvatarUris = new Set();

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
  const alreadyLoaded = Boolean(normalizedUri && loadedAvatarUris.has(normalizedUri));
  const [loading, setLoading] = useState(Boolean(normalizedUri && !alreadyLoaded));
  const [failed, setFailed] = useState(false);
  const opacity = useRef(new Animated.Value(alreadyLoaded ? 1 : 0)).current;

  useEffect(() => {
    const cached = Boolean(normalizedUri && loadedAvatarUris.has(normalizedUri));
    setLoading(Boolean(normalizedUri && !cached));
    setFailed(false);
    opacity.setValue(cached ? 1 : 0);
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
            source={{ uri: normalizedUri, cache: 'force-cache' }}
            resizeMode="cover"
            style={[frame, { opacity }]}
            onLoadStart={() => {
              if (!loadedAvatarUris.has(normalizedUri)) setLoading(true);
            }}
            onLoad={() => {
              loadedAvatarUris.add(normalizedUri);
              setLoading(false);
              Animated.timing(opacity, { toValue: 1, duration: 120, useNativeDriver: true }).start();
            }}
            onError={() => {
              setLoading(false);
              setFailed(true);
            }}
          />
          {loading ? (
            <Skeleton className="absolute inset-0 rounded-full" />
          ) : null}
        </>
      )}
    </View>
  );
}
