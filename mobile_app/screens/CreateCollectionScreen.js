import React, { useEffect, useMemo, useRef, useState } from 'react';
import { ActivityIndicator, ScrollView, Text, TextInput, TouchableOpacity, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { ArrowLeft, CalendarDays, Check, CheckCircle2, ChevronDown, Clock3, MapPin, PackageCheck, Search } from 'lucide-react-native';
import { StatusBar } from 'expo-status-bar';

import { api } from '../api/axios';
import KeyboardAwareScreen from '../components/ui/KeyboardAwareScreen';
import { searchAddresses } from '../utils/addressSearch';

const SCHEDULE_MESSAGE = 'La disponibilidad se confirma con el horario vigente de Querétaro.';
const MATERIAL_OPTIONS = ['PET', 'Tapitas', 'Cartón', 'Plástico', 'Latas', 'Baterías', 'Vidrio', 'Electrónicos'];
const ADDRESS_MIN = 10;
const ADDRESS_MAX = 250;
const QUANTITY_MIN = 2;
const QUANTITY_MAX = 100;
const NOTES_MAX = 500;

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
  const [form, setForm] = useState({ direccion: '', cantidad_estimada: '', notas: '' });
  const [selectedMaterials, setSelectedMaterials] = useState([]);
  const [materialsOpen, setMaterialsOpen] = useState(false);
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
  const toggleMaterial = (material) => setSelectedMaterials((current) => current.includes(material) ? current.filter((item) => item !== material) : [...current, material]);
  const submit = async () => {
    if (submitting) return;
    if (!form.direccion.trim() || !selectedMaterials.length || !form.cantidad_estimada.trim() || !slot) {
      setError('Completa la dirección, selecciona al menos un material, indica la cantidad y elige un horario.');
      return;
    }
    if (form.direccion.trim().length < ADDRESS_MIN) {
      setError(`La dirección debe tener al menos ${ADDRESS_MIN} caracteres.`);
      return;
    }
    if (form.cantidad_estimada.trim().length < QUANTITY_MIN) {
      setError(`La cantidad estimada debe tener al menos ${QUANTITY_MIN} caracteres.`);
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
        direccion: form.direccion.trim(), materiales: selectedMaterials.join(', '),
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
        <Field icon={<MapPin color="#059669" size={18} />} label="Dirección del domicilio"><View className="mt-2 flex-row items-center rounded-2xl border border-slate-200 bg-white px-4"><Search color="#64748B" size={18} /><TextInput value={form.direccion} onChangeText={(value) => { update('direccion')(value); setCoordinates(null); }} maxLength={ADDRESS_MAX} placeholder="Ej. Avenida Universidad 123" className="h-14 flex-1 px-3 text-slate-900" autoCorrect={false} /></View>{addressSearching ? <ActivityIndicator className="mt-3" color="#059669" /> : null}{addressSuggestions.length ? <View className="mt-2 overflow-hidden rounded-2xl border border-slate-200 bg-white">{addressSuggestions.map((suggestion, index) => <TouchableOpacity key={suggestion.id} onPress={() => { setForm((current) => ({ ...current, direccion: suggestion.label.slice(0, ADDRESS_MAX) })); setCoordinates(suggestion.coordinates); setAddressSuggestions([]); }} className={`min-h-14 flex-row items-center px-4 py-3 ${index ? 'border-t border-slate-100' : ''}`}><MapPin color="#059669" size={17} /><View className="ml-3 flex-1"><Text className="font-bold leading-5 text-slate-900">{suggestion.label}</Text>{suggestion.context ? <Text className="mt-0.5 text-xs text-slate-500">{suggestion.context}</Text> : null}</View><Check color="#059669" size={17} /></TouchableOpacity>)}</View> : null}<View className="mt-2 flex-row justify-between"><Text className="flex-1 text-xs leading-4 text-slate-500">Selecciona una sugerencia para usar sus coordenadas exactas.</Text><Text className="ml-2 text-xs font-bold text-slate-400">{form.direccion.length}/{ADDRESS_MAX}</Text></View></Field>
        <Field icon={<PackageCheck color="#059669" size={18} />} label="Materiales"><TouchableOpacity onPress={() => setMaterialsOpen((value) => !value)} className={`mt-2 min-h-14 flex-row items-center rounded-2xl border bg-white px-4 ${materialsOpen ? 'border-emerald-500' : 'border-slate-200'}`} accessibilityRole="button" accessibilityState={{ expanded: materialsOpen }}><View className="flex-1"><Text className={selectedMaterials.length ? 'font-bold text-slate-900' : 'text-slate-400'}>{selectedMaterials.length ? `${selectedMaterials.length} seleccionado${selectedMaterials.length === 1 ? '' : 's'}` : 'Selecciona uno o varios materiales'}</Text>{selectedMaterials.length ? <Text className="mt-0.5 text-xs text-slate-500" numberOfLines={1}>{selectedMaterials.join(', ')}</Text> : null}</View><ChevronDown color="#64748B" size={20} style={{ transform: [{ rotate: materialsOpen ? '180deg' : '0deg' }] }} /></TouchableOpacity>{materialsOpen ? <View className="mt-2 overflow-hidden rounded-2xl border border-slate-200 bg-white">{MATERIAL_OPTIONS.map((material, index) => { const selected = selectedMaterials.includes(material); return <TouchableOpacity key={material} onPress={() => toggleMaterial(material)} className={`min-h-12 flex-row items-center px-4 ${index ? 'border-t border-slate-100' : ''}`} accessibilityRole="checkbox" accessibilityState={{ checked: selected }}><View className={`h-5 w-5 items-center justify-center rounded-md border ${selected ? 'border-emerald-700 bg-emerald-700' : 'border-slate-300 bg-white'}`}>{selected ? <Check color="white" size={14} strokeWidth={3} /> : null}</View><Text className={`ml-3 flex-1 ${selected ? 'font-black text-emerald-900' : 'font-semibold text-slate-700'}`}>{material}</Text></TouchableOpacity>; })}</View> : null}<Text className="mt-2 text-xs text-slate-500">Mínimo 1 y máximo {MATERIAL_OPTIONS.length} materiales.</Text></Field>
        <Field label="Cantidad estimada"><TextInput value={form.cantidad_estimada} onChangeText={update('cantidad_estimada')} maxLength={QUANTITY_MAX} placeholder="Ej. 2 bolsas, 5 kg" className="mt-2 rounded-xl border border-slate-200 bg-white p-4 text-slate-900" /><Text className="mt-2 text-right text-xs font-bold text-slate-400">{form.cantidad_estimada.length}/{QUANTITY_MAX} · mín. {QUANTITY_MIN}</Text></Field>
        <Field icon={<CalendarDays color="#059669" size={18} />} label="Fecha"><ScrollView horizontal showsHorizontalScrollIndicator={false} className="mt-3">{dates.map((item) => { const value = isoDate(item); return <TouchableOpacity key={value} onPress={() => setDate(value)} className={`mr-2 rounded-2xl border px-4 py-3 ${date === value ? 'border-emerald-700 bg-emerald-700' : 'border-slate-200 bg-white'}`}><Text className={`text-xs font-bold ${date === value ? 'text-white' : 'text-slate-500'}`}>{item.toLocaleDateString('es-MX', { weekday: 'short' })}</Text><Text className={`mt-1 font-black ${date === value ? 'text-white' : 'text-slate-900'}`}>{item.getDate()} {item.toLocaleDateString('es-MX', { month: 'short' })}</Text></TouchableOpacity>; })}</ScrollView></Field>
        <Field icon={<Clock3 color="#059669" size={18} />} label="Horario">{loadingSlots ? <ActivityIndicator className="mt-4" color="#059669" /> : slotsError ? <View className="mt-3 rounded-xl border border-red-200 bg-red-50 p-3"><Text className="font-bold text-red-700">{slotsError}</Text><TouchableOpacity onPress={() => setSlotsRequestKey((value) => value + 1)}><Text className="mt-2 font-black text-red-700">Reintentar horarios</Text></TouchableOpacity></View> : slots.length ? <View className="mt-3 flex-row flex-wrap">{slots.map((item) => <TouchableOpacity key={item.value} onPress={() => setSlot(item)} className={`mb-2 mr-2 rounded-xl border px-4 py-3 ${slot?.value === item.value ? 'border-emerald-700 bg-emerald-700' : 'border-slate-200 bg-white'}`}><Text className={`font-black ${slot?.value === item.value ? 'text-white' : 'text-slate-800'}`}>{item.label}</Text></TouchableOpacity>)}</View> : <Text className="mt-3 font-bold text-amber-700">No hay horarios disponibles para esta fecha.</Text>}</Field>
        <Field label="Notas"><TextInput value={form.notas} onChangeText={update('notas')} maxLength={NOTES_MAX} placeholder="Indicaciones opcionales para encontrar el domicilio" multiline className="mt-2 min-h-[96px] rounded-xl border border-slate-200 bg-white p-4 text-slate-900" textAlignVertical="top" /><Text className="mt-2 text-right text-xs font-bold text-slate-400">{form.notas.length}/{NOTES_MAX}</Text></Field>
      </KeyboardAwareScreen>
    </SafeAreaView>
  );
}

function Field({ icon, label, children }) { return <View className="mt-5"><View className="flex-row items-center">{icon}<Text className={`${icon ? 'ml-2' : ''} font-black text-slate-800`}>{label}</Text></View>{children}</View>; }
