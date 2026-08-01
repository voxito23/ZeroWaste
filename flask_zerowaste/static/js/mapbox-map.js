(function (global) {
    'use strict';

    const STYLES = Object.freeze({
        light: 'mapbox://styles/mapbox/streets-v12',
        dark: 'mapbox://styles/mapbox/dark-v11',
    });
    const DEFAULT_CENTER = Object.freeze([-100.3899, 20.5881]);

    function validPublicToken(value) {
        return typeof value === 'string'
            && value.trim() === value
            && value.startsWith('pk.')
            && !value.includes('YOUR_')
            && !/\s/.test(value);
    }

    function normalizePoint(point) {
        if (!point || typeof point !== 'object') return null;
        const latitud = Number(point.latitud);
        const longitud = Number(point.longitud);
        if (!Number.isFinite(latitud) || latitud < -90 || latitud > 90) return null;
        if (!Number.isFinite(longitud) || longitud < -180 || longitud > 180) return null;
        if (point.activo === false) return null;
        return { ...point, latitud, longitud, activo: point.activo !== false };
    }

    function normalizePoints(points) {
        const seen = new Set();
        return (Array.isArray(points) ? points : [])
            .map(normalizePoint)
            .filter((point) => {
                if (!point) return false;
                const key = String(point.id);
                if (seen.has(key)) return false;
                seen.add(key);
                return true;
            });
    }

    function safeErrorMessage(error) {
        const status = Number(error?.status || error?.error?.status || 0);
        if (status === 401 || status === 403) {
            return 'Mapbox rechazó la configuración pública del mapa.';
        }
        return 'No fue posible cargar el mapa. Revisa tu conexión e inténtalo de nuevo.';
    }

    function createMap(options) {
        const mapbox = global.mapboxgl;
        const onError = typeof options?.onError === 'function' ? options.onError : function () {};
        if (!mapbox || typeof mapbox.Map !== 'function') {
            onError('No fue posible iniciar Mapbox en este navegador.');
            return null;
        }
        if (!validPublicToken(options.token)) {
            onError('El token público de Mapbox no está configurado correctamente.');
            return null;
        }
        if (typeof mapbox.supported === 'function' && !mapbox.supported()) {
            onError('Este navegador no tiene WebGL disponible para mostrar el mapa.');
            return null;
        }

        mapbox.accessToken = options.token;
        const map = new mapbox.Map({
            container: options.container,
            style: options.dark ? STYLES.dark : STYLES.light,
            center: options.center || DEFAULT_CENTER,
            zoom: options.zoom ?? 13,
            minZoom: options.minZoom,
            maxBounds: options.maxBounds,
            attributionControl: true,
        });
        let ready = false;
        map.once('load', function () {
            ready = true;
            options.onReady?.(map);
        });
        map.on('error', function (event) {
            if (!ready) onError(safeErrorMessage(event?.error || event));
        });
        return map;
    }

    function setTheme(map, dark) {
        if (map && typeof map.setStyle === 'function') {
            map.setStyle(dark ? STYLES.dark : STYLES.light);
        }
    }

    async function fetchPoints(url) {
        const response = await fetch(url, { headers: { Accept: 'application/json' } });
        const contentType = response.headers.get('content-type') || '';
        if (!response.ok || !contentType.toLowerCase().includes('application/json')) {
            throw new Error(`points-http-${response.status}`);
        }
        return normalizePoints(await response.json());
    }

    global.ZeroWasteMapbox = Object.freeze({
        DEFAULT_CENTER,
        STYLES,
        createMap,
        fetchPoints,
        normalizePoint,
        normalizePoints,
        setTheme,
        validPublicToken,
    });
}(window));
