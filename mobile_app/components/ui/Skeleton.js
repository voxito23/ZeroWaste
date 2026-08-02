import React, { useEffect, useRef } from 'react';
import { AccessibilityInfo, Animated, View } from 'react-native';

export default function Skeleton({ className = '', style }) {
  const opacity = useRef(new Animated.Value(0.42)).current;
  useEffect(() => {
    let animation;
    AccessibilityInfo.isReduceMotionEnabled().then((reduced) => {
      if (reduced) { opacity.setValue(0.58); return; }
      animation = Animated.loop(Animated.sequence([
        Animated.timing(opacity, { toValue: 0.78, duration: 650, useNativeDriver: true }),
        Animated.timing(opacity, { toValue: 0.42, duration: 650, useNativeDriver: true }),
      ]));
      animation.start();
    });
    return () => animation?.stop();
  }, [opacity]);
  return <Animated.View accessibilityElementsHidden importantForAccessibility="no-hide-descendants" className={`bg-slate-200 ${className}`} style={[style, { opacity }]} />;
}

export function PostCardSkeleton() {
  return <View className="mb-5 overflow-hidden rounded-[28px] border border-slate-100 bg-white"><Skeleton style={{ aspectRatio: 16 / 9 }} /><View className="p-5"><View className="flex-row items-center"><Skeleton className="h-10 w-10 rounded-full" /><View className="ml-3 flex-1"><Skeleton className="h-4 w-32 rounded-full" /><Skeleton className="mt-2 h-3 w-20 rounded-full" /></View></View><Skeleton className="mt-5 h-6 w-5/6 rounded-full" /><Skeleton className="mt-3 h-4 w-full rounded-full" /><Skeleton className="mt-2 h-4 w-3/4 rounded-full" /><Skeleton className="mt-5 h-11 w-full rounded-2xl" /></View></View>;
}
