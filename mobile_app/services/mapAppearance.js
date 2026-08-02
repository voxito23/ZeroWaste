export const MAP_APPEARANCE_PREFERENCES = Object.freeze(['automatic', 'day', 'dusk', 'night']);
export const MAP_TIME_ZONE = 'America/Mexico_City';

const localMinutes = (date = new Date()) => {
  const parts = new Intl.DateTimeFormat('es-MX', {
    timeZone: MAP_TIME_ZONE,
    hour: '2-digit',
    minute: '2-digit',
    hourCycle: 'h23',
  }).formatToParts(date);
  const hour = Number(parts.find((part) => part.type === 'hour')?.value);
  const minute = Number(parts.find((part) => part.type === 'minute')?.value);
  return (Number.isFinite(hour) ? hour : 12) * 60 + (Number.isFinite(minute) ? minute : 0);
};

export const getAutomaticLightPreset = (date = new Date()) => {
  const minutes = localMinutes(date);
  if (minutes >= 330 && minutes < 480) return 'dawn';
  if (minutes >= 480 && minutes < 1020) return 'day';
  if (minutes >= 1020 && minutes < 1170) return 'dusk';
  return 'night';
};

export const resolveLightPreset = (preference = 'automatic', date = new Date()) => {
  if (preference === 'day' || preference === 'dusk' || preference === 'night') return preference;
  return getAutomaticLightPreset(date);
};

export const isMapAppearancePreference = (value) => MAP_APPEARANCE_PREFERENCES.includes(value);
