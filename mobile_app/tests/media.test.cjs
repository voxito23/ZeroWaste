const assert = require('node:assert/strict');
const path = require('node:path');
const Module = require('node:module');
const babel = require('@babel/core');

const mediaPath = path.join(__dirname, '..', 'utils', 'media.js');
const transformed = babel.transformFileSync(mediaPath, {
  babelrc: false,
  configFile: false,
  plugins: [require.resolve('@babel/plugin-transform-modules-commonjs')],
}).code;
const mediaModule = new Module(mediaPath, module);
mediaModule.filename = mediaPath;
mediaModule.paths = Module._nodeModulePaths(path.dirname(mediaPath));
mediaModule._compile(transformed, mediaPath);

const { normalizeMediaUrl } = mediaModule.exports;
const origin = 'https://www.zerowaste-qro.com';

const accepted = [
  [null, '', null],
  ['', '', null],
  ['media/foro/post.webp', '', `${origin}/media/foro/post.webp`],
  ['/media/perfiles/avatar.jpg', '', `${origin}/media/perfiles/avatar.jpg`],
  ['media/media/foro/post.webp', '', `${origin}/media/foro/post.webp`],
  ['/MEDIA/FORO/imagen.jpg', '', `${origin}/media/foro/imagen.jpg`],
  ['/images/recompensas/termo.png', '', `${origin}/media/recompensas/termo.png`],
  ['/img/perfiles/avatar.jpg', '', `${origin}/media/perfiles/avatar.jpg`],
  ['/static/img/puntos/acopio.webp', '', `${origin}/media/puntos/acopio.webp`],
  ['post.jpg', 'foro', `${origin}/media/foro/post.jpg`],
  ['legacy/path/avatar.png', 'perfiles', `${origin}/media/perfiles/avatar.png`],
  ['https://zerowaste-qro.com/static/img/eventos/evento.jpg', 'eventos', `${origin}/media/eventos/evento.jpg`],
  ['https://www.zerowaste-qro.com/data/media/foro/imagen.jpg', 'foro', null],
  ['http://www.zerowaste-qro.com/api/foro/posts/imagenes/post.jpg', 'foro', `${origin}/media/foro/post.jpg`],
  ['https://cdn.example.com/assets/image.jpg', '', 'https://cdn.example.com/assets/image.jpg'],
];

for (const [value, collection, expected] of accepted) {
  assert.equal(normalizeMediaUrl(value, collection), expected, `normaliza ${String(value)}`);
}

const rejected = [
  'javascript:alert(1)',
  'file:///data/media/foro/post.jpg',
  'data:image/png;base64,AAAA',
  '/data/media/foro/post.jpg',
  '/app/static/img/posts/post.jpg',
  'C:\\media\\post.jpg',
  '../post.jpg',
  'https://localhost/media/foro/post.jpg',
  'https://127.0.0.1/media/foro/post.jpg',
  'https://10.0.0.4/media/foro/post.jpg',
  'https://192.168.1.8/media/foro/post.jpg',
  'http://cdn.example.com/image.jpg',
];

for (const value of rejected) {
  assert.equal(normalizeMediaUrl(value, 'foro'), null, `rechaza ${value}`);
}

console.log(`media.test.cjs: ${accepted.length + rejected.length} casos correctos`);
