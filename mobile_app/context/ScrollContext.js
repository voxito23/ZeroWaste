import React, { createContext, useContext, useRef } from 'react';
import { Animated } from 'react-native';

const ScrollContext = createContext();

export const ScrollProvider = ({ children }) => {
  const tabY = useRef(new Animated.Value(0)).current;
  const lastScrollY = useRef(0);
  const isVisible = useRef(true);

  const handleScroll = (event) => {
    const currentY = event.nativeEvent.contentOffset.y;
    const diff = currentY - lastScrollY.current;
    
    // Si estamos rebotando arriba del todo (iOS bounce)
    if (currentY <= 0) {
      if (!isVisible.current) showTabBar();
      lastScrollY.current = currentY;
      return;
    }

    if (diff > 5 && isVisible.current) {
      // Hacia abajo: ocultar
      hideTabBar();
    } else if (diff < -5 && !isVisible.current) {
      // Hacia arriba: mostrar
      showTabBar();
    }
    
    lastScrollY.current = currentY;
  };

  const hideTabBar = () => {
    isVisible.current = false;
    Animated.spring(tabY, {
      toValue: 120, // Lo movemos hacia abajo fuera de la pantalla
      useNativeDriver: true,
      speed: 20,
      bounciness: 2
    }).start();
  };

  const showTabBar = () => {
    isVisible.current = true;
    Animated.spring(tabY, {
      toValue: 0,
      useNativeDriver: true,
      speed: 20,
      bounciness: 4
    }).start();
  };

  return (
    <ScrollContext.Provider value={{ tabY, handleScroll }}>
      {children}
    </ScrollContext.Provider>
  );
};

export const useScrollContext = () => useContext(ScrollContext);
