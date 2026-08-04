const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');

const root = path.join(__dirname, '..');
const read = (relative) => fs.readFileSync(path.join(root, relative), 'utf8');

const tabBar = read('components/nav/FloatingTabBar.js');
const scroll = read('context/ScrollContext.js');
const home = read('screens/HomeScreen.js');
const map = read('screens/MapScreen.js');
const navigator = read('navigation/AppNavigator.js');
const mainTabs = read('navigation/MainTabNavigator.js');
const routeNavigation = read('screens/RouteNavigationScreen.js');
const scanner = read('screens/ScannerScreen.js');
const forum = read('screens/ForumScreen.js');
const dialog = read('components/ui/ZeroWasteDialog.js');
const remoteImage = read('components/ui/RemoteImage.js');
const app = read('App.js');
const internalDialogScreens = [
  'screens/LoginScreen.js',
  'screens/RegisterScreen.js',
  'screens/EditProfileScreen.js',
  'screens/MisRecoleccionesScreen.js',
  'screens/MapScreen.js',
  'screens/RewardDetailScreen.js',
].map(read).join('\n');

for (const route of ['home', 'forum', 'scanner', 'map', 'profile']) {
  assert.match(tabBar, new RegExp(`\\b${route}\\b`, 'i'), `incluye acceso ${route}`);
}
assert.match(tabBar, /position:\s*'absolute'/);
assert.match(tabBar, /rounded-full/);
assert.match(tabBar, /getTabBarBottomSpacing\(insets\.bottom\)/);
assert.match(tabBar, /ANDROID_GESTURE_FALLBACK/);
assert.match(tabBar, /paddingBottom:\s*bottomSpacing/);
assert.match(tabBar, /translateY/);
assert.match(tabBar, /opacity/);
assert.match(scroll, /TOP_VISIBILITY_ZONE/);
assert.match(scroll, /DIRECTION_THRESHOLD/);
assert.match(scroll, /keyboardDidShow/);
assert.match(scroll, /keyboardDidHide/);
assert.match(scroll, /reduceMotionChanged/);
assert.match(scroll, /duration:\s*reduceMotionRef\.current \? 0 : motion\.navigation/);
assert.match(home, /navigation\.navigate\('ArticleDetail'/);
assert.doesNotMatch(home, /Linking\.openURL\(item\.url\)/);
assert.match(map, /navigation\.navigate\('PointDetail'/);
assert.match(map, /navigation\.navigate\('RouteNavigation'/);
assert.match(map, /MAP_STYLE_URL/);
assert.match(map, /MAP_FALLBACK_STYLE_URL/);
assert.match(map, /clusterRadius=\{48\}/);
assert.match(map, /MAP_OVERVIEW_CAMERA[\s\S]*pitch:\s*42/);
assert.match(map, /Mapbox\.StyleImport/);
assert.match(map, /show3dBuildings:\s*true/);
assert.match(map, /api\.get\('\/mobile\/config'\)/);
assert.match(map, /MAP_CARDS_BOTTOM_CLEARANCE = 103/);
assert.match(map, /bottomSafeArea \+ MAP_CARDS_BOTTOM_CLEARANCE/);
assert.match(map, /styleURL=\{usingFallbackStyle \? MAP_FALLBACK_STYLE_URL : MAP_STYLE_URL\}/);
assert.doesNotMatch(map, /togglePerspective|threeDimensional/);
assert.match(routeNavigation, /show3dBuildings:\s*true/);
assert.match(routeNavigation, /new globalThis\.Map\(\)/);
assert.doesNotMatch(routeNavigation, /const cacheRef = useRef\(new Map\(\)\)/);
assert.match(navigator, /name="ArticleDetail"/);
assert.match(navigator, /name="NewsDetail"/);
assert.match(navigator, /name="LocationDetail"/);
assert.match(navigator, /name="RouteNavigation"/);
assert.match(navigator, /name="ChangePassword"/);
assert.match(routeNavigation, /Mapbox\.ShapeSource/);
assert.match(routeNavigation, /fetchMapboxRoute/);
assert.doesNotMatch(routeNavigation, /Linking\.openURL|google\.com\/maps|maps:\/\/|geo:/);
assert.doesNotMatch(tabBar, /Acceso restringido|canScan/);
assert.match(scanner, /COLLECTOR_REQUIRED/);
assert.match(mainTabs, /name === 'Scanner'[\s\S]*\? null/);
assert.match(scanner, /\/qr\/confirmar/);
assert.match(scanner, /result\?\.network \? 'Reintentar'/);
assert.match(scanner, /result\?\.status === 'confirm' \|\| result\?\.network \? 'Cancelar'/);
assert.match(forum, /Artículo Destacado/);
assert.match(forum, /numberOfLines=\{3\}/);
assert.match(forum, /numberOfLines=\{4\}/);
assert.match(forum, /setFiltersVisible\(true\)/);
assert.match(forum, /visible=\{filtersVisible\}/);
assert.match(forum, /SlidersHorizontal/);
assert.doesNotMatch(forum, /h-\[340px\]|h-\[220px\]/);
assert.match(forum, /openPost\(post, true\)/);
assert.match(forum, /refreshing=\{refreshing\}/);
assert.match(forum, /Hay una publicación nueva/);
assert.match(forum, /key=\{`post:\$\{post\.id\}`\}/);
assert.match(routeNavigation, /followUserLocation=\{following\}/);
assert.match(routeNavigation, /followPitch=\{threeDimensional \? 60 : 0\}/);
assert.match(routeNavigation, /Mapbox\.StyleImport/);
assert.match(routeNavigation, /lineEmissiveStrength/);
assert.match(routeNavigation, /requestRef\.current\.controller\?\.abort\(\)/);
assert.match(routeNavigation, /voiceNavigation\.repeat/);
assert.match(routeNavigation, /offRouteSamplesRef/);
assert.match(routeNavigation, /\{ id: 'driving-traffic', label: 'Auto' \}/);
assert.doesNotMatch(routeNavigation, /label: 'Tráfico'/);
assert.match(home, />Tendencias</);
assert.match(home, /RefreshControl/);
assert.doesNotMatch(home, /setInterval\(\(\) => setHomeRefreshKey/);
assert.doesNotMatch(home, /h-\[240px\]/);
assert.match(home, /h-\[438px\] w-\[300px\]/);
assert.match(home, /Información próximamente/);
assert.match(home, /campaignListRef\.current\?\.scrollTo/);
assert.match(dialog, /SafeAreaView/);
assert.match(dialog, /navigationBarTranslucent=\{false\}/);
assert.match(dialog, /bg-white/);
assert.match(dialog, /bg-emerald-700/);
assert.match(dialog, /ZeroWasteDialogProvider/);
assert.match(app, /<ZeroWasteDialogProvider>/);
assert.match(app, /initialMetrics=\{initialWindowMetrics\}/);
assert.match(app, /NavigationBar\.setStyle\('dark'\)/);
assert.doesNotMatch(app, /SystemNavigationBackdrop/);
assert.match(app, /SystemNavigationSurface/);
assert.doesNotMatch(internalDialogScreens, /Alert\.alert|\bAlert\b/);
assert.match(remoteImage, /<Skeleton/);

console.log('navigation.test.cjs: navegación nativa y barra flotante correctas');
