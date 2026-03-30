import re
import pandas as pd
# Variable global vacía al inicio (Instancia diferida)
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
    de sentimiento transformadas en porcentajes para el Frontend/Laravel.
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
        "total": total
    }
