const ENTITIES = { amp: '&', apos: "'", gt: '>', lt: '<', nbsp: ' ', quot: '"' };

const decodeEntity = (_, name, decimal, hex) => {
  if (name) return ENTITIES[name.toLowerCase()] ?? `&${name};`;
  const value = Number.parseInt(decimal || hex, hex ? 16 : 10);
  return Number.isFinite(value) && value > 0 && value <= 0x10ffff ? String.fromCodePoint(value) : '';
};

export const htmlToPlainText = (value) => {
  if (typeof value !== 'string') return '';
  return value.replace(/<(script|style)[^>]*>[\s\S]*?<\/\1>/gi, '').replace(/<br\s*\/?>/gi, '\n')
    .replace(/<\/p\s*>|<\/div\s*>|<\/li\s*>/gi, '\n').replace(/<li[^>]*>/gi, '• ')
    .replace(/<[^>]+>/g, '').replace(/&([a-z]+);|&#(\d+);|&#x([0-9a-f]+);/gi, decodeEntity)
    .replace(/\r/g, '').replace(/[ \t]+\n/g, '\n').replace(/\n{3,}/g, '\n\n').trim();
};
