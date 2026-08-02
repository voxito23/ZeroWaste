export const isValidCoordinate = (value) => {
  if (!Array.isArray(value) || value.length < 2) return false;
  const [longitude, latitude] = value;
  const normalizedLongitude = Number(longitude);
  const normalizedLatitude = Number(latitude);
  return Number.isFinite(normalizedLongitude)
    && Number.isFinite(normalizedLatitude)
    && Math.abs(normalizedLongitude) <= 180
    && Math.abs(normalizedLatitude) <= 90
    && !(normalizedLongitude === 0 && normalizedLatitude === 0);
};
