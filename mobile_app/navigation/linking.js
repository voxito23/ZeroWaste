export const MOBILE_PUBLIC_ORIGIN = 'https://www.zerowaste-qro.com/app';
export const MOBILE_LINKS_READY = process.env.EXPO_PUBLIC_MOBILE_LINKS_READY === 'true';

export const mobileShareUrl = (type, id) => {
  const safeType = ['posts', 'articles', 'news', 'points'].includes(type) ? type : null;
  const safeId = String(id ?? '');
  if (!safeType || !/^[A-Za-z0-9-]+$/.test(safeId)) throw new Error('No se puede compartir este contenido.');
  return MOBILE_LINKS_READY
    ? `${MOBILE_PUBLIC_ORIGIN}/${safeType}/${encodeURIComponent(safeId)}`
    : `zerowaste://${safeType}/${encodeURIComponent(safeId)}`;
};

export const linking = {
  prefixes: ['zerowaste://', `${MOBILE_PUBLIC_ORIGIN}/`],
  config: {
    screens: {
      PostDetail: 'posts/:id',
      ArticleDetail: 'articles/:articleId',
      NewsDetail: 'news/:articleId',
      PointDetail: 'points/:id',
      MisRecolecciones: 'collections/:collectionId?',
    },
  },
};

const integerId = (value) => /^\d+$/.test(String(value || '')) ? String(value) : null;
const safeSlug = (value) => /^[A-Za-z0-9][A-Za-z0-9-]{0,119}$/.test(String(value || '')) ? String(value) : null;

export const deepLinkTarget = (rawUrl) => {
  if (typeof rawUrl !== 'string' || rawUrl.length > 500) return null;
  let parsed;
  try { parsed = new URL(rawUrl); } catch { return null; }
  if (parsed.username || parsed.password) return null;
  let segments;
  if (parsed.protocol === 'zerowaste:') {
    segments = [parsed.hostname, ...parsed.pathname.split('/').filter(Boolean)];
  } else if (parsed.protocol === 'https:' && parsed.hostname === 'www.zerowaste-qro.com') {
    const path = parsed.pathname.split('/').filter(Boolean);
    if (path[0] !== 'app') return null;
    segments = path.slice(1);
  } else {
    return null;
  }
  const [kind, rawId] = segments;
  if (kind === 'auth') return null;
  if (segments.length !== 2) return { name: 'ContentUnavailable' };
  if (kind === 'posts') return integerId(rawId) ? { name: 'PostDetail', params: { id: integerId(rawId) } } : { name: 'ContentUnavailable' };
  if (kind === 'points') return integerId(rawId) ? { name: 'PointDetail', params: { id: integerId(rawId) } } : { name: 'ContentUnavailable' };
  if (kind === 'articles') return safeSlug(rawId) ? { name: 'ArticleDetail', params: { articleId: safeSlug(rawId) } } : { name: 'ContentUnavailable' };
  if (kind === 'news') return safeSlug(rawId) ? { name: 'NewsDetail', params: { articleId: safeSlug(rawId) } } : { name: 'ContentUnavailable' };
  return { name: 'ContentUnavailable' };
};
