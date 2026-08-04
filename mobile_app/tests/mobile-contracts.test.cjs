const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const Module = require('node:module');
const babel = require('@babel/core');

const root = path.join(__dirname, '..');
const read = (relative) => fs.readFileSync(path.join(root, relative), 'utf8');

const loadModule = (relative) => {
  const filename = path.join(root, relative);
  const transformed = babel.transformFileSync(filename, {
    babelrc: false,
    configFile: false,
    plugins: [require.resolve('@babel/plugin-transform-modules-commonjs')],
  }).code;
  const instance = new Module(filename, module);
  instance.filename = filename;
  instance.paths = Module._nodeModulePaths(path.dirname(filename));
  instance._compile(transformed, filename);
  return instance.exports;
};

const appearance = loadModule('services/mapAppearance.js');
const coordinates = loadModule('utils/coordinates.js');
const profileValidation = loadModule('utils/profileValidation.js');
assert.equal(profileValidation.PROFILE_TITLE_OPTIONS.length, 10);
assert.equal(profileValidation.validateProfile({ name: 'Ana', location: 'Querétaro, Qro.', profileTitle: 'Promotor de Reciclaje', bio: 'Eco' }).field, 'name');
assert.equal(profileValidation.validateProfile({ name: 'Víctor Rodríguez', location: 'Qro', profileTitle: 'Promotor de Reciclaje', bio: 'Eco' }).field, 'location');
assert.equal(profileValidation.validateProfile({ name: 'Víctor Rodríguez', location: 'Querétaro, Qro.', profileTitle: '', bio: 'Eco' }).field, 'profileTitle');
assert.equal(profileValidation.validateProfile({ name: 'Víctor Rodríguez', location: 'Querétaro, Qro.', profileTitle: 'Promotor de Reciclaje', bio: 'Eco' }), null);
assert.equal(coordinates.isValidCoordinate(null), false);
assert.equal(coordinates.isValidCoordinate(undefined), false);
assert.equal(coordinates.isValidCoordinate([]), false);
assert.equal(coordinates.isValidCoordinate('[-100, 20]'), false);
assert.equal(coordinates.isValidCoordinate([0, 0]), false);
assert.equal(coordinates.isValidCoordinate([-100.3899, 20.5888]), true);
assert.equal(appearance.MAP_TIME_ZONE, 'America/Mexico_City');
assert.equal(appearance.getAutomaticLightPreset(new Date('2026-08-02T12:00:00Z')), 'dawn');
assert.equal(appearance.getAutomaticLightPreset(new Date('2026-08-02T15:00:00Z')), 'day');
assert.equal(appearance.getAutomaticLightPreset(new Date('2026-08-03T00:00:00Z')), 'dusk');
assert.equal(appearance.getAutomaticLightPreset(new Date('2026-08-03T02:00:00Z')), 'night');

process.env.EXPO_PUBLIC_MOBILE_LINKS_READY = 'false';
const links = loadModule('navigation/linking.js');
assert.equal(links.mobileShareUrl('posts', 42), 'zerowaste://posts/42');
assert.equal(links.mobileShareUrl('news', 'reciclaje-qro'), 'zerowaste://news/reciclaje-qro');
assert.throws(() => links.mobileShareUrl('posts', '../secret'));
assert.deepEqual(links.deepLinkTarget('zerowaste://posts/42'), { name: 'PostDetail', params: { id: '42' } });
assert.deepEqual(links.deepLinkTarget('https://www.zerowaste-qro.com/app/news/reciclaje-qro'), { name: 'NewsDetail', params: { articleId: 'reciclaje-qro' } });
assert.equal(links.deepLinkTarget('zerowaste://auth/google?code=redacted'), null);
assert.equal(links.deepLinkTarget('https://example.com/app/posts/42'), null);

const comments = read('components/forum/CommentsModal.js');
const notifications = read('services/mobileNotifications.js');
const navigator = read('navigation/AppNavigator.js');
const articles = read('screens/ArticleDetailScreen.js');
const forum = read('screens/ForumScreen.js');
const postDetail = read('screens/PostScreen.js');
const notificationsScreen = read('screens/NotificationsScreen.js');
const editorial = read('data/editorialContent.js');
const editProfile = read('screens/EditProfileScreen.js');

assert.match(comments, /parent_comment_id/);
assert.match(comments, /KeyboardAvoidingView/);
assert.match(comments, /highlightCommentId/);
assert.match(notifications, /getExpoPushTokenAsync/);
assert.match(notifications, /expoPushToken/);
assert.match(navigator, /getLastNotificationResponseAsync/);
assert.match(navigator, /addNotificationResponseReceivedListener/);
assert.match(navigator, /addPushTokenListener/);
assert.match(articles, /mobileShareUrl/);
assert.doesNotMatch(forum, /\/foro\/\$\{post\.id\}/);
assert.match(postDetail, /<CommentsModal/);
assert.doesNotMatch(postDetail, /api\.get\(`\/foro\/posts\/\$\{postId\}\/respuestas`\)/);
assert.doesNotMatch(postDetail, /<FlatList/);
assert.match(notificationsScreen, /\/usuarios\/me\/notificaciones/);
assert.match(editorial, /Querétaro fortalece la gestión de sus residuos/);
assert.match(editorial, /references:/);
assert.doesNotMatch(editorial, /Linking\.openURL|WebView/);
assert.doesNotMatch(editorial, /<\/?[a-z][^>]*>/i);
assert.match(editProfile, /PROFILE_TITLE_OPTIONS\.map/);
assert.match(editProfile, /showDialog\(\{ type: 'error'/);
assert.match(editProfile, /maxLength=\{100\}/);

console.log('mobile-contracts.test.cjs: mapa, contenido, enlaces, comentarios y push correctos');
