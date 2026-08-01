const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const source = fs.readFileSync(
  path.join(__dirname, '..', '..', 'flask_zerowaste', 'static', 'js', 'mapbox-map.js'),
  'utf8',
);
const context = { window: {} };
vm.createContext(context);
vm.runInContext(source, context);

const config = context.window.ZeroWasteMapbox;
assert.equal(config.validPublicToken('pk.valid-token'), true);
assert.equal(config.validPublicToken(' pk.valid-token'), false);
assert.equal(config.validPublicToken('YOUR_MAPBOX_TOKEN_HERE'), false);

const normalized = config.normalizePoints([
  { id: 1, latitud: '20.5', longitud: '-100.4', activo: true },
  { id: 1, latitud: 21, longitud: -100 },
  { id: 2, latitud: 'not-a-number', longitud: -100 },
  { id: 3, latitud: 20, longitud: -100, activo: false },
]);
assert.equal(normalized.length, 1);
assert.equal(normalized[0].latitud, 20.5);
assert.equal(normalized[0].longitud, -100.4);

console.log('mapbox-web.test.cjs: configuración y coordenadas correctas');
