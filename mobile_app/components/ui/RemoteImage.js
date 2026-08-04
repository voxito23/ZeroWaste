import React, { useEffect, useMemo, useState } from 'react';
import { Image, Text, TouchableOpacity, View } from 'react-native';
import { ImageOff } from 'lucide-react-native';
import Skeleton from './Skeleton';

const isDevelopment = typeof __DEV__ !== 'undefined' && __DEV__;

const safeLogTarget = (uri) => {
  if (!uri) return 'sin-url';
  try {
    const parsed = new URL(uri);
    const segments = parsed.pathname.split('/').filter(Boolean);
    const filename = segments.at(-1) || '';
    const extension = filename.includes('.') ? `.${filename.split('.').pop().slice(0, 8)}` : '';
    const mediaPrefix = segments[0] === 'media' && segments[1] ? `/media/${segments[1]}` : '';
    return `${parsed.origin}${mediaPrefix}/[archivo]${extension}`;
  } catch {
    return 'url-invalida';
  }
};

const inferHttpStatus = (errorEvent) => {
  const message = errorEvent?.nativeEvent?.error;
  if (typeof message !== 'string') return null;
  const match = message.match(/(?:http(?:\s+status)?|status(?:\s+code)?|response(?:\s+code)?)\D{0,12}([1-5]\d{2})/i);
  return match?.[1] || null;
};

const logImageEvent = (event, uri, httpStatus = null) => {
  if (!isDevelopment) return;
  const status = httpStatus ? ` (HTTP ${httpStatus})` : '';
  console.info(`[media] ${event}${status}: ${safeLogTarget(uri)}`);
};

export default function RemoteImage({
  uri,
  className,
  style,
  imageClassName = 'h-full w-full',
  resizeMode = 'cover',
  aspectRatio = 16 / 9,
  fallbackSource,
  backgroundClassName = 'bg-gray-100',
  loadingClassName = 'bg-slate-200',
  accessibilityLabel = 'Imagen remota',
  onLoadStart,
  onLoad,
  onError,
}) {
  const [failed, setFailed] = useState(false);
  const [loading, setLoading] = useState(Boolean(uri));
  const [attempt, setAttempt] = useState(0);
  const wrapperStyle = useMemo(() => [aspectRatio ? { aspectRatio } : null, style], [aspectRatio, style]);

  useEffect(() => {
    setFailed(false);
    setLoading(Boolean(uri));
    setAttempt(0);
  }, [uri]);

  const retry = () => {
    setFailed(false);
    setLoading(true);
    setAttempt((value) => value + 1);
  };

  if (!uri || failed) {
    return (
      <View className={`${className || ''} ${backgroundClassName} items-center justify-center overflow-hidden`} style={wrapperStyle}>
        {fallbackSource ? (
          <Image source={fallbackSource} className={imageClassName} resizeMode={resizeMode} accessibilityLabel={`${accessibilityLabel} alternativa`} />
        ) : (
          <>
            <ImageOff color="#9CA3AF" size={30} />
            <Text className="mt-2 text-xs font-bold text-gray-400">Imagen no disponible</Text>
          </>
        )}
        {uri ? (
          <TouchableOpacity onPress={retry} className="absolute bottom-2 rounded-full bg-white/90 px-3 py-1.5" accessibilityRole="button" accessibilityLabel="Reintentar carga de imagen">
            <Text className="text-xs font-black text-emerald-700">Reintentar</Text>
          </TouchableOpacity>
        ) : null}
      </View>
    );
  }

  return (
    <View className={`${className || ''} ${backgroundClassName} overflow-hidden`} style={wrapperStyle}>
      <Image
        key={`${uri}-${attempt}`}
        source={{ uri }}
        className={imageClassName}
        resizeMode={resizeMode}
        accessibilityLabel={accessibilityLabel}
        onLoadStart={(event) => {
          setLoading(true);
          logImageEvent('inicio', uri);
          onLoadStart?.(event);
        }}
        onLoad={(event) => {
          setLoading(false);
          logImageEvent('cargada', uri);
          onLoad?.(event);
        }}
        onError={(event) => {
          setLoading(false);
          setFailed(true);
          logImageEvent('error', uri, inferHttpStatus(event));
          onError?.(event);
        }}
        onLoadEnd={() => setLoading(false)}
      />
      {loading ? (
        <Skeleton className={`absolute inset-0 ${loadingClassName}`} />
      ) : null}
    </View>
  );
}
