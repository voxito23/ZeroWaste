import { useEffect, useMemo, useState } from 'react';
import AsyncStorage from '@react-native-async-storage/async-storage';

import { isMapAppearancePreference, resolveLightPreset } from '../services/mapAppearance';

export const MAP_APPEARANCE_STORAGE_KEY = 'zerowaste.map.appearance';
const preferenceListeners = new Set();
let currentPreference = 'automatic';

export default function useMapAppearance() {
  const [preference, setPreferenceState] = useState(currentPreference);
  const [clock, setClock] = useState(() => new Date());
  const [hydrated, setHydrated] = useState(false);

  useEffect(() => {
    let active = true;
    AsyncStorage.getItem(MAP_APPEARANCE_STORAGE_KEY)
      .then((stored) => {
        if (active && isMapAppearancePreference(stored)) {
          currentPreference = stored;
          preferenceListeners.forEach((listener) => listener(stored));
        }
      })
      .finally(() => { if (active) setHydrated(true); });
    return () => { active = false; };
  }, []);

  useEffect(() => {
    preferenceListeners.add(setPreferenceState);
    return () => preferenceListeners.delete(setPreferenceState);
  }, []);

  useEffect(() => {
    if (preference !== 'automatic') return undefined;
    const timer = setInterval(() => setClock(new Date()), 60_000);
    return () => clearInterval(timer);
  }, [preference]);

  const setPreference = async (nextPreference) => {
    if (!isMapAppearancePreference(nextPreference)) return false;
    currentPreference = nextPreference;
    preferenceListeners.forEach((listener) => listener(nextPreference));
    await AsyncStorage.setItem(MAP_APPEARANCE_STORAGE_KEY, nextPreference);
    return true;
  };

  const lightPreset = useMemo(
    () => resolveLightPreset(preference, clock),
    [clock, preference],
  );

  return { preference, setPreference, lightPreset, hydrated };
}
