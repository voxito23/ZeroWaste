"""Native news contract backed by the canonical structured editorial catalog."""

from fastapi import APIRouter, Depends, HTTPException, status

from app.models.domain_models import Usuario
from app.routers.articles import ARTICLES, ARTICLE_BY_ID, ArticleDetail, ArticleSummary, ContentLikeResponse, enrich_article
from app.security.jwt_auth import get_current_user, get_optional_current_user
from app.services.content_reactions import ContentReactions, get_content_reactions


router = APIRouter(prefix="/news", tags=["Noticias"])


@router.get("", response_model=list[ArticleSummary], summary="Listar noticias publicadas")
def list_news(
    current_user: Usuario | None = Depends(get_optional_current_user),
    reactions: ContentReactions = Depends(get_content_reactions),
):
    user_id = getattr(current_user, "id", None)
    return [ArticleSummary(**enrich_article(article, "news", user_id, reactions).model_dump()) for article in ARTICLES if article.category == "Noticia local"]


@router.put("/{slug}/like", response_model=ContentLikeResponse, summary="Dar corazón a una noticia")
def like_news(
    slug: str,
    current_user: Usuario = Depends(get_current_user),
    reactions: ContentReactions = Depends(get_content_reactions),
):
    article = ARTICLE_BY_ID.get(slug)
    if not article or article.category != "Noticia local":
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Noticia no encontrada.")
    likes_count, liked = reactions.set_like("news", slug, current_user.id, True)
    return ContentLikeResponse(liked=liked, likes_count=likes_count)


@router.delete("/{slug}/like", response_model=ContentLikeResponse, summary="Quitar corazón de una noticia")
def unlike_news(
    slug: str,
    current_user: Usuario = Depends(get_current_user),
    reactions: ContentReactions = Depends(get_content_reactions),
):
    article = ARTICLE_BY_ID.get(slug)
    if not article or article.category != "Noticia local":
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Noticia no encontrada.")
    likes_count, liked = reactions.set_like("news", slug, current_user.id, False)
    return ContentLikeResponse(liked=liked, likes_count=likes_count)


@router.get("/{slug}", response_model=ArticleDetail, summary="Obtener noticia estructurada")
def get_news(
    slug: str,
    current_user: Usuario | None = Depends(get_optional_current_user),
    reactions: ContentReactions = Depends(get_content_reactions),
):
    article = ARTICLE_BY_ID.get(slug)
    if not article or article.category != "Noticia local":
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Noticia no encontrada.")
    return enrich_article(article, "news", getattr(current_user, "id", None), reactions)
