import Mapbox from '@rnmapbox/maps';

const validPublicToken = (value) => value.startsWith('pk.') && !value.includes('YOUR_');

export let MAPBOX_PUBLIC_TOKEN = process.env.EXPO_PUBLIC_MAPBOX_TOKEN?.trim() || '';
export let HAS_VALID_MAPBOX_TOKEN = validPublicToken(MAPBOX_PUBLIC_TOKEN);
export const MAP_STYLE_URL = 'mapbox://styles/mapbox/standard';
export const MAP_2D_STYLE_URL = Mapbox.StyleURL.Street;
export const MAP_FALLBACK_STYLE_URL = Mapbox.StyleURL.Street;
export const MAP_TRAFFIC_STYLE_URL = Mapbox.StyleURL.TrafficDay;
export const QUERETARO_CENTER = [-100.3929, 20.5888];
export const MAP_DEFAULT_CAMERA = Object.freeze({
  centerCoordinate: QUERETARO_CENTER,
  zoomLevel: 13.5,
  pitch: 42,
  heading: 0,
});

let initializationPromise = null;

if (HAS_VALID_MAPBOX_TOKEN) Mapbox.setAccessToken(MAPBOX_PUBLIC_TOKEN);

export const configureMapbox = (value) => {
  const token = String(value || '').trim();
  if (!validPublicToken(token)) return false;
  MAPBOX_PUBLIC_TOKEN = token;
  HAS_VALID_MAPBOX_TOKEN = true;
  initializationPromise = Promise.resolve(Mapbox.setAccessToken(token)).then(() => true);
  return true;
};

export const initializeMapbox = () => {
  if (!HAS_VALID_MAPBOX_TOKEN) {
    return Promise.reject(new Error('El token público de Mapbox no está configurado correctamente.'));
  }
  if (!initializationPromise) {
    initializationPromise = Promise.resolve(Mapbox.setAccessToken(MAPBOX_PUBLIC_TOKEN))
      .then(() => true)
      .catch((error) => {
        initializationPromise = null;
        throw error;
      });
  }
  return initializationPromise;
};

export { Mapbox };
