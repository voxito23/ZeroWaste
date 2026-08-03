export const PROFILE_TITLE_OPTIONS = Object.freeze([
  { value: 'Usuario Eco-consciente', icon: 'leaf' },
  { value: 'Entusiasta del Desarrollo Sostenible', icon: 'globe' },
  { value: 'Activista Ambiental', icon: 'mountain' },
  { value: 'Ingeniero Ambiental', icon: 'wrench' },
  { value: 'Estudiante Comprometido con el Medio Ambiente', icon: 'graduation' },
  { value: 'Promotor de Reciclaje', icon: 'recycle' },
  { value: 'Educador Ecológico', icon: 'book' },
  { value: 'Voluntario Verde', icon: 'heart' },
  { value: 'Emprendedor Sustentable', icon: 'rocket' },
  { value: 'Líder Comunitario Ecológico', icon: 'users' },
]);

const titleValues = new Set(PROFILE_TITLE_OPTIONS.map((option) => option.value));
const containsMarkup = (value) => /[<>]/.test(value);

export const validateProfile = ({ name, location, profileTitle, bio }) => {
  const cleanName = String(name || '').trim();
  const cleanLocation = String(location || '').trim();
  const cleanTitle = String(profileTitle || '').trim();
  const cleanBio = String(bio || '').trim();

  if (cleanName.length < 10) return { field: 'name', title: 'Nombre demasiado corto', message: 'Escribe tu nombre completo con al menos 10 caracteres.' };
  if (cleanName.length > 50) return { field: 'name', title: 'Nombre demasiado largo', message: 'El nombre completo puede tener como máximo 50 caracteres.' };
  if (containsMarkup(cleanName)) return { field: 'name', title: 'Nombre no válido', message: 'El nombre no puede contener etiquetas o símbolos < >.' };
  if (cleanLocation.length < 10) return { field: 'location', title: 'Ubicación incompleta', message: 'Escribe una ubicación de al menos 10 caracteres, por ejemplo: Querétaro, Qro.' };
  if (cleanLocation.length > 50) return { field: 'location', title: 'Ubicación demasiado larga', message: 'La ubicación puede tener como máximo 50 caracteres.' };
  if (containsMarkup(cleanLocation)) return { field: 'location', title: 'Ubicación no válida', message: 'La ubicación no puede contener etiquetas o símbolos < >.' };
  if (!titleValues.has(cleanTitle)) return { field: 'profileTitle', title: 'Selecciona tu título', message: 'Elige una opción de la lista para mostrarla en tu perfil.' };
  if (cleanBio.length < 1) return { field: 'bio', title: 'Biografía vacía', message: 'Cuéntale a la comunidad algo sobre ti.' };
  if (cleanBio.length > 100) return { field: 'bio', title: 'Biografía demasiado larga', message: 'La biografía puede tener como máximo 100 caracteres.' };
  if (containsMarkup(cleanBio)) return { field: 'bio', title: 'Biografía no válida', message: 'La biografía no puede contener etiquetas o símbolos < >.' };
  return null;
};
