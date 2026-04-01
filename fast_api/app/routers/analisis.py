from fastapi import APIRouter, Depends
from sqlalchemy.orm import Session
import re
import pandas as pd

from app.data.database import get_db
from app.models.domain_models import Foro, CalificacionPunto

router = APIRouter(prefix="/analisis", tags=["IA Análisis"])

analyzer = None

def get_analyzer():
    global analyzer
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
