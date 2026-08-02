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

for (const route of ['home', 'forum', 'scanner', 'map', 'profile']) {
  assert.match(tabBar, new RegExp(`\\b${route}\\b`, 'i'), `incluye acceso ${route}`);
}
assert.match(tabBar, /position:\s*'absolute'/);
assert.match(tabBar, /rounded-full/);
assert.match(tabBar, /Math\.max\(insets\.bottom, 8\)/);
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
assert.match(map, /onSelected=\{\(\) => navigation\.navigate\('LocationDetail'/);
assert.match(map, /openPointDirections\(p\)/);
assert.match(map, /event\.stopPropagation\(\)/);
assert.match(navigator, /name="ArticleDetail"/);
assert.match(navigator, /name="LocationDetail"/);

console.log('navigation.test.cjs: navegación nativa y barra flotante correctas');
