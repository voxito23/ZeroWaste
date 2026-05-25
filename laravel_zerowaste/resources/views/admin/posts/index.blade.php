@extends('layouts.admin')

@section('title', 'Foro - Administrar Posts')
@section('page_title', 'Foro')

@php
    function getCategoryColorClass($categoryName) {
        $name = strtolower(trim($categoryName));
        if (str_contains($name, 'compostaje')) return 'bg-amber-100 text-amber-800 border-amber-200 dark:bg-amber-900/30 dark:text-amber-400 dark:border-amber-800/50';
        if (str_contains($name, 'reciclaje')) return 'bg-blue-100 text-blue-800 border-blue-200 dark:bg-blue-900/30 dark:text-blue-400 dark:border-blue-800/50';
        if (str_contains($name, 'reducción') || str_contains($name, 'residuos')) return 'bg-emerald-100 text-emerald-800 border-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-400 dark:border-emerald-800/50';
        if (str_contains($name, 'eventos')) return 'bg-purple-100 text-purple-800 border-purple-200 dark:bg-purple-900/30 dark:text-purple-400 dark:border-purple-800/50';
        if (str_contains($name, 'tips')) return 'bg-pink-100 text-pink-800 border-pink-200 dark:bg-pink-900/30 dark:text-pink-400 dark:border-pink-800/50';
        if (str_contains($name, 'preguntas') || str_contains($name, 'dudas')) return 'bg-indigo-100 text-indigo-800 border-indigo-200 dark:bg-indigo-900/30 dark:text-indigo-400 dark:border-indigo-800/50';
        
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
                                    onclick="viewPostDetail(
                                        `{{ htmlspecialchars($post->titulo, ENT_QUOTES) }}`,
                                        `{{ htmlspecialchars($post->autor->nombre ?? 'Desconocido', ENT_QUOTES) }}`,
                                        `{{ htmlspecialchars($catName, ENT_QUOTES) }}`,
                                        `{{ $catClass }}`,
                                        `{{ $post->created_at ? $post->created_at->format('d/m/Y H:i') : '' }}`,
                                        `{{ htmlspecialchars(nl2br(e($post->contenido)), ENT_QUOTES) }}`,
                                        `{{ getImageUrl($post->imagen) }}`
                                    )"
                                    class="p-2 text-blue-500 hover:bg-blue-50 dark:hover:bg-blue-500/10 rounded-xl transition-colors" title="Ver Detalles">
                                    <span class="material-symbols-outlined text-[20px]">visibility</span>
                                </button>
                                
                                <form action="{{ route('posts.destroy', $post->id) }}" method="POST" class="inline-block" onsubmit="return confirm('¿Eliminar permanentemente este post y sus comentarios?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-2 text-red-500 hover:bg-red-50 dark:hover:bg-red-500/10 rounded-xl transition-colors" title="Eliminar">
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
function viewPostDetail(title, author, category, catClass, date, content, image) {
    const isDark = document.documentElement.classList.contains('dark');
    
    // Decodificar el HTML que enviamos codificado para evitar errores JS
    const decodeHTML = function(html) {
        var txt = document.createElement("textarea");
        txt.innerHTML = html;
        return txt.value;
    };
    
    const safeTitle = decodeHTML(title);
    const safeContent = decodeHTML(content);
    
    let imageHtml = '';
    if (image && image !== 'null' && image !== '') {
        imageHtml = `
            <div class="mb-5 rounded-xl overflow-hidden border border-gray-200 dark:border-gray-700 max-h-64 flex items-center justify-center bg-black/5 dark:bg-black/20">
                <img src="${image}" alt="Imagen del post" class="max-w-full max-h-64 object-contain">
            </div>
        `;
    }

    Swal.fire({
        html: `
            <div class="text-left">
                <!-- Encabezado Modal -->
                <div class="flex items-start justify-between mb-4">
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider border ${catClass}">
                        ${category}
                    </span>
                    <span class="text-xs font-semibold text-gray-400 flex items-center gap-1">
                        <span class="material-symbols-outlined text-[14px]">schedule</span> ${date}
                    </span>
                </div>
                
                <h2 class="text-2xl font-black text-gray-900 dark:text-white mb-4 leading-tight">${safeTitle}</h2>
                
                <!-- Autor -->
                <div class="flex items-center gap-3 mb-6 p-3 rounded-xl bg-gray-50 dark:bg-gray-800/50 border border-gray-100 dark:border-gray-700">
                    <div class="w-10 h-10 rounded-full bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center text-emerald-600 dark:text-emerald-400 shrink-0">
                        <span class="material-symbols-outlined">person</span>
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
</script>
@endpush
