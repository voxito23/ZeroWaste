import React from 'react';
import { TextInput, View, Text, TouchableOpacity } from 'react-native';


export default function CustomInput({ 
  label, 
  error, 
  className, 
  leftIcon, 
  rightIcon, 
  onRightIconPress, 
  ...props 
}) {
  return (
    <View className="mb-4">
      {label && <Text className="text-primary mb-1.5 text-sm font-bold">{label}</Text>}
      <View className={`flex-row items-center bg-[#FAFAFA] border ${error ? 'border-red-500' : 'border-gray-200'} rounded-xl px-4 py-1 focus:border-primary ${className || ''}`}>
        {leftIcon && <View className="mr-3">{leftIcon}</View>}
        <TextInput
          className="flex-1 py-3.5 text-base text-text"
          placeholderTextColor="#9CA3AF"
          {...props}
        />
        {rightIcon && (
          <TouchableOpacity onPress={onRightIconPress} className="ml-3" disabled={!onRightIconPress}>
            {rightIcon}
          </TouchableOpacity>
        )}
      </View>
      {error && <Text className="text-red-500 text-xs mt-1">{error}</Text>}
    </View>
  );
}