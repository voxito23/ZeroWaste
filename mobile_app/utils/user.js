export const resolveAvatar = (value) => {
  const entity = value || {};
  const nested = entity.author || entity.autor || entity.usuario || entity.user || {};
  return entity.avatar_url ?? entity.foto_perfil_url ?? entity.perfil_url ?? entity.foto_perfil
    ?? entity.autor_foto ?? nested.avatar_url ?? nested.foto_perfil_url ?? nested.perfil_url
    ?? nested.foto_perfil ?? nested.imagen ?? null;
};

export const resolveDisplayName = (value) => {
  const entity = value || {};
  const nested = entity.author || entity.autor || entity.usuario || entity.user || {};
  return entity.autor_nombre ?? entity.nombre_usuario ?? entity.nombre ?? nested.nombre ?? nested.name ?? 'Usuario';
};
