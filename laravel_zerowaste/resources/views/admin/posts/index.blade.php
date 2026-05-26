@extends('layouts.admin')

@section('title', 'Foro - Administrar Posts')
@section('page_title', 'Foro')

@php
    function getCategoryColorClass($categoryName) {
        $name = mb_strtolower(trim($categoryName));
        if (str_contains($name, 'reciclaje')) return 'bg-amber-100 text-amber-800 border-amber-300 dark:bg-amber-900/30 dark:text-amber-400 dark:border-amber-800/50';
        if (str_contains($name, 'compostaje')) return 'bg-emerald-100 text-emerald-800 border-emerald-300 dark:bg-emerald-900/30 dark:text-emerald-400 dark:border-emerald-800/50';
        if (str_contains($name, 'reducci') || str_contains($name, 'residuos')) return 'bg-cyan-100 text-cyan-800 border-cyan-300 dark:bg-cyan-900/30 dark:text-cyan-400 dark:border-cyan-800/50';
        if (str_contains($name, 'eventos')) return 'bg-violet-100 text-violet-800 border-violet-300 dark:bg-violet-900/30 dark:text-violet-400 dark:border-violet-800/50';
        if (str_contains($name, 'preguntas') || str_contains($name, 'dudas')) return 'bg-rose-100 text-rose-800 border-rose-300 dark:bg-rose-900/30 dark:text-rose-400 dark:border-rose-800/50';
        if (str_contains($name, 'tips')) return 'bg-pink-100 text-pink-800 border-pink-200 dark:bg-pink-900/30 dark:text-pink-400 dark:border-pink-800/50';
        
        return 'bg-gray-100 text-gray-800 border-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700';
    }

    function getImageUrl($imagePath) {
        if (!$imagePath) return null;
        if (str_starts_with($imagePath, 'http')) return $imagePath;
        return '/static/img/posts/' . $imagePath; // Asumiendo ruta local
    }
@endphp

@section('content')
<div class="mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
    <div>
        <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">Administración del Foro</h1>
        <p class="text-gray-500 dark:text-gray-400 text-sm mt-1">Revisa y gestiona los posts publicados por la comunidad.</p>
    </div>
</div>

