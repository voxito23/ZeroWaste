import React, { useCallback, useEffect, useRef, useState } from 'react';
import {
  AccessibilityInfo,
  ActivityIndicator,
  Animated,
  Easing,
  ScrollView,
  Share,
  Text,
  TouchableOpacity,
  View,
} from 'react-native';
import { ArrowLeft, CalendarDays, Clock3, Share2 } from 'lucide-react-native';
import { useNavigation, useRoute } from '@react-navigation/native';
import { StatusBar } from 'expo-status-bar';
import { SafeAreaView } from 'react-native-safe-area-context';

import { api } from '../api/axios';
import RemoteImage from '../components/ui/RemoteImage';
import { colors, motion } from '../theme/tokens';
import { normalizeMediaUrl } from '../utils/media';


const webArticleUrl = (id) => (
  id === 'queretaro-recicla'
    ? 'https://www.zerowaste-qro.com/noticia-queretaro'
    : `https://www.zerowaste-qro.com/tema/${id}`
);

export default function ArticleDetailScreen() {
  const navigation = useNavigation();
  const route = useRoute();
  const articleId = route.params?.articleId;
  const bundledArticle = route.params?.article;
  const [article, setArticle] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const entrance = useRef(new Animated.Value(0)).current;

  const loadArticle = useCallback(async () => {
    if (bundledArticle) {
      setArticle(bundledArticle);
      setLoading(false);
      setError('');
      return;
    }
    if (!articleId) {
      setError('No se indicó el artículo que deseas consultar.');
      setLoading(false);
      return;
    }
    setLoading(true);
    setError('');
    try {
      const { data } = await api.get(`/articles/${encodeURIComponent(articleId)}`);
      setArticle(data);
    } catch (requestError) {
      setArticle(null);
      setError(requestError.userMessage || 'No fue posible cargar el artículo.');
    } finally {
      setLoading(false);
    }
  }, [articleId, bundledArticle]);

  useEffect(() => {
    void loadArticle();
  }, [loadArticle]);

  useEffect(() => {
    if (!article) return undefined;
    let active = true;
    AccessibilityInfo.isReduceMotionEnabled().then((reduceMotion) => {
      if (!active) return;
      Animated.timing(entrance, {
        toValue: 1,
        duration: reduceMotion ? 0 : motion.navigation,
        easing: Easing.out(Easing.cubic),
        useNativeDriver: true,
      }).start();
    });
    return () => { active = false; };
  }, [article, entrance]);

  const shareArticle = () => {
    if (!article) return;
    void Share.share({
      title: article.title,
      message: `${article.title}\n${webArticleUrl(article.id)}`,
    });
  };

  const imageUrl = normalizeMediaUrl(article?.image_url);
  const published = article?.published_at
    ? new Date(`${article.published_at}T12:00:00`).toLocaleDateString('es-MX', { day: 'numeric', month: 'long', year: 'numeric' })
    : null;

  return (
    <SafeAreaView className="flex-1 bg-white" edges={['top', 'bottom']}>
      <StatusBar style="dark" />
      <View className="z-10 flex-row items-center border-b border-slate-100 bg-white px-4 py-3">
        <TouchableOpacity onPress={() => navigation.goBack()} className="h-11 w-11 items-center justify-center rounded-full bg-slate-100" accessibilityLabel="Volver">
          <ArrowLeft color={colors.text} size={21} />
        </TouchableOpacity>
        <Text className="ml-4 flex-1 text-lg font-black text-slate-900" numberOfLines={1}>Artículo</Text>
        <TouchableOpacity onPress={shareArticle} disabled={!article} className="h-11 w-11 items-center justify-center rounded-full bg-emerald-50" accessibilityLabel="Compartir artículo">
          <Share2 color={article ? colors.green : '#94A3B8'} size={20} />
        </TouchableOpacity>
      </View>

      {loading ? (
        <View className="flex-1 items-center justify-center bg-slate-50 px-8">
          <ActivityIndicator color={colors.green} size="large" />
          <Text className="mt-4 font-bold text-slate-500">Cargando artículo…</Text>
        </View>
      ) : error || !article ? (
        <View className="flex-1 items-center justify-center bg-slate-50 px-8">
          <Text className="text-center text-lg font-black text-slate-900">No fue posible cargar el artículo</Text>
          <Text className="mt-2 text-center leading-5 text-slate-500">{error}</Text>
          <TouchableOpacity onPress={loadArticle} className="mt-6 rounded-full bg-emerald-700 px-7 py-3.5" accessibilityRole="button">
            <Text className="font-black text-white">Reintentar</Text>
          </TouchableOpacity>
        </View>
      ) : (
        <Animated.View className="flex-1" style={{ opacity: entrance, transform: [{ translateY: entrance.interpolate({ inputRange: [0, 1], outputRange: [12, 0] }) }] }}>
          <ScrollView showsVerticalScrollIndicator={false} contentContainerStyle={{ paddingBottom: 32 }}>
            <RemoteImage uri={imageUrl} aspectRatio={16 / 10} className="w-full" accessibilityLabel={`Imagen de ${article.title}`} />
            <View className="px-5 pb-4 pt-6">
              <Text className="self-start rounded-full bg-emerald-50 px-3 py-1.5 text-[11px] font-black uppercase tracking-widest text-emerald-700">{article.category}</Text>
              <Text className="mt-4 text-[30px] font-black leading-[35px] tracking-tight text-slate-950">{article.title}</Text>
              <View className="mt-4 flex-row flex-wrap items-center gap-4">
                {published ? <View className="flex-row items-center gap-1.5"><CalendarDays color={colors.textSecondary} size={15} /><Text className="text-xs font-semibold text-slate-500">{published}</Text></View> : null}
                <View className="flex-row items-center gap-1.5"><Clock3 color={colors.textSecondary} size={15} /><Text className="text-xs font-semibold text-slate-500">{article.read_time}</Text></View>
              </View>
              {article.author ? <Text className="mt-3 text-sm font-bold text-slate-600">Por {article.author}</Text> : null}
              <Text className="mt-6 border-l-4 border-emerald-500 pl-4 text-[17px] font-semibold leading-7 text-slate-700">{article.excerpt}</Text>

              {(article.blocks || []).map((block, index) => (
                <View key={`${block.type}-${index}`} className="mt-7">
                  {block.heading ? <Text className="mb-3 text-[22px] font-black leading-7 text-emerald-950">{block.heading}</Text> : null}
                  {block.text ? <Text className="text-[16px] leading-7 text-slate-700">{block.text}</Text> : null}
                  {block.items?.map((item, itemIndex) => (
                    <View key={`${index}-${itemIndex}`} className="mt-3 flex-row items-start pr-2">
                      <View className="mr-3 mt-2.5 h-2 w-2 rounded-full bg-emerald-500" />
                      <Text className="flex-1 text-[16px] leading-7 text-slate-700">{item}</Text>
                    </View>
                  ))}
                </View>
              ))}
            </View>
          </ScrollView>
        </Animated.View>
      )}
    </SafeAreaView>
  );
}
