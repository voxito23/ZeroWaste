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

@if(session('success'))
<div class="mb-4 bg-emerald-50 border-l-4 border-emerald-400 text-emerald-800 p-4 rounded-xl font-bold text-sm">{{ session('success') }}</div>
@endif

@if($errors->any())
<div class="mb-4 bg-red-50 border-l-4 border-red-500 text-red-800 p-4 rounded-xl font-bold text-sm">
    <ul class="list-disc pl-5">
        @foreach($errors->all() as $err)
            <li>{{ $err }}</li>
        @endforeach
    </ul>
</div>
@endif

<div class="bg-white dark:bg-forest-card rounded-3xl shadow-lg border border-emerald-100 dark:border-emerald-800/50 overflow-hidden">
    <table class="w-full text-left text-sm">
        <thead class="bg-emerald-50 dark:bg-emerald-900/30 text-[#064E3B] dark:text-emerald-200 font-bold text-xs uppercase tracking-wider">
            <tr>
                <th class="p-4">Nombre</th>
                <th class="p-4">Email</th>
                <th class="p-4">Ubicación</th>
                <th class="p-4">Mensaje</th>
                <th class="p-4">Fecha</th>
                <th class="p-4">Estado</th>
                <th class="p-4">Acción</th>
            </tr>
        </thead>
        <tbody class="dark:text-emerald-100">
            @forelse ($mensajes as $msg)
            <tr class="border-b border-emerald-50 dark:border-emerald-800/50 hover:bg-emerald-50/50 dark:hover:bg-emerald-900/20 transition-colors">
                <td class="p-4 font-bold">{{ $msg->nombre }}</td>
                <td class="p-4 text-gray-600 dark:text-gray-400">{{ $msg->email }}</td>
                <td class="p-4 text-gray-500 dark:text-gray-400 text-xs">{{ $msg->ubicacion ?? 'N/A' }}</td>
                <td class="p-4 text-gray-700 dark:text-gray-300 max-w-[300px] truncate" title="{{ $msg->mensaje }}">{{ mb_strimwidth($msg->mensaje, 0, 40, '...') }}</td>
                <td class="p-4 text-gray-400 dark:text-gray-500 text-xs">{{ $msg->created_at ? $msg->created_at->format('d M Y H:i') : '' }}</td>
                <td class="p-4">
                    <span class="px-3 py-1 rounded-full text-xs font-bold 
                        {{ $msg->estado === 'pendiente' ? 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400' : 
                          ($msg->estado === 'revisado' ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400' : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400') }}">
                        {{ ucfirst($msg->estado) }}
                    </span>
                </td>
                <td class="p-4">
                    <button onclick="openReplyModal({{ $msg->id }}, '{{ addslashes($msg->nombre) }}')" class="text-xs {{ $msg->estado === 'respondido' ? 'bg-gray-500 hover:bg-gray-600' : 'bg-emerald-500 hover:bg-emerald-600' }} text-white px-4 py-2 rounded-xl font-bold transition-all flex items-center gap-1">
                        <span class="material-symbols-outlined text-[16px]">{{ $msg->estado === 'respondido' ? 'forum' : 'reply' }}</span> 
                        {{ $msg->estado === 'respondido' ? 'Ver Conversación' : 'Responder' }}
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
<div id="replyModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm hidden text-left">
    <div class="bg-white rounded-[2rem] p-8 max-w-lg w-full shadow-2xl transform transition-all max-h-[90vh] flex flex-col">
        <div class="flex justify-between items-center mb-4 border-b border-gray-100 pb-4 shrink-0">
            <h3 class="text-xl font-black text-[#064E3B] flex items-center gap-2" id="replyModalTitle">
                <span class="material-symbols-outlined text-emerald-500">forum</span> Conversación
            </h3>
            <button type="button" onclick="closeReplyModal()" class="text-gray-400 hover:text-red-500">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        
        {{-- Thread de conversación --}}
        <div id="replyThread" class="overflow-y-auto mb-4 grow pr-1" style="max-height: 350px;">
            <div class="text-center py-4 text-gray-400">Cargando...</div>
        </div>
        
        {{-- Formulario de respuesta --}}
        <form id="replyModalForm" action="" method="POST" class="shrink-0 border-t border-gray-100 pt-4">
            @csrf 
            @method('PUT')
            <div class="mb-4">
                <label class="block text-xs font-bold uppercase text-gray-500 mb-2">Tu Respuesta (Admin)</label>
                <textarea name="respuesta_admin" required minlength="2" rows="3" class="w-full px-4 py-3 rounded-xl bg-white border border-emerald-200 focus:ring-2 focus:ring-[#00E096] transition-all resize-none" placeholder="Escribe tu respuesta..."></textarea>
                @error('respuesta_admin')
                    <p class="text-red-500 text-xs font-bold mt-2"><span class="material-symbols-outlined text-[14px] align-middle">error</span> {{ $message }}</p>
                @enderror
            </div>
            <div class="flex justify-end gap-3">
                <button type="button" onclick="closeReplyModal()" class="px-5 py-2.5 rounded-xl font-bold text-gray-500 hover:bg-gray-100 transition-colors">Cancelar</button>
                <button type="submit" class="bg-[#00E096] hover:bg-emerald-400 text-secondary font-black px-6 py-2.5 rounded-xl shadow-lg flex items-center gap-2">
                    <span class="material-symbols-outlined text-[18px]">send</span> Enviar
                </button>
            </div>
        </form>
    </div>
</div>

@endsection
