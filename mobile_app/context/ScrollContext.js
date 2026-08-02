import React, { createContext, useCallback, useContext, useEffect, useRef, useState } from 'react';
import { AccessibilityInfo, Animated, Easing, Keyboard } from 'react-native';

import { motion } from '../theme/tokens';


const ScrollContext = createContext(null);
const TOP_VISIBILITY_ZONE = 32;
const DIRECTION_THRESHOLD = 9;

export const ScrollProvider = ({ children }) => {
  const tabY = useRef(new Animated.Value(0)).current;
  const lastScrollY = useRef(0);
  const directionDelta = useRef(0);
  const isVisibleRef = useRef(true);
  const keyboardVisibleRef = useRef(false);
  const reduceMotionRef = useRef(false);
  const [isTabVisible, setIsTabVisible] = useState(true);
  const [reduceMotion, setReduceMotion] = useState(false);

  const setVisibility = useCallback((visible) => {
    if (visible && keyboardVisibleRef.current) return;
    if (isVisibleRef.current === visible) return;
    isVisibleRef.current = visible;
    setIsTabVisible(visible);
    Animated.timing(tabY, {
      toValue: visible ? 0 : 1,
      duration: reduceMotionRef.current ? 0 : motion.navigation,
      easing: Easing.out(Easing.cubic),
      useNativeDriver: true,
    }).start();
  }, [tabY]);

  const showTabBar = useCallback(() => setVisibility(true), [setVisibility]);
  const hideTabBar = useCallback(() => setVisibility(false), [setVisibility]);

  const handleScroll = useCallback((event) => {
    const currentY = Math.max(0, Number(event?.nativeEvent?.contentOffset?.y) || 0);
    const delta = currentY - lastScrollY.current;

    if (currentY <= TOP_VISIBILITY_ZONE) {
      directionDelta.current = 0;
      showTabBar();
    } else {
      if (Math.sign(delta) !== Math.sign(directionDelta.current)) directionDelta.current = 0;
      directionDelta.current += delta;
    }
    if (currentY > TOP_VISIBILITY_ZONE && directionDelta.current >= DIRECTION_THRESHOLD) {
      directionDelta.current = 0;
      hideTabBar();
    } else if (currentY > TOP_VISIBILITY_ZONE && directionDelta.current <= -DIRECTION_THRESHOLD) {
      directionDelta.current = 0;
      showTabBar();
    }
    lastScrollY.current = currentY;
  }, [hideTabBar, showTabBar]);

  useEffect(() => {
    AccessibilityInfo.isReduceMotionEnabled().then((enabled) => {
      reduceMotionRef.current = enabled;
      setReduceMotion(enabled);
    });
    const reduceSubscription = AccessibilityInfo.addEventListener('reduceMotionChanged', (enabled) => {
      reduceMotionRef.current = enabled;
      setReduceMotion(enabled);
    });
    const keyboardShow = Keyboard.addListener('keyboardDidShow', () => {
      keyboardVisibleRef.current = true;
      setVisibility(false);
    });
    const keyboardHide = Keyboard.addListener('keyboardDidHide', () => {
      keyboardVisibleRef.current = false;
      setVisibility(true);
    });
    return () => {
      reduceSubscription.remove();
      keyboardShow.remove();
      keyboardHide.remove();
    };
  }, [setVisibility]);

  return (
    <ScrollContext.Provider value={{
      tabY,
      handleScroll,
      hideTabBar,
      showTabBar,
      isTabVisible,
      reduceMotion,
    }}>
      {children}
    </ScrollContext.Provider>
  );
};

export const useScrollContext = () => {
  const context = useContext(ScrollContext);
  if (!context) throw new Error('useScrollContext debe usarse dentro de ScrollProvider.');
  return context;
};
