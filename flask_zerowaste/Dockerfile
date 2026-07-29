FROM python:3.12-slim

RUN apt-get update && apt-get install -y curl \
    && curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app

COPY requirements.txt .
RUN pip install --no-cache-dir torch --index-url https://download.pytorch.org/whl/cpu && \
    pip install --no-cache-dir -r requirements.txt

COPY frontend_build/package*.json ./frontend_build/
RUN cd frontend_build && npm install && chmod -R +x node_modules/.bin/

COPY . .

# Compilar Tailwind CSS
RUN cd frontend_build && npx tailwindcss -i ../static/src/input.css -o ../static/css/output.css --minify

EXPOSE 5000

CMD ["python", "app.py"]