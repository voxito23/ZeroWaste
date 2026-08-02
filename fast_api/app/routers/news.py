"""Native news contract backed by the canonical structured editorial catalog."""

from fastapi import APIRouter, HTTPException, status

from app.routers.articles import ARTICLES, ARTICLE_BY_ID, ArticleDetail, ArticleSummary


router = APIRouter(prefix="/news", tags=["Noticias"])


@router.get("", response_model=list[ArticleSummary], summary="Listar noticias publicadas")
def list_news():
    return [ArticleSummary(**article.model_dump()) for article in ARTICLES if article.category == "Noticia local"]


@router.get("/{slug}", response_model=ArticleDetail, summary="Obtener noticia estructurada")
def get_news(slug: str):
    article = ARTICLE_BY_ID.get(slug)
    if not article or article.category != "Noticia local":
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Noticia no encontrada.")
    return article
