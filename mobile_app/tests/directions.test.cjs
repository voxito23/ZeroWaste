const assert = require('node:assert/strict');
const path = require('node:path');
const Module = require('node:module');
const babel = require('@babel/core');

const directionsPath = path.join(__dirname, '..', 'utils', 'directions.js');
const transformed = babel.transformFileSync(directionsPath, {
  babelrc: false,
  configFile: false,
  plugins: [require.resolve('@babel/plugin-transform-modules-commonjs')],
}).code;
const directionsModule = new Module(directionsPath, module);
directionsModule.filename = directionsPath;
directionsModule.paths = Module._nodeModulePaths(path.dirname(directionsPath));
directionsModule.require = (request) => request === './mapbox'
  ? { MAPBOX_PUBLIC_TOKEN: 'pk.contract-test' }
  : Module.prototype.require.call(directionsModule, request);
directionsModule._compile(transformed, directionsPath);

const { fetchMapboxRoute } = directionsModule.exports;

(async () => {
  let requestedUrl = '';
  global.fetch = async (url) => {
    requestedUrl = url;
    const walking = url.includes('/walking/');
    return {
      ok: true,
      json: async () => ({ routes: [{
        distance: 24200,
        duration: walking ? 20820 : 1560,
        geometry: { type: 'LineString', coordinates: [[-100.4, 20.5], [-100.3, 20.6]] },
        legs: [{ steps: [{
          maneuver: { instruction: 'Continúa al norte.' },
          bannerInstructions: [{ primary: { text: 'Continúa' } }],
          voiceInstructions: [{ announcement: 'En 200 metros, continúa.' }],
        }] }],
      }] }),
    };
  };
  const route = await fetchMapboxRoute([-100.4, 20.5], [-100.3, 20.6], 'driving');
  assert.equal(route.distanceMeters, 24200);
  assert.equal(route.durationSeconds, 1560);
  assert.equal(route.profile, 'driving');
  assert.match(requestedUrl, /\/driving\//);
  assert.match(requestedUrl, /banner_instructions=true/);
  assert.match(requestedUrl, /voice_instructions=true/);
  assert.match(requestedUrl, /voice_units=metric/);
  assert.match(requestedUrl, /language=es/);
  assert.match(requestedUrl, /alternatives=true/);
  assert.equal(route.steps[0].voiceInstructions[0].announcement, 'En 200 metros, continúa.');
  assert.equal(Math.round(route.durationSeconds / 60), 26);
  const walkingRoute = await fetchMapboxRoute([-100.4, 20.5], [-100.3, 20.6], 'walking');
  assert.equal(Math.round(walkingRoute.durationSeconds / 60), 347);

  await assert.rejects(
    fetchMapboxRoute([0, 0], [-100.3, 20.6], 'walking'),
    /coordenadas/,
  );

  global.fetch = (_url, { signal }) => new Promise((_resolve, reject) => {
    signal.addEventListener('abort', () => reject(Object.assign(new Error('aborted'), { name: 'AbortError' })));
  });
  const controller = new AbortController();
  const pending = fetchMapboxRoute([-100.4, 20.5], [-100.3, 20.6], 'cycling', { signal: controller.signal });
  controller.abort();
  await assert.rejects(pending, (error) => error.name === 'AbortError');

  console.log('directions.test.cjs: perfiles, unidades, coordenadas y cancelación correctos');
})().catch((error) => {
  console.error(error);
  process.exitCode = 1;
});
