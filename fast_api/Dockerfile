FROM python:3.12-slim

# Evitar archivos .pyc y habilitar logs sin buffer
ENV PYTHONDONTWRITEBYTECODE=1
ENV PYTHONUNBUFFERED=1

WORKDIR /app

# Instalar dependencias del sistema para psycopg2
RUN apt-get update && \
    apt-get install -y --no-install-recommends gcc libpq-dev && \
    rm -rf /var/lib/apt/lists/*

# Instalar dependencias de Python
COPY requirements.txt .
RUN pip install --no-cache-dir --upgrade pip && \
    pip install --no-cache-dir torch --index-url https://download.pytorch.org/whl/cpu && \
    pip install --no-cache-dir -r requirements.txt

# Copiar el código fuente
COPY . .

EXPOSE 6000

CMD ["uvicorn", "app.main:app", "--host", "0.0.0.0", "--port", "6000", "--proxy-headers", "--forwarded-allow-ips=*"]
