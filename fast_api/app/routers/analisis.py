from fastapi import APIRouter, Depends
from sqlalchemy.orm import Session
import re
import pandas as pd

from app.data.database import get_db
from app.models.domain_models import Foro, CalificacionPunto

router = APIRouter(prefix="/analisis", tags=["IA Análisis"])

import threading
analyzer = None
analyzer_lock = threading.Lock()

def get_analyzer():
    global analyzer
    if analyzer is None:
        with analyzer_lock:
            if analyzer is None:
                try:
                    from pysentimiento import create_analyzer
                    print("Cargando modelo PySentimiento en memoria...")
                    analyzer = create_analyzer(task="sentiment", lang="es")
                except Exception as e:
                    print(f"Error cargando analyzer: {e}")
                    analyzer = None
    return analyzer

def limpiar_texto(texto):
    """Filtro de limpieza básico para NLP."""
    if not isinstance(texto, str):
        return ""
    texto = re.sub(r'http\S+', '', texto) # Remover URLs
    texto = re.sub(r'@\w+', '', texto)    # Remover Menciones
    texto = re.sub(r'[^\w\s.,!?áéíóúÁÉÍÓÚñÑ]', '', texto)
    return texto.strip()

def analizar_sentimiento_comunidad(posts):
    """
    Ingiere un array de posts, los limpia y extrae las métricas globales
    de sentimiento transformadas en porcentajes.
    """
    if not posts:
        return {"POS": 0, "NEG": 0, "NEU": 0, "total": 0}

    df = pd.DataFrame({"texto": posts})
    df["limpio"] = df["texto"].apply(limpiar_texto)
    df = df[df["limpio"].str.len() > 0]

    modelo_ia = get_analyzer()
    if df.empty or not modelo_ia:
        return {"POS": 0, "NEG": 0, "NEU": 0, "total": 0}

    # Inferencia masiva
    preds = df["limpio"].apply(lambda txt: modelo_ia.predict(txt).output)
    conteos = preds.value_counts().to_dict()
    total = sum(conteos.values())

    return {
        "POS": round((conteos.get("POS", 0) / total) * 100, 1),
        "NEG": round((conteos.get("NEG", 0) / total) * 100, 1),
        "NEU": round((conteos.get("NEU", 0) / total) * 100, 1),
        "total": int(total)
    }

@router.get("/sentimiento")
def api_sentimiento(db: Session = Depends(get_db)):
    """Endpoint REST para compartir el análisis a Laravel y Flask."""
    posts_records = db.query(Foro.contenido).filter(Foro.contenido != None).all()
    textos = [p[0] for p in posts_records]
    
    todas_calificaciones = db.query(CalificacionPunto.estrellas, CalificacionPunto.comentario).all()
    for estrellas, comentario in todas_calificaciones:
        if comentario and comentario.strip():
            textos.append(comentario)
        else:
            # Generar texto sintético para NLP basado en las estrellas si no hay reseña
            if estrellas == 5: textos.append("Excelente, muy buen lugar.")
            elif estrellas == 4: textos.append("Buen lugar, recomendado.")
            elif estrellas == 3: textos.append("Normal, aceptable.")
            elif estrellas == 2: textos.append("Puede mejorar mucho.")
            else: textos.append("No recomendado, mala experiencia.")
    
    resultados = analizar_sentimiento_comunidad(textos)
    return {"success": True, "data": resultados}

