"""Canonical structured editorial content used by the native mobile client."""

from __future__ import annotations

from typing import Literal

from fastapi import APIRouter, Depends, HTTPException, status
from pydantic import BaseModel, Field

from app.models.domain_models import Usuario
from app.security.jwt_auth import get_current_user, get_optional_current_user
from app.services.content_reactions import ContentReactions, get_content_reactions


router = APIRouter(prefix="/articles", tags=["Artículos"])
PUBLIC_STATIC = "https://www.zerowaste-qro.com/static/img"


class ArticleBlock(BaseModel):
    type: Literal["paragraph", "section"]
    heading: str | None = None
    text: str | None = None
    items: list[str] | None = None


class ArticleSummary(BaseModel):
    id: str
    category: str
    title: str
    excerpt: str
    published_at: str
    read_time: str
    image_url: str
    likes_count: int = 0
    liked_by_me: bool = False


class ArticleDetail(ArticleSummary):
    author: str | None = None
    blocks: list[ArticleBlock]
    references: list[str] = Field(default_factory=list)


class ContentLikeResponse(BaseModel):
    liked: bool
    likes_count: int


ARTICLES: tuple[ArticleDetail, ...] = (
    ArticleDetail(
        id="reciclar-plastico",
        category="Guía sostenible",
        title="Reciclar plástico: 10 consejos para reducir",
        excerpt="Pequeños cambios diarios que reducen la presión de los plásticos de un solo uso.",
        published_at="2026-03-15",
        read_time="5 min",
        image_url=f"{PUBLIC_STATIC}/plasticos-mobile.jpg",
        blocks=[
            ArticleBlock(type="paragraph", text="La contaminación por plástico representa una de las crisis ambientales más severas de nuestro tiempo. A nivel global, más del 90% del plástico manufacturado nunca ha sido reciclado de manera óptima (PNUMA, 2023). Integrar prácticas diarias para reducir su impacto desde el hogar es imperativo."),
            ArticleBlock(type="section", heading="El impacto de lo efímero", text="Los envases de un solo uso constituyen más del 40% del total de residuos plásticos a nivel mundial (Geyer et al., 2017). Sustituirlos por alternativas reutilizables reduce la presión de los microplásticos sobre los ecosistemas."),
            ArticleBlock(type="section", heading="Estrategias cotidianas", text="Para crear un impacto real y sostenible, aplica estos lineamientos al gestionar tu consumo:", items=[
                "Prioriza bolsas de tela orgánica en supermercados.",
                "Compra granos, harinas y cereales a granel.",
                "Evita envases PET cuando exista una alternativa reutilizable en vidrio.",
                "Lleva empaques flexibles limpios y secos a programas que realmente los procesen.",
            ]),
            ArticleBlock(type="paragraph", text="La responsabilidad también consiste en ejercer el poder de consumo y exigir políticas públicas que impulsen una economía circular más vigorosa."),
        ],
        references=[
            "Geyer, R., Jambeck, J. R., & Law, K. L. (2017). Production, use, and fate of all plastics ever made. Science Advances, 3(7), e1700782. https://doi.org/10.1126/sciadv.1700782",
            "Programa de las Naciones Unidas para el Medio Ambiente. (2023). Turning off the tap: How the world can end plastic pollution and create a circular economy.",
        ],
    ),
    ArticleDetail(
        id="ahorro-agua",
        category="Guía sostenible",
        title="Ahorro de agua: nuevas técnicas para el futuro",
        excerpt="Métodos domésticos para reducir el consumo, captar lluvia y reutilizar agua gris.",
        published_at="2026-03-16",
        read_time="8 min",
        image_url=f"{PUBLIC_STATIC}/aguah-mobile.jpg",
        blocks=[
            ArticleBlock(type="paragraph", text="La escasez hídrica ha sobrepasado niveles de alerta en múltiples regiones metropolitanas. Más de 2,000 millones de personas experimentan estrés hídrico domiciliario (OMS, 2022). Minimizar la huella individual dejó de ser opcional."),
            ArticleBlock(type="section", heading="Técnicas inteligentes domésticas", text="El consumo promedio supera los 200 litros diarios por persona en algunas zonas urbanas, aunque las necesidades esenciales son menores. Reparaciones y hábitos en ducha y lavandería producen una reducción inmediata."),
            ArticleBlock(type="section", heading="Tácticas de máxima utilidad", text="Las entidades protectoras de cuencas recomiendan intervenciones de infraestructura y hábitos familiares:", items=[
                "Cosecha agua de lluvia con techos limpios dirigidos a una cisterna adecuada.",
                "Cierra el grifo durante el lavado dental y el enjabonado de la ducha.",
                "Instala aireadores que mantienen la sensación de presión usando menos volumen.",
                "Reutiliza agua gris de la lavadora para descargas sanitarias cuando la instalación lo permita.",
            ]),
            ArticleBlock(type="paragraph", text="La huella de agua virtual de alimentos y ropa también importa: reducir el desperdicio y evitar compras innecesarias disminuye miles de litros indirectos."),
        ],
        references=[
            "Fondo de las Naciones Unidas para la Infancia & Organización Mundial de la Salud. (2023). Progress on household drinking water, sanitation and hygiene 2000–2022: Special focus on gender.",
        ],
    ),
    ArticleDetail(
        id="energia-solar",
        category="Guía sostenible",
        title="Energía solar: fuentes limpias para renovar",
        excerpt="Autonomía fotovoltaica y microhábitos para consumir menos electricidad fósil.",
        published_at="2026-03-17",
        read_time="6 min",
        image_url=f"{PUBLIC_STATIC}/solar-mobile.jpg",
        blocks=[
            ArticleBlock(type="paragraph", text="La dependencia de combustibles fósiles impulsa el calentamiento global. La radiación solar recibida por la Tierra ofrece un potencial energético suficiente si se aprovecha con infraestructura fotovoltaica eficiente (IRENA, 2023)."),
            ArticleBlock(type="section", heading="Autonomía fotovoltaica doméstica", text="Instalar paneles fotovoltaicos es hoy considerablemente más accesible que hace una década. Los sistemas modernos producen energía de forma descentralizada y requieren poco mantenimiento posterior."),
            ArticleBlock(type="section", heading="Microacciones eléctricas", text="Si una instalación completa aún está fuera de alcance, estos hábitos reducen el consumo:", items=[
                "Aprovecha iluminación y calefacción solar pasiva mediante ventanas bien orientadas.",
                "Desconecta adaptadores y equipos en reposo que mantienen consumos fantasma.",
                "Usa iluminación LED eficiente.",
                "Considera cargadores solares portátiles para baterías y dispositivos pequeños.",
            ]),
            ArticleBlock(type="paragraph", text="Consolidar redes autónomas e interconectadas reduce emisiones, costos y vulnerabilidad ante fallas del sistema eléctrico."),
        ],
        references=[
            "International Renewable Energy Agency. (2023). World energy transitions outlook 2023: 1.5°C pathway (Vol. 1).",
        ],
    ),
    ArticleDetail(
        id="compostaje-urbano",
        category="Guía sostenible",
        title="Compostaje urbano: nutrientes vivos para circular",
        excerpt="Convierte residuos orgánicos en suelo fértil incluso dentro de un entorno urbano.",
        published_at="2026-03-18",
        read_time="7 min",
        image_url=f"{PUBLIC_STATIC}/composta-mobile.jpg",
        blocks=[
            ArticleBlock(type="paragraph", text="Una parte importante de los residuos domésticos es orgánica. Cuando se dispone sin oxígeno puede producir metano; separarla permite convertir un problema en suelo fértil (PNUMA, 2023)."),
            ArticleBlock(type="section", heading="Abraza a las lombrices", text="Una compostera doméstica puede reducir de forma importante el volumen enviado al basurero. La base es equilibrar materiales ricos en nitrógeno y carbono y excluir productos que atraigan fauna nociva."),
            ArticleBlock(type="section", heading="Reglas de la biomasa doméstica", text="Para prevenir moscas y malos olores:", items=[
                "Conserva temporalmente en frío las cáscaras y restos vegetales antes de llevarlos a la compostera.",
                "Excluye carne, vísceras, lácteos y productos cárnicos.",
                "Oxigena y controla periódicamente la humedad de la mezcla.",
                "Usa lombriz roja californiana solo con las condiciones adecuadas para vermicomposta.",
            ]),
            ArticleBlock(type="paragraph", text="El abono resultante enriquece jardines y huertos y ayuda a cerrar el ciclo de los residuos alimentarios urbanos."),
        ],
        references=[
            "Programa de las Naciones Unidas para el Medio Ambiente. (2023). Turning off the tap: How the world can end plastic pollution and create a circular economy.",
        ],
    ),
    ArticleDetail(
        id="queretaro-recicla",
        category="Noticia local",
        title="Querétaro fortalece la gestión de sus residuos",
        excerpt="La separación, la recolección municipal y la participación ciudadana sostienen una economía más circular.",
        published_at="2024-01-08",
        read_time="5 min",
        image_url=f"{PUBLIC_STATIC}/qrocapita.jpg",
        blocks=[
            ArticleBlock(type="paragraph", text="Los municipios de Querétaro reportan infraestructura y servicios para la recolección y el manejo de residuos sólidos urbanos. El panorama estatal del INEGI permite consultar estas capacidades sin atribuir cifras no verificadas (INEGI, 2024)."),
            ArticleBlock(type="section", heading="Un modelo de economía circular", text="Las plantas de separación permiten reincorporar materiales como PET, aluminio y cartón a la cadena productiva. La participación ciudadana y la separación desde el hogar son piezas centrales del proceso."),
            ArticleBlock(type="section", heading="Impacto en la comunidad", text="Además de reducir los residuos enviados a vertederos, la industria local de reciclaje genera empleos y evita parte de las emisiones asociadas con producir materiales vírgenes.", items=[
                "Reducción de residuos en vertederos.",
                "Ahorro energético en la producción de materiales.",
                "Fortalecimiento de una cultura de responsabilidad ambiental.",
            ]),
            ArticleBlock(type="paragraph", text="El siguiente reto es ampliar la infraestructura de recolección, la educación ambiental y el monitoreo inteligente para sostener los avances de la región."),
        ],
        references=[
            "Instituto Nacional de Estadística y Geografía. (2024). Panorama de los gobiernos municipales de México 2022: Querétaro.",
        ],
    ),
)

