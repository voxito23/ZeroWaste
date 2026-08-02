import { MAPBOX_PUBLIC_TOKEN } from './mapbox';
import { isValidCoordinate as validCoordinate } from './coordinates';

const PROFILES = new Set(['walking', 'cycling', 'driving', 'driving-traffic']);

const normalizeStep = (step, legIndex, stepIndex) => ({
  id: `${legIndex}:${stepIndex}`,
  distanceMeters: Number(step?.distance) || 0,
  durationSeconds: Number(step?.duration) || 0,
  name: step?.name || '',
  instruction: step?.maneuver?.instruction || '',
  maneuver: step?.maneuver || null,
  bannerInstructions: Array.isArray(step?.bannerInstructions) ? step.bannerInstructions : [],
  voiceInstructions: Array.isArray(step?.voiceInstructions) ? step.voiceInstructions : [],
  geometry: step?.geometry || null,
});

const normalizeRoute = (route, profile, index) => {
  const legs = Array.isArray(route?.legs) ? route.legs : [];
  const steps = legs.flatMap((leg, legIndex) => (
    Array.isArray(leg?.steps) ? leg.steps.map((step, stepIndex) => normalizeStep(step, legIndex, stepIndex)) : []
  ));
  return {
    id: `route:${profile}:${index}`,
    geometry: { type: 'Feature', properties: { routeIndex: index }, geometry: route.geometry },
    distanceMeters: Number(route.distance) || 0,
    durationSeconds: Number(route.duration) || 0,
    durationTypicalSeconds: Number(route.duration_typical) || null,
    weight: Number(route.weight) || null,
    weightName: route.weight_name || '',
    legs,
    steps,
    instruction: steps[0]?.instruction || 'Sigue la ruta marcada en el mapa.',
    profile,
  };
};

export const fetchMapboxRoute = async (origin, destination, profile = 'driving-traffic', { signal, alternatives = true } = {}) => {
  if (!PROFILES.has(profile)) throw new Error('Perfil de ruta no válido.');
  if (!validCoordinate(origin) || !validCoordinate(destination)) throw new Error('No fue posible determinar las coordenadas de la ruta.');
  if (!MAPBOX_PUBLIC_TOKEN) throw new Error('Mapbox no está configurado para calcular rutas.');
  const coordinates = [...origin, ...destination].map(Number);
  const controller = new AbortController();
  let timedOut = false;
  const abortFromCaller = () => controller.abort();
  if (signal?.aborted) controller.abort();
  else signal?.addEventListener('abort', abortFromCaller, { once: true });
  const timeout = setTimeout(() => { timedOut = true; controller.abort(); }, 12000);
  try {
    const path = `${coordinates[0]},${coordinates[1]};${coordinates[2]},${coordinates[3]}`;
    const annotations = profile === 'driving-traffic' ? '&annotations=congestion,distance,duration' : '';
    const url = `https://api.mapbox.com/directions/v5/mapbox/${profile}/${path}?alternatives=${alternatives ? 'true' : 'false'}&banner_instructions=true&continue_straight=true&geometries=geojson&language=es&overview=full&steps=true&voice_instructions=true&voice_units=metric${annotations}&access_token=${encodeURIComponent(MAPBOX_PUBLIC_TOKEN)}`;
    const response = await fetch(url, { signal: controller.signal });
    if (!response.ok) throw new Error('Mapbox no pudo calcular una ruta en este momento.');
    const payload = await response.json();
    if (payload?.code && payload.code !== 'Ok') throw new Error(payload.message || 'Mapbox no encontró una ruta válida.');
    const routes = (Array.isArray(payload?.routes) ? payload.routes : [])
      .filter((route) => route?.geometry?.type === 'LineString' && route.geometry.coordinates?.length >= 2)
      .map((route, index) => normalizeRoute(route, profile, index));
    if (!routes.length) throw new Error('No existe una ruta disponible hacia este punto.');
    return { ...routes[0], alternatives: routes.slice(1) };
  } catch (error) {
    if (error.name === 'AbortError' && timedOut) throw new Error('La consulta de la ruta tardó demasiado. Inténtalo nuevamente.');
    throw error;
  } finally {
    clearTimeout(timeout);
    signal?.removeEventListener('abort', abortFromCaller);
  }
};