@router.get("/grafica_usuarios")
def api_grafica_usuarios(db: Session = Depends(get_db)):
    from app.models.domain_models import Usuario
    from fastapi.responses import Response
    import matplotlib
    matplotlib.use('Agg')  # Non-interactive backend
    import matplotlib.pyplot as plt
    import matplotlib.dates as mdates
    import matplotlib.ticker as ticker
    import io
    
    # 1. Fetch data
    usuarios = db.query(Usuario.created_at).all()
    if not usuarios:
        # Return a minimal placeholder image
        fig, ax = plt.subplots(figsize=(7.5, 3.2), facecolor='none')
        ax.text(0.5, 0.5, 'Sin datos de usuarios', transform=ax.transAxes,
                ha='center', va='center', fontsize=14, color='#9ca3af',
                fontfamily='sans-serif')
        ax.set_facecolor('none')
        for spine in ax.spines.values():
            spine.set_visible(False)
        ax.set_xticks([])
        ax.set_yticks([])
        buf = io.BytesIO()
        fig.savefig(buf, format='png', transparent=True, dpi=150, bbox_inches='tight')
        buf.seek(0)
        plt.close(fig)
        return Response(content=buf.getvalue(), media_type="image/png")
        
    df = pd.DataFrame(usuarios, columns=["created_at"])
    df['created_at'] = pd.to_datetime(df['created_at'])
    
    # Group by date
    conteo = df.groupby(df['created_at'].dt.date).size().reset_index(name='total')
    conteo['created_at'] = pd.to_datetime(conteo['created_at'])
    conteo = conteo.sort_values('created_at')
    
    # If only one date, add trailing zero so line renders
    if len(conteo) == 1:
        extra = conteo.iloc[0].copy()
        extra['created_at'] = extra['created_at'] + pd.Timedelta(days=1)
        extra['total'] = 0
        conteo = pd.concat([conteo, pd.DataFrame([extra])], ignore_index=True)
    
    # 2. Professional plot
    fig, ax = plt.subplots(figsize=(7.5, 3.2), facecolor='none')
    ax.set_facecolor('none')
    
    # Laravel palette: coral red
    COLOR_PRIMARY = "#FF2D20"
    COLOR_FILL_TOP = "#FF2D20"
    
    # Main line
    ax.plot(conteo['created_at'], conteo['total'],
            color=COLOR_PRIMARY, linewidth=2.5, solid_capstyle='round',
            marker='o', markersize=7, markerfacecolor='#ffffff',
            markeredgecolor=COLOR_PRIMARY, markeredgewidth=2.2, zorder=5)
    
    # Gradient-like area fill
    ax.fill_between(conteo['created_at'], conteo['total'],
                    color=COLOR_FILL_TOP, alpha=0.10, zorder=2)
    ax.fill_between(conteo['created_at'], conteo['total'],
                    color=COLOR_FILL_TOP, alpha=0.06, zorder=1)
    
    # Grid - subtle
    ax.grid(axis='y', color='#e5e7eb', linestyle='-', linewidth=0.7, alpha=0.8, zorder=0)
    ax.grid(axis='x', color='#f3f4f6', linestyle='--', linewidth=0.5, alpha=0.6, zorder=0)
    
    # Axes formatting
    ax.xaxis.set_major_formatter(mdates.DateFormatter('%d %b'))
    ax.xaxis.set_major_locator(mdates.AutoDateLocator(minticks=3, maxticks=8))
    ax.yaxis.set_major_locator(ticker.MaxNLocator(integer=True))
    ax.set_ylim(bottom=0)
    
    # Tick styling
    ax.tick_params(axis='both', colors='#6b7280', labelsize=9.5, length=0, pad=8)
    for label in ax.get_xticklabels():
        label.set_fontfamily('sans-serif')
        label.set_fontweight('medium')
    for label in ax.get_yticklabels():
        label.set_fontfamily('sans-serif')
        label.set_fontweight('medium')
    
    # Remove all spines for clean look
    for spine in ax.spines.values():
        spine.set_visible(False)
    
    # Add a subtle bottom line
    ax.axhline(y=0, color='#d1d5db', linewidth=0.8, zorder=0)
        
    fig.tight_layout(pad=1.2)
    
    buf = io.BytesIO()
    fig.savefig(buf, format='png', transparent=True, dpi=180, bbox_inches='tight')
    buf.seek(0)
    plt.close(fig)
    
    return Response(content=buf.getvalue(), media_type="image/png")
