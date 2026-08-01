const PUBLIC_ORIGIN = 'https://www.zerowaste-qro.com';

const MEDIA_COLLECTIONS = new Set(['foro', 'perfiles', 'recompensas', 'campanas', 'eventos', 'puntos']);

const LEGACY_PATHS = [
  [/^api\/foro\/posts\/imagenes\//i, 'media/foro/'],
  [/^api\/foro\/perfiles\//i, 'media/perfiles/'],
  [/^static\/img\/posts\//i, 'media/foro/'],
  [/^static\/img\/perfiles\//i, 'media/perfiles/'],
  [/^static\/img\/recompensas\//i, 'media/recompensas/'],
  [/^static\/img\/campanas\//i, 'media/campanas/'],
  [/^static\/img\/eventos\//i, 'media/eventos/'],
  [/^static\/img\/puntos\//i, 'media/puntos/'],
  [/^images\/recompensas\//i, 'media/recompensas/'],
  [/^img\/perfiles\//i, 'media/perfiles/'],
];

const INTERNAL_PATH = /^(?:app|data|var\/www|usr|etc|opt|srv|tmp)(?:\/|$)/i;
const UNSAFE_SCHEME = /^(?:javascript|file|data|blob|ftp):/i;

const cleanValue = (value) => (typeof value === 'string' ? value.trim().replace(/\\/g, '/') : '');

const isPrivateIpv4 = (hostname) => {
  if (!/^\d{1,3}(?:\.\d{1,3}){3}$/.test(hostname)) return false;
  const parts = hostname.split('.').map(Number);
  if (parts.some((part) => part < 0 || part > 255)) return true;
  const [a, b] = parts;
  return (
    a === 0 || a === 10 || a === 127 || a >= 224 ||
    (a === 100 && b >= 64 && b <= 127) ||
    (a === 169 && b === 254) ||
    (a === 172 && b >= 16 && b <= 31) ||
    (a === 192 && b === 168) ||
    (a === 198 && (b === 18 || b === 19))
  );
};

const isInternalHostname = (value) => {
  const hostname = String(value || '').toLowerCase().replace(/^\[|\]$/g, '').replace(/\.$/, '');
  if (!hostname) return true;
  if (
    hostname === 'localhost' || hostname.endsWith('.localhost') ||
    hostname.endsWith('.local') || hostname.endsWith('.internal') || hostname.endsWith('.lan') ||
    hostname === '::1' || hostname.startsWith('fc') || hostname.startsWith('fd') || hostname.startsWith('fe80:') ||
    hostname.startsWith('::ffff:') || (!hostname.includes('.') && !hostname.includes(':'))
  ) return true;
  return isPrivateIpv4(hostname);
};

const hasUnsafePath = (path) => {
  if (!path || path.includes('\0') || /^[a-z]:\//i.test(path)) return true;
  let decoded = path;
  try {
    decoded = decodeURIComponent(path);
  } catch {
    return true;
  }
  return decoded.split('/').some((segment) => segment === '..' || segment === '.');
};

const splitSuffix = (value) => {
  const index = value.search(/[?#]/);
  return index === -1 ? [value, ''] : [value.slice(0, index), value.slice(index)];
};

const canonicalMediaPath = (value, collection = '') => {
  const requestedCollection = MEDIA_COLLECTIONS.has(collection) ? collection : '';
  const [pathValue, suffix] = splitSuffix(cleanValue(value));
  let path = pathValue.replace(/^\/+/, '').replace(/\/{2,}/g, '/');
  if (!path || hasUnsafePath(path) || INTERNAL_PATH.test(path)) return null;

  const sharedEventPath = /^static\/img\/eventos\//i;
  if (sharedEventPath.test(path) && ['campanas', 'eventos'].includes(requestedCollection)) {
    path = path.replace(sharedEventPath, `media/${requestedCollection}/`);
  }

  for (const [pattern, replacement] of LEGACY_PATHS) {
    if (pattern.test(path)) {
      path = path.replace(pattern, replacement);
      break;
    }
  }

  path = path.replace(/^(?:media\/)+/i, 'media/');
  const firstSegment = path.split('/')[0].toLowerCase();
  if (MEDIA_COLLECTIONS.has(firstSegment)) path = `media/${path}`;

  if (!/^media\//i.test(path)) {
    if (!requestedCollection) return null;
    const filename = path.split('/').pop();
    if (!filename) return null;
    path = `media/${requestedCollection}/${filename}`;
  }

  const segments = path.split('/');
  if (segments.length < 3 || !MEDIA_COLLECTIONS.has(segments[1].toLowerCase()) || !segments.slice(2).join('/')) {
    return null;
  }
  segments[0] = 'media';
  segments[1] = segments[1].toLowerCase();
  return `/${segments.join('/')}${suffix}`;
};

/**
 * Converts persisted media metadata to a public HTTPS URL.
 *
 * `collection` is used only for legacy values that contain a filename instead
 * of a canonical `media/<collection>/...` path.
 */
export function normalizeMediaUrl(value, collection = '') {
  const raw = cleanValue(value);
  if (!raw || UNSAFE_SCHEME.test(raw)) return null;

  if (/^https?:\/\//i.test(raw)) {
    let parsed;
    try {
      parsed = new URL(raw);
    } catch {
      return null;
    }
    if (isInternalHostname(parsed.hostname) || parsed.username || parsed.password) return null;

    const isZeroWasteHost = ['zerowaste-qro.com', 'www.zerowaste-qro.com'].includes(parsed.hostname.toLowerCase());
    if (parsed.protocol !== 'https:' && !isZeroWasteHost) return null;
    if (parsed.protocol !== 'https:' && parsed.protocol !== 'http:') return null;

    if (isZeroWasteHost) {
      const publicPath = parsed.pathname.replace(/^\/+/, '');
      if (INTERNAL_PATH.test(publicPath) || hasUnsafePath(publicPath)) return null;
      const canonicalPath = canonicalMediaPath(`${parsed.pathname}${parsed.search}${parsed.hash}`, collection);
      if (canonicalPath) return `${PUBLIC_ORIGIN}${canonicalPath}`;
      if (parsed.protocol === 'http:') return null;
      return `${PUBLIC_ORIGIN}${parsed.pathname}${parsed.search}${parsed.hash}`;
    }

    return parsed.protocol === 'https:' ? parsed.toString() : null;
  }

  const canonicalPath = canonicalMediaPath(raw, collection);
  return canonicalPath ? `${PUBLIC_ORIGIN}${canonicalPath}` : null;
}
