const PUBLIC_ORIGIN = 'https://www.zerowaste-qro.com';

const cleanPath = (value) => String(value || '').trim().replace(/\\/g, '/');

export function normalizeMediaUrl(value, fallbackPath = '') {
  const raw = cleanPath(value);
  if (!raw) return fallbackPath ? `${PUBLIC_ORIGIN}/${cleanPath(fallbackPath).replace(/^\/+/, '')}` : null;

  if (/^https:\/\//i.test(raw)) return raw;
  if (/^http:\/\//i.test(raw)) return raw.replace(/^http:\/\//i, 'https://');

  return `${PUBLIC_ORIGIN}/${raw.replace(/^\/+/, '')}`;
}

export function forumPostImageUrl(value) {
  const raw = cleanPath(value);
  if (!raw) return null;
  if (/^https?:\/\//i.test(raw) || raw.startsWith('/media/') || raw.startsWith('media/')) {
    return normalizeMediaUrl(raw);
  }
  return `${PUBLIC_ORIGIN}/api/foro/posts/imagenes/${encodeURIComponent(raw.split('/').pop())}`;
}

export function profileImageUrl(value) {
  const raw = cleanPath(value);
  if (!raw || ['perfil_default.png', 'default.png'].includes(raw)) return null;
  if (/^https?:\/\//i.test(raw) || raw.startsWith('/media/') || raw.startsWith('media/')) {
    return normalizeMediaUrl(raw);
  }
  return `${PUBLIC_ORIGIN}/api/foro/perfiles/${encodeURIComponent(raw.split('/').pop())}`;
}
