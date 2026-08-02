"""Canonical structured editorial content used by the native mobile client."""

from __future__ import annotations

from typing import Literal

from fastapi import APIRouter, HTTPException, status
from pydantic import BaseModel


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


class ArticleDetail(ArticleSummary):
    author: str | None = None
    blocks: list[ArticleBlock]


ARTICLES: tuple[ArticleDetail, ...] = (
    ArticleDetail(
        id="reciclar-plastico",
        category="Guía sostenible",
        title="Reciclar plástico: 10 consejos para reducir",
        excerpt="Pequeños cambios diarios que reducen la presión de los plásticos de un solo uso.",
        published_at="2026-03-15",
        read_time="5 min",
        image_url=f"{PUBLIC_STATIC}/plasticos.png",
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
    ),
    ArticleDetail(
        id="ahorro-agua",
        category="Guía sostenible",
        title="Ahorro de agua: nuevas técnicas para el futuro",
        excerpt="Métodos domésticos para reducir el consumo, captar lluvia y reutilizar agua gris.",
        published_at="2026-03-16",
        read_time="8 min",
        image_url=f"{PUBLIC_STATIC}/aguah.png",
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
    ),
    ArticleDetail(
        id="energia-solar",
        category="Guía sostenible",
        title="Energía solar: fuentes limpias para renovar",
        excerpt="Autonomía fotovoltaica y microhábitos para consumir menos electricidad fósil.",
        published_at="2026-03-17",
        read_time="6 min",
        image_url=f"{PUBLIC_STATIC}/solar.png",
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
    ),
    ArticleDetail(
        id="compostaje-urbano",
        category="Guía sostenible",
        title="Compostaje urbano: nutrientes vivos para circular",
        excerpt="Convierte residuos orgánicos en suelo fértil incluso dentro de un entorno urbano.",
        published_at="2026-03-18",
        read_time="7 min",
        image_url=f"{PUBLIC_STATIC}/composta.png",
        blocks=[
            ArticleBlock(type="paragraph", text="Cerca del 45% de los residuos de hogares mexicanos son orgánicos. En vertederos anaeróbicos producen metano; separarlos permite convertir un problema en suelo fértil (SEMARNAT, 2023)."),
            ArticleBlock(type="section", heading="Abraza a las lombrices", text="Una compostera doméstica puede reducir de forma importante el volumen enviado al basurero. La base es equilibrar materiales ricos en nitrógeno y carbono y excluir productos que atraigan fauna nociva."),
            ArticleBlock(type="section", heading="Reglas de la biomasa doméstica", text="Para prevenir moscas y malos olores:", items=[
                "Conserva temporalmente en frío las cáscaras y restos vegetales antes de llevarlos a la compostera.",
                "Excluye carne, vísceras, lácteos y productos cárnicos.",
                "Oxigena y controla periódicamente la humedad de la mezcla.",
                "Usa lombriz roja californiana solo con las condiciones adecuadas para vermicomposta.",
            ]),
            ArticleBlock(type="paragraph", text="El abono resultante enriquece jardines y huertos y ayuda a cerrar el ciclo de los residuos alimentarios urbanos."),
        ],
    ),
    ArticleDetail(
        id="queretaro-recicla",
        category="Noticia local",
        title="Querétaro recicla 2.4 kg per cápita al día",
        excerpt="La separación y la infraestructura regional impulsan un modelo de economía circular.",
        published_at="2024-01-08",
        read_time="5 min",
        image_url=f"{PUBLIC_STATIC}/qrocapita.jpg",
        blocks=[
            ArticleBlock(type="paragraph", text="Querétaro ha incrementado el porcentaje de reciclaje hasta llegar al 30% de los 2.4 kilos per cápita generados diariamente, de acuerdo con el informe ambiental citado por la publicación original (SEDESU, 2023)."),
            ArticleBlock(type="section", heading="Un modelo de economía circular", text="Las plantas de separación permiten reincorporar materiales como PET, aluminio y cartón a la cadena productiva. La participación ciudadana y la separación desde el hogar son piezas centrales del proceso."),
            ArticleBlock(type="section", heading="Impacto en la comunidad", text="Además de reducir los residuos enviados a vertederos, la industria local de reciclaje genera empleos y evita parte de las emisiones asociadas con producir materiales vírgenes.", items=[
                "Reducción de residuos en vertederos.",
                "Ahorro energético en la producción de materiales.",
                "Fortalecimiento de una cultura de responsabilidad ambiental.",
            ]),
            ArticleBlock(type="paragraph", text="El siguiente reto es ampliar la infraestructura de recolección, la educación ambiental y el monitoreo inteligente para sostener los avances de la región."),
        ],
    ),
)

ARTICLE_BY_ID = {article.id: article for article in ARTICLES}


@router.get("", response_model=list[ArticleSummary], summary="Listar contenido editorial móvil")
def list_articles():
    return [ArticleSummary(**article.model_dump()) for article in ARTICLES if article.category != "Noticia local"]


@router.get("/{article_id}", response_model=ArticleDetail, summary="Obtener artículo estructurado")
def get_article(article_id: str):
    article = ARTICLE_BY_ID.get(article_id)
    if not article or article.category == "Noticia local":
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Artículo no encontrado.")
    return article