<div class="glass-card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="premium-table">
            <thead>
                <tr>
                    <th style="width: 40%">Post</th>
                    <th>Autor</th>
                    <th>Categoría</th>
                    <th>Fecha</th>
                    <th class="text-right">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($posts as $post)
                    @php 
                        $catName = $post->categoria->nombre ?? 'Sin categoría';
                        $catClass = getCategoryColorClass($catName);
                    @endphp
                    <tr>
                        <td>
                            <div class="flex items-center gap-3">
                                @if($post->imagen)
                                    <div class="w-12 h-12 rounded-lg bg-gray-100 dark:bg-gray-800 flex-shrink-0 overflow-hidden border border-gray-200 dark:border-gray-700">
                                        <img src="{{ getImageUrl($post->imagen) }}" alt="Img" class="w-full h-full object-cover">
                                    </div>
                                @else
                                    <div class="w-12 h-12 rounded-lg bg-emerald-50 dark:bg-emerald-900/20 flex items-center justify-center flex-shrink-0 border border-emerald-100 dark:border-emerald-800/30 text-emerald-500">
                                        <span class="material-symbols-outlined">article</span>
                                    </div>
                                @endif
                                <div>
                                    <h3 class="text-sm font-bold text-gray-900 dark:text-white line-clamp-1" title="{{ $post->titulo }}">{{ $post->titulo }}</h3>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 line-clamp-1 mt-0.5">{{ strip_tags($post->contenido) }}</p>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="flex items-center gap-2">
                                <div class="w-6 h-6 rounded-full bg-gray-200 dark:bg-gray-700 overflow-hidden flex-shrink-0 border border-gray-300 dark:border-gray-600">
                                    <img src="{{ $post->autor && $post->autor->foto_perfil ? '/static/img/perfiles/' . $post->autor->foto_perfil : '/static/img/perfiles/default.png' }}" 
                                         alt="Avatar" class="w-full h-full object-cover" onerror="this.src='/static/img/perfiles/default.png'">
                                </div>
                                <span class="text-sm font-semibold text-gray-800 dark:text-gray-200">{{ $post->autor->nombre ?? 'Usuario Eliminado' }}</span>
                            </div>
                        </td>
                        <td>
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider border {{ $catClass }}">
                                {{ $catName }}
                            </span>
                        </td>
                        <td>
                            <div class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                {{ $post->created_at ? $post->created_at->format('d M, Y') : 'N/A' }}
                            </div>
                            <div class="text-xs text-gray-400 dark:text-gray-500">
                                {{ $post->created_at ? $post->created_at->format('H:i') : '' }}
                            </div>
                        </td>
                        <td class="text-right">
                            <div class="flex items-center justify-end gap-1">
                                <button type="button" 
                                    data-title="{{ $post->titulo }}"
                                    data-author="{{ $post->autor->nombre ?? 'Desconocido' }}"
                                    data-category="{{ $catName }}"
                                    data-catclass="{{ $catClass }}"
                                    data-date="{{ $post->created_at ? $post->created_at->format('d/m/Y H:i') : '' }}"
                                    data-image="{{ getImageUrl($post->imagen) }}"
                                    data-author-image="{{ $post->autor && $post->autor->foto_perfil ? '/static/img/perfiles/' . $post->autor->foto_perfil : '/static/img/perfiles/default.png' }}"
                                    data-comments="{{ base64_encode(json_encode($post->respuestas)) }}"
                                    onclick="viewPostDetail(this)"
                                    class="p-2 text-blue-500 hover:bg-blue-50 dark:hover:bg-blue-500/10 rounded-xl transition-colors" title="Ver Detalles">
                                    <span class="material-symbols-outlined text-[20px]">visibility</span>
                                    <span class="hidden data-content">{{ base64_encode($post->contenido) }}</span>
                                </button>
                                
                                <form id="delete-post-{{ $post->id }}" action="{{ route('posts.destroy', $post->id) }}" method="POST" class="inline-block">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" onclick="confirmarEliminarPost('delete-post-{{ $post->id }}')" class="p-2 text-red-500 hover:bg-red-50 dark:hover:bg-red-500/10 rounded-xl transition-colors" title="Eliminar">
                                        <span class="material-symbols-outlined text-[20px]">delete</span>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="py-12 text-center">
                            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-emerald-50 dark:bg-emerald-900/20 text-emerald-500 mb-3">
                                <span class="material-symbols-outlined text-3xl">forum</span>
                            </div>
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white">Aún no hay posts</h3>
                            <p class="text-gray-500 dark:text-gray-400 text-sm mt-1">El foro de la comunidad está vacío.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    @if($posts->hasPages())
        <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-700/50">
            {{ $posts->links() }}
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
function viewPostDetail(btn) {
    const title = btn.dataset.title;
    const author = btn.dataset.author;
    const category = btn.dataset.category;
    const catClass = btn.dataset.catclass;
    const date = btn.dataset.date;
    const image = btn.dataset.image;
    const authorImage = btn.dataset.authorImage;
    const contentBase64 = btn.querySelector('.data-content').textContent;
    
    // Decodificar Base64 a UTF-8 string
    let safeContent = '';
    try {
        safeContent = decodeURIComponent(escape(window.atob(contentBase64)));
    } catch(e) {
        safeContent = window.atob(contentBase64);
    }
    
    const isDark = document.documentElement.classList.contains('dark');
    
    let imageHtml = '';
    if (image && image !== 'null' && image !== '') {
        imageHtml = `
            <div class="mb-5 rounded-xl overflow-hidden border border-gray-200 dark:border-gray-700 max-h-64 flex items-center justify-center bg-black/5 dark:bg-black/20">
                <img src="${image}" alt="Imagen del post" class="max-w-full max-h-64 object-contain">
            </div>
        `;
    }

    const commentsBase64 = btn.dataset.comments;
    let comments = [];
    try {
        if (commentsBase64) {
            comments = JSON.parse(decodeURIComponent(escape(window.atob(commentsBase64))));
        }
    } catch(e) {
        console.error("Error parsing comments", e);
    }
    
    // Generar HTML para comentarios
    let commentsHtml = '';
    if (comments.length > 0) {
        commentsHtml += '<div class="mt-6 border-t border-gray-100 dark:border-gray-800 pt-4"><h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2"><span class="material-symbols-outlined text-[18px]">forum</span> Comentarios (' + comments.length + ')</h3><div class="space-y-3 max-h-64 overflow-y-auto pr-2" style="scrollbar-width: thin;">';
        
        comments.forEach(c => {
            const cAuthor = c.autor ? c.autor.nombre : 'Usuario';
            const cAuthorImg = c.autor && c.autor.foto_perfil ? '/static/img/perfiles/' + c.autor.foto_perfil : '/static/img/perfiles/default.png';
            const cDate = new Date(c.created_at).toLocaleString('es-MX', {day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit'});
            
            commentsHtml += `
                <div class="bg-gray-50 dark:bg-gray-800/50 rounded-xl p-3 border border-gray-100 dark:border-gray-700 relative group">
                    <div class="flex items-start gap-3">
                        <img src="${cAuthorImg}" alt="Avatar" class="w-8 h-8 rounded-full object-cover border border-gray-200 dark:border-gray-700 shrink-0" onerror="this.src='/static/img/perfiles/default.png'">
                        <div class="flex-1 min-w-0 pr-8">
                            <div class="flex items-center justify-between">
                                <p class="text-sm font-bold text-gray-900 dark:text-white truncate">${cAuthor}</p>
                                <span class="text-[10px] text-gray-400">${cDate}</span>
                            </div>
                            <p class="text-xs text-gray-700 dark:text-gray-300 mt-1 whitespace-pre-wrap">${c.contenido}</p>
                        </div>
                    </div>
                    <button onclick="deleteRespuesta(${c.id})" class="absolute top-2 right-2 opacity-100 md:opacity-0 md:group-hover:opacity-100 transition-opacity p-1.5 bg-white dark:bg-gray-900 text-red-500 hover:bg-red-50 dark:hover:bg-red-500/10 rounded-lg border border-gray-200 dark:border-gray-700" title="Eliminar Comentario">
                        <span class="material-symbols-outlined text-[14px]">delete</span>
                    </button>
                </div>
            `;
        });
        
        commentsHtml += '</div></div>';
    } else {
        commentsHtml += '<div class="mt-6 border-t border-gray-100 dark:border-gray-800 pt-4"><h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2 flex items-center gap-2"><span class="material-symbols-outlined text-[18px]">forum</span> Comentarios (0)</h3><p class="text-sm text-gray-500 dark:text-gray-400 italic">No hay comentarios en este post.</p></div>';
    }

    Swal.fire({
        html: `
            <div class="text-left">
                <!-- Encabezado Modal -->
                <div class="flex items-start justify-between mb-4 pr-8">
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider border ${catClass}">
                        ${category}
                    </span>
                    <span class="text-xs font-semibold text-gray-400 flex items-center gap-1">
                        <span class="material-symbols-outlined text-[14px]">schedule</span> ${date}
                    </span>
                </div>
                
                <h2 class="text-2xl font-black text-gray-900 dark:text-white mb-4 leading-tight">${title}</h2>
                
                <!-- Autor -->
                <div class="flex items-center gap-3 mb-6 p-3 rounded-xl bg-gray-50 dark:bg-gray-800/50 border border-gray-100 dark:border-gray-700">
                    <div class="w-10 h-10 rounded-full bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center overflow-hidden border border-emerald-200 dark:border-emerald-800/50 shrink-0">
                        <img src="${authorImage}" alt="Avatar" class="w-full h-full object-cover" onerror="this.src='/static/img/perfiles/default.png'">
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-0.5">Publicado por</p>
                        <p class="text-sm font-bold text-gray-800 dark:text-gray-200 leading-none">${author}</p>
                    </div>
                </div>

                ${imageHtml}
                
                <!-- Contenido -->
                <div class="text-gray-700 dark:text-gray-300 text-sm leading-relaxed whitespace-pre-wrap bg-white dark:bg-[#0B1F18] p-4 rounded-xl border border-gray-100 dark:border-gray-800 max-h-64 overflow-y-auto" style="scrollbar-width: thin;">
                    ${safeContent}
                </div>
                
                ${commentsHtml}
            </div>
        `,
        showConfirmButton: false,
        showCloseButton: true,
        background: isDark ? '#0F2A20' : '#ffffff',
        width: 650,
        customClass: {
            popup: 'rounded-[1.5rem] border border-gray-100 dark:border-emerald-900/30 shadow-2xl',
            closeButton: 'text-gray-500 hover:text-red-500 focus:outline-none'
        }
    });
}

window.deleteRespuesta = function(id) {
    const isDark = document.documentElement.classList.contains('dark');
    Swal.fire({
        html: `<div class="text-center">
            <div class="w-16 h-16 rounded-full mx-auto mb-4 flex items-center justify-center bg-red-50 dark:bg-red-500/10 border border-red-100 dark:border-red-900/50">
                <span class="material-symbols-outlined text-red-500 text-3xl">delete</span>
            </div>
            <h3 class="text-xl font-black text-gray-900 dark:text-white mb-2">¿Eliminar comentario?</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">Esta acción no se puede deshacer.</p>
        </div>`,
        showCancelButton: true,
        confirmButtonColor: '#EF4444',
        cancelButtonColor: 'transparent',
        confirmButtonText: '<span class="font-bold flex items-center gap-1"><span class="material-symbols-outlined text-sm">delete</span>Eliminar</span>',
        cancelButtonText: '<span class="font-bold" style="color: ' + (isDark ? '#D1FAE5' : '#1F2937') + ';">Cancelar</span>',
        background: isDark ? '#0F2A20' : '#ffffff',
        width: 380,
        customClass: {
            popup: 'rounded-[1.5rem] border border-gray-100 dark:border-emerald-900/30 shadow-2xl',
            confirmButton: 'rounded-full px-5 py-2.5',
            cancelButton: 'rounded-full px-5 py-2.5 border border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-gray-800'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('/zw-interno/respuestas/' + id, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            })
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    Swal.close();
                    location.reload();
                } else {
                    Swal.fire('Error', 'Ocurrió un error al eliminar el comentario.', 'error');
                }
            })
            .catch(err => {
                console.error(err);
                Swal.fire('Error', 'Error de red al eliminar el comentario.', 'error');
            });
        }
    });
}

