import React, { useEffect, useRef, useState } from 'react';
import { AccessibilityInfo, Animated, Pressable, Text } from 'react-native';
import { Heart } from 'lucide-react-native';


export default function LikeButton({ liked, count = 0, pending = false, onPress, size = 20 }) {
  const scale = useRef(new Animated.Value(1)).current;
  const [reduceMotion, setReduceMotion] = useState(false);

  useEffect(() => {
    AccessibilityInfo.isReduceMotionEnabled().then(setReduceMotion).catch(() => {});
    const subscription = AccessibilityInfo.addEventListener('reduceMotionChanged', setReduceMotion);
    return () => subscription.remove();
  }, []);

  const handlePress = () => {
    if (pending) return;
    if (!reduceMotion) {
      Animated.sequence([
        Animated.timing(scale, { toValue: 0.9, duration: 70, useNativeDriver: true }),
        Animated.timing(scale, { toValue: 1.12, duration: 90, useNativeDriver: true }),
        Animated.timing(scale, { toValue: 1, duration: 70, useNativeDriver: true }),
      ]).start();
    }
    onPress?.();
  };

  return (
    <Pressable
      onPress={handlePress}
      disabled={pending}
      accessibilityRole="button"
      accessibilityState={{ selected: liked, busy: pending, disabled: pending }}
      accessibilityLabel={liked ? 'Quitar Me gusta' : 'Dar Me gusta'}
      className="flex-row items-center gap-2 rounded-full px-2 py-1.5"
      style={({ pressed }) => ({ opacity: pending ? 0.55 : pressed ? 0.75 : 1 })}
    >
      <Animated.View style={{ transform: [{ scale }] }}>
        <Heart color={liked ? '#E11D48' : '#64748B'} fill={liked ? '#E11D48' : 'transparent'} size={size} />
      </Animated.View>
      <Text className={`font-bold ${liked ? 'text-rose-600' : 'text-slate-600'}`}>{Math.max(0, Number(count) || 0)}</Text>
    </Pressable>
  );
}
