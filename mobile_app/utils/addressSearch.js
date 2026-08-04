import { MAPBOX_PUBLIC_TOKEN } from './mapbox';

const QUERETARO_PROXIMITY = '-100.3929,20.5888';

export const searchAddresses = async (query, { signal } = {}) => {
  const value = String(query || '').trim();
  if (value.length < 3 || !MAPBOX_PUBLIC_TOKEN) return [];
  const params = new URLSearchParams({
    q: value,
    access_token: MAPBOX_PUBLIC_TOKEN,
    country: 'mx',
    language: 'es',
    proximity: QUERETARO_PROXIMITY,
    types: 'address,street,neighborhood,place,postcode',
    limit: '5',
  });
  const response = await fetch(`https://api.mapbox.com/search/geocode/v6/forward?${params}`, { signal });
  if (!response.ok) throw new Error('No fue posible buscar domicilios en este momento.');
  const payload = await response.json();
  return (payload?.features || []).map((feature) => ({
    id: feature.id,
    label: feature.properties?.full_address || feature.properties?.name_preferred || feature.properties?.name || '',
    context: feature.properties?.place_formatted || '',
    coordinates: feature.geometry?.coordinates,
  })).filter((item) => item.id && item.label && Array.isArray(item.coordinates));
};
