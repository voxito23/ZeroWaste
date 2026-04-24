@extends('layouts.admin')

@section('title', 'Mensajes de Contacto')
@section('page_title', 'Mensajes de Contacto')

@section('content')

@push('scripts')
<script>
    let currentMsgId = null;

    function openReplyModal(id, nombre) {
        currentMsgId = id;
        document.getElementById('replyModalTitle').innerText = 'Conversación con ' + nombre;
        
        let urlTemplate = "{{ route('mensajes.responder', ['id' => 'REPLACE_ID']) }}";
        document.getElementById('replyModalForm').action = urlTemplate.replace('REPLACE_ID', id);
        
        document.querySelector('textarea[name="respuesta_admin"]').value = '';
        
        // Load thread via AJAX
        const threadContainer = document.getElementById('replyThread');
        threadContainer.innerHTML = '<div class="text-center py-4 text-gray-400"><span class="material-symbols-outlined animate-spin">progress_activity</span><br>Cargando conversación...</div>';
        
        let threadUrl = "/admin/mensajes/" + id + "/thread";
        fetch(threadUrl)
            .then(r => r.json())
            .then(data => {
                if (!data.success) {
                    threadContainer.innerHTML = '<p class="text-red-500 text-center">Error al cargar.</p>';
                    return;
                }
                let html = '';
                // Original user message
                html += `
                <div class="bg-blue-50 p-4 rounded-xl border border-blue-200 mb-3">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-bold text-blue-700 flex items-center gap-1">
                            <span class="material-symbols-outlined text-sm">person</span> ${data.original.nombre}
                        </span>
                        <span class="text-xs text-gray-400">${data.original.created_at}</span>
                    </div>
                    <p class="text-sm text-gray-700">${data.original.mensaje}</p>
                </div>`;
                
                // Thread replies
                data.replies.forEach(r => {
                    if (r.sender === 'admin') {
                        html += `
                        <div class="bg-emerald-50 p-4 rounded-xl border border-emerald-200 mb-3 ml-4">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-xs font-bold text-emerald-700 flex items-center gap-1">
                                    <span class="material-symbols-outlined text-sm">support_agent</span> Admin
                                </span>
                                <span class="text-xs text-gray-400">${r.created_at}</span>
                            </div>
                            <p class="text-sm text-gray-700">${r.mensaje}</p>
                        </div>`;
                    } else {
                        html += `
                        <div class="bg-blue-50 p-4 rounded-xl border border-blue-200 mb-3">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-xs font-bold text-blue-700 flex items-center gap-1">
                                    <span class="material-symbols-outlined text-sm">person</span> ${data.original.nombre}
                                </span>
                                <span class="text-xs text-gray-400">${r.created_at}</span>
                            </div>
                            <p class="text-sm text-gray-700">${r.mensaje}</p>
                        </div>`;
                    }
                });
                
                threadContainer.innerHTML = html;
                threadContainer.scrollTop = threadContainer.scrollHeight;
            })
            .catch(() => {
                threadContainer.innerHTML = '<p class="text-red-500 text-center">Error de conexión.</p>';
            });
        
        document.getElementById('replyModal').classList.remove('hidden');
    }
    
    function closeReplyModal() {
        document.getElementById('replyModal').classList.add('hidden');
    }
</script>
@endpush



@if($errors->any())
<div class="mb-4 bg-red-50 border-l-4 border-red-500 text-red-800 p-4 rounded-xl font-bold text-sm">
    <ul class="list-disc pl-5">
        @foreach($errors->all() as $err)
            <li>{{ $err }}</li>
        @endforeach
    </ul>
</div>
@endif

<div class="page-header"><h2>Mensajes de Contacto</h2></div>

