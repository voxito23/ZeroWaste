"""Shared reactions for static editorial content.

Redis keeps the counter consistent across FastAPI replicas without requiring a
schema change for the curated, file-backed article catalog.
"""

from __future__ import annotations

import hashlib
import os

from fastapi import HTTPException, status
from redis import Redis
from redis.exceptions import RedisError


class ContentReactions:
    def __init__(self, client: Redis | None = None):
        redis_url = os.getenv("REDIS_URL")
        if client is None and not redis_url:
            raise RuntimeError("Required environment variable is not configured: REDIS_URL")
        self.redis = client or Redis.from_url(
            redis_url,
            decode_responses=True,
            socket_connect_timeout=2,
            socket_timeout=2,
        )

    @staticmethod
    def _key(content_type: str, content_id: str) -> str:
        return f"zw:content:{content_type}:{content_id}:likes"

    @staticmethod
    def _member(user_id: int) -> str:
        return hashlib.sha256(f"user:{user_id}".encode("utf-8")).hexdigest()

    def state(self, content_type: str, content_id: str, user_id: int | None) -> tuple[int, bool]:
        key = self._key(content_type, content_id)
        try:
            pipe = self.redis.pipeline(transaction=False)
            pipe.scard(key)
            if user_id is not None:
                pipe.sismember(key, self._member(user_id))
            values = pipe.execute()
            return int(values[0]), bool(values[1]) if user_id is not None else False
        except RedisError:
            # Editorial content must remain readable during a transient Redis
            # incident; only its social counters are temporarily omitted.
            return 0, False

    def set_like(self, content_type: str, content_id: str, user_id: int, liked: bool) -> tuple[int, bool]:
        key = self._key(content_type, content_id)
        member = self._member(user_id)
        try:
            pipe = self.redis.pipeline(transaction=True)
            if liked:
                pipe.sadd(key, member)
            else:
                pipe.srem(key, member)
            pipe.scard(key)
            values = pipe.execute()
            return int(values[-1]), liked
        except RedisError as exc:
            raise HTTPException(
                status_code=status.HTTP_503_SERVICE_UNAVAILABLE,
                detail="No fue posible actualizar el corazón en este momento.",
            ) from exc


_reactions: ContentReactions | None = None


def get_content_reactions() -> ContentReactions:
    global _reactions
    if _reactions is None:
        _reactions = ContentReactions()
    return _reactions
