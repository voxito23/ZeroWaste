import React from 'react';
import { TouchableOpacity, Text, ActivityIndicator } from 'react-native';


export default function CustomButton({ title, variant = 'primary', loading, className, ...props }) {
  const baseStyle = "py-4 rounded-xl items-center justify-center flex-row";
  const variants = {
    primary: "bg-primary shadow-lg shadow-primary/30",
    secondary: "bg-secondary shadow-lg shadow-secondary/30",
    outline: "bg-transparent border-2 border-primary"
  };
  
  const textStyles = {
    primary: "text-white font-bold text-lg",
    secondary: "text-primary font-bold text-lg",
    outline: "text-primary font-bold text-lg"
  };

  return (
    <TouchableOpacity 
      className={`${baseStyle} ${variants[variant]} ${className || ''}`}
      disabled={loading || props.disabled}
      activeOpacity={0.8}
      {...props}
    >
      {loading ? (
        <ActivityIndicator color={variant === 'outline' ? '#064E3B' : '#fff'} />
      ) : (
        <Text className={textStyles[variant]}>{title}</Text>
      )}
    </TouchableOpacity>
  );
}