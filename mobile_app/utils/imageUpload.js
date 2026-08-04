export const MAX_PROFILE_IMAGE_BYTES = 15 * 1024 * 1024;
export const SUPPORTED_PROFILE_IMAGE_TYPES = ['image/jpeg', 'image/png', 'image/webp'];

export async function getPickedImageSize(asset) {
  if (!asset?.uri) return 0;
  try {
    const response = await fetch(asset.uri);
    const blob = await response.blob();
    if (Number.isFinite(blob?.size) && blob.size > 0) return blob.size;
  } catch {
    // Algunos proveedores de Android solo exponen el tamaño del selector.
  }
  return Number(asset.fileSize) || 0;
}

export async function validatePickedProfileImage(asset) {
  const type = String(asset?.mimeType || '').toLowerCase();
  if (type && !SUPPORTED_PROFILE_IMAGE_TYPES.includes(type)) {
    return { valid: false, title: 'Formato no compatible', message: 'Selecciona una fotografía JPEG, PNG o WebP.' };
  }
  const size = await getPickedImageSize(asset);
  if (size > MAX_PROFILE_IMAGE_BYTES) {
    return { valid: false, title: 'Imagen demasiado grande', message: `La imagen procesada pesa ${(size / 1024 / 1024).toFixed(1)} MB. El máximo permitido es 15 MB.` };
  }
  return { valid: true, asset: { ...asset, fileSize: size || asset?.fileSize } };
}