ARTICLE_BY_ID = {article.id: article for article in ARTICLES}


def enrich_article(article: ArticleDetail, content_type: str, user_id: int | None, reactions: ContentReactions) -> ArticleDetail:
    if not isinstance(reactions, ContentReactions):
        return article
    likes_count, liked_by_me = reactions.state(content_type, article.id, user_id)
    return article.model_copy(update={"likes_count": likes_count, "liked_by_me": liked_by_me})


@router.get("", response_model=list[ArticleSummary], summary="Listar contenido editorial móvil")
def list_articles(
    current_user: Usuario | None = Depends(get_optional_current_user),
    reactions: ContentReactions = Depends(get_content_reactions),
):
    user_id = getattr(current_user, "id", None)
    return [ArticleSummary(**enrich_article(article, "article", user_id, reactions).model_dump()) for article in ARTICLES if article.category != "Noticia local"]


@router.put("/{article_id}/like", response_model=ContentLikeResponse, summary="Dar corazón a un artículo")
def like_article(
    article_id: str,
    current_user: Usuario = Depends(get_current_user),
    reactions: ContentReactions = Depends(get_content_reactions),
):
    article = ARTICLE_BY_ID.get(article_id)
    if not article or article.category == "Noticia local":
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Artículo no encontrado.")
    likes_count, liked = reactions.set_like("article", article_id, current_user.id, True)
    return ContentLikeResponse(liked=liked, likes_count=likes_count)


@router.delete("/{article_id}/like", response_model=ContentLikeResponse, summary="Quitar corazón de un artículo")
def unlike_article(
    article_id: str,
    current_user: Usuario = Depends(get_current_user),
    reactions: ContentReactions = Depends(get_content_reactions),
):
    article = ARTICLE_BY_ID.get(article_id)
    if not article or article.category == "Noticia local":
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Artículo no encontrado.")
    likes_count, liked = reactions.set_like("article", article_id, current_user.id, False)
    return ContentLikeResponse(liked=liked, likes_count=likes_count)


@router.get("/{article_id}", response_model=ArticleDetail, summary="Obtener artículo estructurado")
def get_article(
    article_id: str,
    current_user: Usuario | None = Depends(get_optional_current_user),
    reactions: ContentReactions = Depends(get_content_reactions),
):
    article = ARTICLE_BY_ID.get(article_id)
    if not article or article.category == "Noticia local":
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Artículo no encontrado.")
    return enrich_article(article, "article", getattr(current_user, "id", None), reactions)
