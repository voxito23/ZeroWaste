FROM python:3.12.10-slim-bookworm AS python-builder

ARG TORCH_VERSION=2.6.0

ENV PIP_DISABLE_PIP_VERSION_CHECK=1 \
    PIP_NO_CACHE_DIR=1 \
    VIRTUAL_ENV=/opt/venv

RUN apt-get update \
    && apt-get install -y --no-install-recommends gcc libpq-dev \
    && rm -rf /var/lib/apt/lists/*

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

USER 10001:10001

EXPOSE 6000

CMD ["uvicorn", "app.main:app", "--host", "0.0.0.0", "--port", "6000", "--proxy-headers", "--forwarded-allow-ips", "127.0.0.1"]
