"""Native editorial-content contracts without database or network access."""

from __future__ import annotations

import importlib.util
import sys
import types
import unittest
from pathlib import Path

from pydantic import ValidationError


ROOT = Path(__file__).resolve().parents[1]


def load_articles():
    path = ROOT / "fast_api/app/routers/articles.py"
    fast_api_root = str(ROOT / "fast_api")
    if fast_api_root not in sys.path:
        sys.path.insert(0, fast_api_root)
    spec = importlib.util.spec_from_file_location("zerowaste_articles", path)
    if spec is None or spec.loader is None:
        raise RuntimeError("Unable to load articles router")
    module = importlib.util.module_from_spec(spec)
    mocked_modules = {
        "app.models": types.ModuleType("app.models"),
        "app.models.domain_models": types.ModuleType("app.models.domain_models"),
        "app.security": types.ModuleType("app.security"),
        "app.security.jwt_auth": types.ModuleType("app.security.jwt_auth"),
        "app.services": types.ModuleType("app.services"),
        "app.services.content_reactions": types.ModuleType("app.services.content_reactions"),
    }
    mocked_modules["app.models"].__path__ = []
    mocked_modules["app.security"].__path__ = []
    mocked_modules["app.services"].__path__ = []

    class DummyUsuario:
        id = 1

    class DummyReactions:
        def state(self, *_args):
            return 0, False

    mocked_modules["app.models.domain_models"].Usuario = DummyUsuario
    mocked_modules["app.security.jwt_auth"].get_current_user = lambda: None
    mocked_modules["app.security.jwt_auth"].get_optional_current_user = lambda: None
    mocked_modules["app.services.content_reactions"].ContentReactions = DummyReactions
    mocked_modules["app.services.content_reactions"].get_content_reactions = DummyReactions
    previous = {name: sys.modules.get(name) for name in mocked_modules}
    sys.modules.update(mocked_modules)
    try:
        spec.loader.exec_module(module)
    finally:
        for name, original in previous.items():
            if original is None:
                sys.modules.pop(name, None)
            else:
                sys.modules[name] = original
    return module


class ArticleContractsTests(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        cls.module = load_articles()

    def test_editorial_catalog_is_structured_and_complete(self):
        articles = self.module.ARTICLES
        self.assertEqual(5, len(articles))
        self.assertEqual(len(articles), len({article.id for article in articles}))
        for article in articles:
            self.assertTrue(article.title)
            self.assertTrue(article.excerpt)
            self.assertTrue(article.image_url.startswith("https://www.zerowaste-qro.com/static/img/"))
            self.assertTrue(article.blocks)
            self.assertTrue(article.references)
            for block in article.blocks:
                values = [block.heading or "", block.text or "", *(block.items or [])]
                self.assertFalse(any("<" in value or ">" in value for value in values))

    def test_list_has_summaries_and_unknown_detail_is_404(self):
        summaries = self.module.list_articles()
        self.assertEqual(4, len(summaries))
        self.assertNotIn("queretaro-recicla", {item.id for item in summaries})
        self.assertFalse(hasattr(summaries[0], "blocks"))
        with self.assertRaises(self.module.HTTPException) as raised:
            self.module.get_article("does-not-exist")
        self.assertEqual(404, raised.exception.status_code)

    def test_mobile_uses_native_article_route(self):
        home = (ROOT / "mobile_app/screens/HomeScreen.js").read_text(encoding="utf-8")
        navigator = (ROOT / "mobile_app/navigation/AppNavigator.js").read_text(encoding="utf-8")
        detail = (ROOT / "mobile_app/screens/ArticleDetailScreen.js").read_text(encoding="utf-8")
        main = (ROOT / "fast_api/app/main.py").read_text(encoding="utf-8")
        self.assertIn("app.include_router(articles.router)", main)
        self.assertIn("api.get('/articles'", home)
        self.assertIn("navigation.navigate('ArticleDetail'", home)
        self.assertNotIn("Linking.openURL(item.url)", home)
        self.assertIn('name="ArticleDetail"', navigator)
        self.assertIn("isNews ? 'news' : 'articles'", detail)
        self.assertNotIn("WebView", detail)

    def test_news_has_an_independent_native_contract(self):
        news = (ROOT / "fast_api/app/routers/news.py").read_text(encoding="utf-8")
        home = (ROOT / "mobile_app/screens/HomeScreen.js").read_text(encoding="utf-8")
        navigator = (ROOT / "mobile_app/navigation/AppNavigator.js").read_text(encoding="utf-8")
        self.assertIn('prefix="/news"', news)
        self.assertIn("api.get('/news'", home)
        self.assertIn("navigation.navigate('NewsDetail'", home)
        self.assertIn('name="NewsDetail"', navigator)
        self.assertNotIn("Querétaro recicla{' '}", home)

    def test_articles_and_news_have_internal_apa_references_and_hearts(self):
        articles = (ROOT / "fast_api/app/routers/articles.py").read_text(encoding="utf-8")
        news = (ROOT / "fast_api/app/routers/news.py").read_text(encoding="utf-8")
        detail = (ROOT / "mobile_app/screens/ArticleDetailScreen.js").read_text(encoding="utf-8")
        self.assertIn('@router.put("/{article_id}/like"', articles)
        self.assertIn('@router.delete("/{article_id}/like"', articles)
        self.assertIn('@router.put("/{slug}/like"', news)
        self.assertIn('@router.delete("/{slug}/like"', news)
        self.assertIn("article.references.map", detail)
        self.assertNotIn("Linking.openURL(reference", detail)

    def test_article_blocks_reject_unknown_types(self):
        with self.assertRaises(ValidationError):
            self.module.ArticleBlock(type="html", text="contenido no permitido")


if __name__ == "__main__":
    unittest.main()