<div class="glass-card overflow-hidden">
    <table class="premium-table">
        <thead>
            <tr><th>Nombre</th><th>Email</th><th>Ubicación</th><th>Mensaje</th><th>Fecha</th><th>Estado</th><th>Acción</th></tr>
        </thead>
        <tbody>
            @forelse ($mensajes as $msg)
            <tr>
                <td class="font-bold text-sm text-[#064E3B] dark:text-white">{{ $msg->nombre }}</td>
                <td class="text-gray-400 text-xs">{{ $msg->email }}</td>
                <td class="text-gray-400 text-xs">{{ $msg->ubicacion ?? '—' }}</td>
                <td class="text-gray-500 dark:text-gray-400 max-w-[250px] truncate text-xs" title="{{ $msg->mensaje }}">{{ mb_strimwidth($msg->mensaje, 0, 40, '...') }}</td>
                <td class="text-gray-400 text-xs">{{ $msg->created_at ? $msg->created_at->format('d M Y H:i') : '' }}</td>
                <td>
                    @if($msg->estado === 'pendiente')
                        <span class="badge-sm bg-amber-500/10 text-amber-600 dark:text-amber-400">Pendiente</span>
                    @elseif($msg->estado === 'revisado')
                        <span class="badge-sm bg-blue-500/10 text-blue-600 dark:text-blue-400">Revisado</span>
                    @else
                        <span class="badge-sm bg-emerald-500/10 text-emerald-600 dark:text-emerald-400">{{ ucfirst($msg->estado) }}</span>
                    @endif
                </td>
                <td>
                    <button onclick="openReplyModal({{ $msg->id }}, '{{ addslashes($msg->nombre) }}')" class="btn-primary text-xs py-1.5 px-3">
                        <span class="material-symbols-outlined text-[14px]">{{ $msg->estado === 'respondido' ? 'forum' : 'reply' }}</span>
                        {{ $msg->estado === 'respondido' ? 'Ver' : 'Responder' }}
                    </button>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="p-8 text-center text-gray-500 dark:text-gray-400 italic">No hay mensajes de contacto.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- Modal de Conversación --}}
<div id="replyModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm hidden text-left">
    <div class="glass-card p-8 max-w-lg w-full shadow-2xl max-h-[90vh] flex flex-col">
        <div class="flex justify-between items-center mb-4 border-b border-gray-100 dark:border-emerald-800/50 pb-4 shrink-0">
            <h3 class="text-xl font-black text-[#064E3B] dark:text-white flex items-center gap-2" id="replyModalTitle">
                <span class="material-symbols-outlined text-emerald-500">forum</span> Conversación
            </h3>
            <button type="button" onclick="closeReplyModal()" class="w-9 h-9 rounded-xl bg-red-500/10 hover:bg-red-500 text-red-500 hover:text-white flex items-center justify-center transition-all duration-200">
                <span class="material-symbols-outlined text-[18px]">close</span>
            </button>
        </div>
        
        {{-- Thread de conversación --}}
        <div id="replyThread" class="overflow-y-auto mb-4 grow pr-1" style="max-height: 350px;">
            <div class="text-center py-4 text-gray-400 dark:text-gray-500">Cargando...</div>
        </div>
        
        {{-- Formulario de respuesta --}}
        <form id="replyModalForm" action="" method="POST" class="shrink-0 border-t border-gray-100 dark:border-emerald-800/50 pt-4">
            @csrf 
            @method('PUT')
            <div class="mb-4">
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-emerald-400 mb-2">Tu Respuesta (Admin)</label>
                <textarea name="respuesta_admin" required minlength="2" rows="3" class="w-full px-4 py-3 rounded-xl bg-white dark:bg-forest-dark border-2 border-emerald-200 dark:border-emerald-800 dark:text-white focus:ring-2 focus:ring-[#00E096] transition-all resize-none" placeholder="Escribe tu respuesta..."></textarea>
                @error('respuesta_admin')
                    <p class="text-red-500 text-xs font-bold mt-2"><span class="material-symbols-outlined text-[14px] align-middle">error</span> {{ $message }}</p>
                @enderror
            </div>
            <div class="flex justify-end gap-3">
                <button type="button" onclick="closeReplyModal()" class="btn-secondary">Cancelar</button>
                <button type="submit" class="btn-primary">
                    <span class="material-symbols-outlined text-[16px]">send</span> Enviar
                </button>
            </div>
        </form>
    </div>
</div>

@endsection
