import React, { useEffect, useMemo, useRef, useState } from 'react';
import { ActivityIndicator, ScrollView, Text, TextInput, TouchableOpacity, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { ArrowLeft, CalendarDays, Check, CheckCircle2, Clock3, MapPin, Search } from 'lucide-react-native';
import { StatusBar } from 'expo-status-bar';

import { api } from '../api/axios';
import KeyboardAwareScreen from '../components/ui/KeyboardAwareScreen';
import { searchAddresses } from '../utils/addressSearch';

const SCHEDULE_MESSAGE = 'La disponibilidad se confirma con el horario vigente de Querétaro.';

const isoDate = (date) => {
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const day = String(date.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
};

export default function CreateCollectionScreen({ navigation, route }) {
  const routeCoordinates = route.params?.coordinates;
  const dates = useMemo(() => Array.from({ length: 21 }, (_, index) => {
    const value = new Date();
    value.setHours(12, 0, 0, 0);
    value.setDate(value.getDate() + index);
    return value;
  }), []);
  const [form, setForm] = useState({ direccion: '', materiales: '', cantidad_estimada: '', notas: '' });
  const [date, setDate] = useState(dates[0] ? isoDate(dates[0]) : '');
  const [slots, setSlots] = useState([]);
  const [slot, setSlot] = useState(null);
  const [loadingSlots, setLoadingSlots] = useState(false);
  const [submitting, setSubmitting] = useState(false);
  const [created, setCreated] = useState(null);
  const [error, setError] = useState('');
  const [slotsError, setSlotsError] = useState('');
  const [slotsRequestKey, setSlotsRequestKey] = useState(0);
  const [addressSuggestions, setAddressSuggestions] = useState([]);
  const [addressSearching, setAddressSearching] = useState(false);
  const [coordinates, setCoordinates] = useState(routeCoordinates);
  const addressAbortRef = useRef(null);

  useEffect(() => {
    const query = form.direccion.trim();
    addressAbortRef.current?.abort();
    if (query.length < 3) {
      setAddressSuggestions([]);
      setAddressSearching(false);
      return undefined;
    }
    const controller = new AbortController();
    addressAbortRef.current = controller;
    setAddressSearching(true);
    const timer = setTimeout(() => {
      searchAddresses(query, { signal: controller.signal })
        .then(setAddressSuggestions)
        .catch((requestError) => { if (requestError.name !== 'AbortError') setAddressSuggestions([]); })
        .finally(() => { if (!controller.signal.aborted) setAddressSearching(false); });
    }, 350);
    return () => { clearTimeout(timer); controller.abort(); };
  }, [form.direccion]);

  useEffect(() => {
    if (!date) return;
    let active = true;
    setLoadingSlots(true);
    setSlot(null);
    setSlotsError('');
    api.get('/recolecciones/disponibilidad', { params: { fecha: date } })
      .then(({ data }) => { if (active) setSlots(data?.slots || []); })
      .catch((requestError) => { if (active) { setSlots([]); setSlotsError(requestError.userMessage || 'No fue posible consultar la disponibilidad.'); } })
      .finally(() => { if (active) setLoadingSlots(false); });
    return () => { active = false; };
  }, [date, slotsRequestKey]);

  const update = (key) => (value) => setForm((current) => ({ ...current, [key]: value }));
  const submit = async () => {
    if (submitting) return;
    if (!form.direccion.trim() || !form.materiales.trim() || !form.cantidad_estimada.trim() || !slot) {
      setError('Completa la dirección, material, cantidad estimada, fecha y horario.');
      return;
    }
    if (!Array.isArray(coordinates) || coordinates.length !== 2) {
      setError('Selecciona una dirección de la lista para ubicar correctamente tu domicilio.');
      return;
    }
    setSubmitting(true);
    setError('');
    try {
      const { data } = await api.post('/recolecciones', {
        direccion: form.direccion.trim(), materiales: form.materiales.trim(),
        cantidad_estimada: form.cantidad_estimada.trim(), notas: form.notas.trim() || null,
        longitud: Number(coordinates[0]), latitud: Number(coordinates[1]), scheduled_at: slot.value,
      });
      setCreated(data);
    } catch (requestError) {
      setError(requestError.response?.data?.detail || requestError.userMessage || SCHEDULE_MESSAGE);
    } finally {
      setSubmitting(false);
    }
  };

  if (created) {
    return <SafeAreaView className="flex-1 items-center justify-center bg-emerald-50 px-7"><StatusBar style="dark" /><CheckCircle2 color="#059669" size={64} /><Text className="mt-5 text-center text-2xl font-black text-emerald-950">Solicitud confirmada</Text><Text className="mt-3 text-center text-slate-600">Folio</Text><Text className="mt-1 font-black tracking-wider text-emerald-800">{created.folio}</Text><Text className="mt-5 text-center text-slate-700">{new Date(created.scheduled_at).toLocaleString('es-MX', { dateStyle: 'long', timeStyle: 'short', timeZone: 'America/Mexico_City' })}</Text><TouchableOpacity onPress={() => navigation.replace('MisRecolecciones')} className="mt-8 rounded-2xl bg-emerald-700 px-7 py-4"><Text className="font-black text-white">Ver solicitud</Text></TouchableOpacity><TouchableOpacity onPress={() => navigation.navigate('Main')} className="mt-3 px-7 py-3"><Text className="font-bold text-slate-600">Volver al inicio</Text></TouchableOpacity></SafeAreaView>;
  }

  return (
    <SafeAreaView className="flex-1 bg-slate-50" edges={['top', 'bottom']}><StatusBar style="dark" />
      <View className="flex-row items-center border-b border-slate-100 bg-white px-4 py-3"><TouchableOpacity onPress={() => navigation.goBack()} className="h-11 w-11 items-center justify-center rounded-full bg-slate-100"><ArrowLeft color="#0F172A" size={21} /></TouchableOpacity><View className="ml-4"><Text className="text-lg font-black text-slate-900">Solicitar recolección</Text><Text className="text-xs text-slate-500">Querétaro, Qro.</Text></View></View>
      <KeyboardAwareScreen
        contentContainerStyle={{ padding: 20, paddingBottom: 28 }}
        footer={<View className="border-t border-slate-100 bg-white px-5 py-3"><TouchableOpacity disabled={submitting || !slot} onPress={submit} className="items-center rounded-2xl bg-emerald-700 py-4 disabled:opacity-50">{submitting ? <ActivityIndicator color="white" /> : <Text className="text-base font-black text-white">Confirmar solicitud</Text>}</TouchableOpacity></View>}
      >
        <View className="rounded-[24px] border border-emerald-100 bg-emerald-950 p-5"><Text className="text-xs font-black uppercase tracking-widest text-emerald-300">Recolección a domicilio</Text><Text className="mt-2 text-lg font-black leading-6 text-white">Agenda una visita en tres pasos</Text><Text className="mt-2 font-semibold leading-5 text-emerald-100">Escribe tu domicilio, elige la fecha y confirma un horario disponible. {SCHEDULE_MESSAGE}</Text></View>
        {error ? <View className="mt-4 rounded-2xl border border-red-200 bg-red-50 p-4"><Text accessibilityLiveRegion="polite" className="font-bold text-red-700">{error}</Text></View> : null}
        <Field icon={<MapPin color="#059669" size={18} />} label="Dirección del domicilio"><View className="mt-2 flex-row items-center rounded-2xl border border-slate-200 bg-white px-4"><Search color="#64748B" size={18} /><TextInput value={form.direccion} onChangeText={update('direccion')} placeholder="Ej. Avenida Universidad 123" className="h-14 flex-1 px-3 text-slate-900" autoCorrect={false} /></View>{addressSearching ? <ActivityIndicator className="mt-3" color="#059669" /> : null}{addressSuggestions.length ? <View className="mt-2 overflow-hidden rounded-2xl border border-slate-200 bg-white">{addressSuggestions.map((suggestion, index) => <TouchableOpacity key={suggestion.id} onPress={() => { setForm((current) => ({ ...current, direccion: suggestion.label })); setCoordinates(suggestion.coordinates); setAddressSuggestions([]); }} className={`min-h-14 flex-row items-center px-4 py-3 ${index ? 'border-t border-slate-100' : ''}`}><MapPin color="#059669" size={17} /><View className="ml-3 flex-1"><Text className="font-bold leading-5 text-slate-900">{suggestion.label}</Text>{suggestion.context ? <Text className="mt-0.5 text-xs text-slate-500">{suggestion.context}</Text> : null}</View><Check color="#059669" size={17} /></TouchableOpacity>)}</View> : null}<Text className="mt-2 text-xs leading-4 text-slate-500">Selecciona una sugerencia para usar sus coordenadas exactas.</Text></Field>
        <Field label="Material"><TextInput value={form.materiales} onChangeText={update('materiales')} placeholder="PET, cartón, vidrio…" className="mt-2 rounded-xl border border-slate-200 bg-white p-4 text-slate-900" /></Field>
        <Field label="Cantidad estimada"><TextInput value={form.cantidad_estimada} onChangeText={update('cantidad_estimada')} placeholder="Ej. 2 bolsas, 5 kg" className="mt-2 rounded-xl border border-slate-200 bg-white p-4 text-slate-900" /></Field>
        <Field icon={<CalendarDays color="#059669" size={18} />} label="Fecha"><ScrollView horizontal showsHorizontalScrollIndicator={false} className="mt-3">{dates.map((item) => { const value = isoDate(item); return <TouchableOpacity key={value} onPress={() => setDate(value)} className={`mr-2 rounded-2xl border px-4 py-3 ${date === value ? 'border-emerald-700 bg-emerald-700' : 'border-slate-200 bg-white'}`}><Text className={`text-xs font-bold ${date === value ? 'text-white' : 'text-slate-500'}`}>{item.toLocaleDateString('es-MX', { weekday: 'short' })}</Text><Text className={`mt-1 font-black ${date === value ? 'text-white' : 'text-slate-900'}`}>{item.getDate()} {item.toLocaleDateString('es-MX', { month: 'short' })}</Text></TouchableOpacity>; })}</ScrollView></Field>
        <Field icon={<Clock3 color="#059669" size={18} />} label="Horario">{loadingSlots ? <ActivityIndicator className="mt-4" color="#059669" /> : slotsError ? <View className="mt-3 rounded-xl border border-red-200 bg-red-50 p-3"><Text className="font-bold text-red-700">{slotsError}</Text><TouchableOpacity onPress={() => setSlotsRequestKey((value) => value + 1)}><Text className="mt-2 font-black text-red-700">Reintentar horarios</Text></TouchableOpacity></View> : slots.length ? <View className="mt-3 flex-row flex-wrap">{slots.map((item) => <TouchableOpacity key={item.value} onPress={() => setSlot(item)} className={`mb-2 mr-2 rounded-xl border px-4 py-3 ${slot?.value === item.value ? 'border-emerald-700 bg-emerald-700' : 'border-slate-200 bg-white'}`}><Text className={`font-black ${slot?.value === item.value ? 'text-white' : 'text-slate-800'}`}>{item.label}</Text></TouchableOpacity>)}</View> : <Text className="mt-3 font-bold text-amber-700">No hay horarios disponibles para esta fecha.</Text>}</Field>
        <Field label="Notas"><TextInput value={form.notas} onChangeText={update('notas')} placeholder="Indicaciones opcionales" multiline className="mt-2 min-h-[96px] rounded-xl border border-slate-200 bg-white p-4 text-slate-900" textAlignVertical="top" /></Field>
      </KeyboardAwareScreen>
    </SafeAreaView>
  );
}

function Field({ icon, label, children }) { return <View className="mt-5"><View className="flex-row items-center">{icon}<Text className={`${icon ? 'ml-2' : ''} font-black text-slate-800`}>{label}</Text></View>{children}</View>; }
