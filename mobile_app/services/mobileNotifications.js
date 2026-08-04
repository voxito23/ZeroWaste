const integerId = (value) => {
  const normalized = String(value ?? '');
  return /^\d+$/.test(normalized) ? normalized : null;
};

const safeSlug = (value) => {
  const normalized = String(value ?? '');
  return /^[a-z0-9][a-z0-9-]{0,119}$/i.test(normalized) ? normalized : null;
};

export const notificationTarget = (data = {}) => {
  const type = String(data.type || '');
  if (['post_comment', 'comment_reply', 'post_like'].includes(type)) {
    const id = integerId(data.postId || data.entityId);
    if (!id) return null;
    return { name: 'PostDetail', params: { id, focusComments: Boolean(data.openComments), highlightCommentId: integerId(data.highlightCommentId) } };
  }
  if (type === 'article_published') {
    const articleId = safeSlug(data.entityId);
    return articleId ? { name: 'ArticleDetail', params: { articleId } } : null;
  }
  if (type === 'news_published') {
    const articleId = safeSlug(data.entityId);
    return articleId ? { name: 'NewsDetail', params: { articleId } } : null;
  }
  if (type === 'collection_created') {
    const collectionId = integerId(data.entityId);
    const latitude = Number(data.latitude ?? data.latitud);
    const longitude = Number(data.longitude ?? data.longitud);
    const requesterName = String(data.requesterName ?? data.requester_name ?? '').trim().slice(0, 100);
    if (collectionId && Number.isFinite(latitude) && latitude >= -90 && latitude <= 90 && Number.isFinite(longitude) && longitude >= -180 && longitude <= 180) {
      return {
        name: 'RouteNavigation',
        params: {
          point: {
            id: `collection-${collectionId}`,
            nombre: requesterName ? `Recolección de ${requesterName}` : `Recolección ${collectionId}`,
            direccion: String(data.address || data.direccion || 'Domicilio de recolección').slice(0, 500),
            materiales: String(data.materials || data.materiales || '').slice(0, 500),
            cantidad_estimada: String(data.quantity || data.cantidad_estimada || '').slice(0, 100),
            scheduled_at: data.scheduledAt || data.scheduled_at || null,
            folio: String(data.folio || '').slice(0, 30),
            solicitante: requesterName,
            requester_avatar_url: String(data.requesterAvatarUrl || data.requester_avatar_url || '').slice(0, 500),
            tipo: 'Recolección a domicilio',
            latitud: latitude,
            longitud: longitude,
          },
        },
      };
    }
  }
  if (type.startsWith('collection_')) {
    const collectionId = integerId(data.entityId);
    return { name: 'MisRecolecciones', params: collectionId ? { collectionId } : undefined };
  }
  if (type === 'reward_status') return { name: 'MyRedemptions' };
  if (type === 'points_earned') return { name: 'PointsHistory' };
  return null;
};
