import Mapbox from '@rnmapbox/maps';


export const MAPBOX_PUBLIC_TOKEN = process.env.EXPO_PUBLIC_MAPBOX_TOKEN?.trim() || '';
export const HAS_VALID_MAPBOX_TOKEN = (
  MAPBOX_PUBLIC_TOKEN.startsWith('pk.')
  && !MAPBOX_PUBLIC_TOKEN.includes('YOUR_')
);

let initializationPromise = null;

if (HAS_VALID_MAPBOX_TOKEN) Mapbox.setAccessToken(MAPBOX_PUBLIC_TOKEN);

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
