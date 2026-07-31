import React, { useEffect, useState } from 'react';
import { ActivityIndicator, Image, Text, TouchableOpacity, View } from 'react-native';
import { ImageOff } from 'lucide-react-native';

export default function RemoteImage({ uri, className, resizeMode = 'cover' }) {
  const [failed, setFailed] = useState(false);
  const [loading, setLoading] = useState(Boolean(uri));
  const [attempt, setAttempt] = useState(0);
  useEffect(() => { setFailed(false); setLoading(Boolean(uri)); }, [uri]);
  if (!uri || failed) return <View className={`${className || ''} items-center justify-center bg-gray-100`}><ImageOff color="#9CA3AF" size={30}/><Text className="mt-2 text-xs font-bold text-gray-400">Imagen no disponible</Text>{uri?<TouchableOpacity onPress={()=>{setFailed(false);setLoading(true);setAttempt(value=>value+1);}}><Text className="mt-2 text-xs font-black text-emerald-700">Reintentar</Text></TouchableOpacity>:null}</View>;
  return <View className={`${className || ''} overflow-hidden bg-gray-100`}><Image key={`${uri}-${attempt}`} source={{uri}} className="h-full w-full" resizeMode={resizeMode} onLoadStart={()=>setLoading(true)} onLoadEnd={()=>setLoading(false)} onError={()=>{setLoading(false);setFailed(true);}}/>{loading?<View className="absolute inset-0 items-center justify-center bg-gray-100"><ActivityIndicator color="#047857"/></View>:null}</View>;
}
