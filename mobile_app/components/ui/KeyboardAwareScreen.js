import React from 'react';
import {
  KeyboardAvoidingView,
  Platform,
  ScrollView,
} from 'react-native';


export default function KeyboardAwareScreen({
  children,
  contentContainerStyle,
  footer,
  scrollRef,
  ...scrollProps
}) {
  return (
    <KeyboardAvoidingView
      behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
      style={{ flex: 1 }}
    >
      <ScrollView
        ref={scrollRef}
        style={{ flex: 1 }}
        contentContainerStyle={[{ flexGrow: 1, paddingBottom: 32 }, contentContainerStyle]}
        keyboardShouldPersistTaps="handled"
        keyboardDismissMode={Platform.OS === 'ios' ? 'interactive' : 'on-drag'}
        automaticallyAdjustKeyboardInsets={Platform.OS === 'ios'}
        contentInsetAdjustmentBehavior="automatic"
        showsVerticalScrollIndicator={false}
        {...scrollProps}
      >
        {children}
      </ScrollView>
      {footer}
    </KeyboardAvoidingView>
  );
}
