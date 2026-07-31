FROM node:20.19.1-alpine AS assets

WORKDIR /app
COPY frontend_build/package.json frontend_build/package-lock.json ./frontend_build/
RUN npm --prefix frontend_build ci

COPY frontend_build/tailwind.config.js ./frontend_build/tailwind.config.js
COPY templates ./templates
COPY static ./static
RUN npm --prefix frontend_build run build:css


FROM python:3.12.10-slim-bookworm AS python-builder

ARG TORCH_VERSION=2.6.0

ENV PIP_DISABLE_PIP_VERSION_CHECK=1 \
    PIP_NO_CACHE_DIR=1 \
    VIRTUAL_ENV=/opt/venv

RUN python -m venv "$VIRTUAL_ENV"
ENV PATH="$VIRTUAL_ENV/bin:$PATH"

WORKDIR /build
COPY requirements.txt ./requirements.txt
RUN pip install "torch==${TORCH_VERSION}" --index-url https://download.pytorch.org/whl/cpu \
    && pip install -r requirements.txt


FROM python:3.12.10-slim-bookworm AS runtime

ENV PYTHONDONTWRITEBYTECODE=1 \
    PYTHONUNBUFFERED=1 \
    PIP_DISABLE_PIP_VERSION_CHECK=1 \
    VIRTUAL_ENV=/opt/venv \
    PATH="/opt/venv/bin:$PATH" \
    HOME=/home/zerowaste

RUN apt-get update \
    && apt-get install -y --no-install-recommends ca-certificates libgomp1 libpq5 \
    && rm -rf /var/lib/apt/lists/* \
    && groupadd --gid 10001 zerowaste \
    && useradd --uid 10001 --gid 10001 --create-home --home-dir /home/zerowaste --shell /usr/sbin/nologin zerowaste

COPY --from=python-builder /opt/venv /opt/venv

WORKDIR /app
COPY --chown=10001:10001 . .
COPY --from=assets --chown=10001:10001 /app/static/css/output.css /app/static/css/output.css

USER 10001:10001

EXPOSE 5000

CMD ["gunicorn", "--bind", "0.0.0.0:5000", "--workers", "2", "--timeout", "60", "--graceful-timeout", "30", "--access-logfile", "-", "--error-logfile", "-", "app:app"]
