import { Linking, Platform } from 'react-native';

export const openDirections = async (point) => {
  const latitude = Number(point?.latitud ?? point?.latitude);
  const longitude = Number(point?.longitud ?? point?.longitude);
  if (!Number.isFinite(latitude) || !Number.isFinite(longitude)) throw new Error('Este punto no tiene coordenadas válidas.');
  const label = encodeURIComponent(point?.nombre || 'Punto ZeroWaste');
  const nativeUrl = Platform.select({ ios: `maps://?daddr=${latitude},${longitude}&q=${label}`, android: `geo:${latitude},${longitude}?q=${latitude},${longitude}(${label})` });
  const fallback = `https://www.google.com/maps/dir/?api=1&destination=${latitude},${longitude}`;
  if (nativeUrl && await Linking.canOpenURL(nativeUrl)) return Linking.openURL(nativeUrl);
  return Linking.openURL(fallback);
};
