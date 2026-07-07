import React from 'react';
import { View } from 'react-native';

export default function FloatingCard({ children, className, ...props }) {
  return (
    <View 
      className={`bg-surface rounded-2xl shadow-md shadow-black/5 elevation-2 p-5 ${className || ''}`}
      {...props}
    >
      {children}
    </View>
  );
}