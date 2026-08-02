"""Native editorial-content contracts without database or network access."""

from __future__ import annotations

import importlib.util
import unittest
from pathlib import Path

from pydantic import ValidationError


ROOT = Path(__file__).resolve().parents[1]


def load_articles():
    path = ROOT / "fast_api/app/routers/articles.py"
    spec = importlib.util.spec_from_file_location("zerowaste_articles", path)
    if spec is None or spec.loader is None:
        raise RuntimeError("Unable to load articles router")
    module = importlib.util.module_from_spec(spec)
    spec.loader.exec_module(module)
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
            for block in article.blocks:
                values = [block.heading or "", block.text or "", *(block.items or [])]
                self.assertFalse(any("<" in value or ">" in value for value in values))

    def test_list_has_summaries_and_unknown_detail_is_404(self):
        summaries = self.module.list_articles()
        self.assertEqual(5, len(summaries))
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
        self.assertIn("api.get('/articles')", home)
        self.assertIn("navigation.navigate('ArticleDetail'", home)
        self.assertNotIn("Linking.openURL(item.url)", home)
        self.assertIn('name="ArticleDetail"', navigator)
        self.assertIn("api.get(`/articles/${encodeURIComponent(articleId)}`)", detail)
        self.assertNotIn("WebView", detail)

    def test_article_blocks_reject_unknown_types(self):
        with self.assertRaises(ValidationError):
            self.module.ArticleBlock(type="html", text="contenido no permitido")


if __name__ == "__main__":
    unittest.main()