window.confirmarEliminarPost = function(formId) {
    const isDark = document.documentElement.classList.contains('dark');
    Swal.fire({
        html: `<div class="text-center">
            <div class="w-16 h-16 rounded-full mx-auto mb-4 flex items-center justify-center bg-red-50 dark:bg-red-500/10 border border-red-100 dark:border-red-900/50">
                <span class="material-symbols-outlined text-red-500 text-3xl">delete_forever</span>
            </div>
            <h3 class="text-xl font-black text-gray-900 dark:text-white mb-2">¿Eliminar post?</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">Esta acción eliminará el post y todos sus comentarios permanentemente.</p>
        </div>`,
        showCancelButton: true,
        confirmButtonColor: '#EF4444',
        cancelButtonColor: 'transparent',
        confirmButtonText: '<span class="font-bold flex items-center gap-1"><span class="material-symbols-outlined text-sm">delete</span>Eliminar</span>',
        cancelButtonText: '<span class="font-bold" style="color: ' + (isDark ? '#D1FAE5' : '#1F2937') + ';">Cancelar</span>',
        background: isDark ? '#0F2A20' : '#ffffff',
        width: 380,
        customClass: {
            popup: 'rounded-[1.5rem] border border-gray-100 dark:border-emerald-900/30 shadow-2xl',
            confirmButton: 'rounded-full px-5 py-2.5',
            cancelButton: 'rounded-full px-5 py-2.5 border border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-gray-800'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById(formId).submit();
        }
    });
}
</script>
@endpush
