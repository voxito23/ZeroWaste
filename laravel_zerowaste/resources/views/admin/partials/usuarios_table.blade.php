<div class="overflow-x-auto">
    <table id="usersTable" class="premium-table w-full">
        <thead>
            <tr>
                <th>Usuario</th>
                <th>Rol</th>
                <th>Estado</th>
                <th>Ubicación</th>
                <th>Título</th>
                <th>Registro</th>
                <th class="text-right">Acciones</th>
            </tr>
        </thead>
        <tbody class="transition-opacity duration-300" id="usersTableBody">
            @forelse ($usuarios as $user)
            @php
                $userRole = $user->is_admin ? 'admin' : 'user';
                if ($user->rol === 'recolector') $userRole = 'recolector';
                if ($user->bloqueado ?? false) $userRole = 'blocked';
            @endphp
            <tr class="user-row" data-user-role="{{ $userRole }}">
                <td>
                    <div class="flex items-center gap-3">
                        @php $userFoto = $user->foto_perfil ?? 'default.png'; @endphp
                        <img src="{{ url('/static/img/perfiles/' . $userFoto) }}" alt="{{ $user->nombre }}"
                             class="w-9 h-9 rounded-full border-2 {{ $user->is_admin ? 'border-violet-400' : 'border-emerald-400' }} object-cover"
                             onerror="this.onerror=null; this.src='data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 40 40%22><rect fill=%22%2334D399%22 width=%2240%22 height=%2240%22 rx=%2220%22/><text x=%2250%%22 y=%2254%%22 text-anchor=%22middle%22 fill=%22%23064E3B%22 font-size=%2218%22 font-weight=%22bold%22>{{ strtoupper(substr($user->nombre, 0, 1)) }}</text></svg>';">
                        <div>
                            <p class="font-bold text-[#064E3B] dark:text-white text-sm">{{ $user->nombre }}</p>
                            <p class="text-[11px] text-gray-400">{{ $user->email }}</p>
                        </div>
                    </div>
                </td>
                <td>
                    <div class="flex flex-wrap gap-1.5 items-center">
                        @if($user->is_admin || $user->rol === 'admin')
                            <span class="badge-sm bg-violet-500/10 text-violet-600 dark:text-violet-400 font-bold border border-violet-500/20">Admin</span>
                        @endif
                        @if($user->rol === 'recolector')
                            <span class="badge-sm bg-amber-500/10 text-amber-600 dark:text-amber-400 font-bold border border-amber-500/20">Recolector</span>
                        @endif
                        @if(!$user->is_admin && $user->rol !== 'admin' && $user->rol !== 'recolector')
                            <span class="badge-sm bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 font-bold border border-emerald-500/20">Usuario</span>
                        @endif
                    </div>
                </td>
                <td>
                    @if($user->bloqueado ?? false)
                        <span class="flex items-center gap-1.5"><span class="w-1.5 h-1.5 rounded-full bg-red-500"></span><span class="text-xs font-bold text-red-500">Bloqueado</span></span>
                    @else
                        <span class="flex items-center gap-1.5"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span><span class="text-xs font-bold text-emerald-500">Activo</span></span>
                    @endif
                </td>
                <td class="text-gray-400 text-xs">{{ $user->ubicacion ?? '—' }}</td>
                <td><span class="badge-sm bg-gray-100 dark:bg-white/5 text-gray-500 dark:text-gray-400">{{ $user->titulo_perfil ?? 'Ciudadano' }}</span></td>
                <td class="text-gray-400 text-xs">{{ $user->created_at ? $user->created_at->format('d M Y') : '—' }}</td>
                <td class="text-right">
                    <div class="flex items-center justify-end gap-1">
                        @php
                            $isPrincipal = $user->email === config('app.admin_email');
                            $isSelf = auth()->check() && auth()->id() === $user->id;
                            $amPrincipal = auth()->check() && auth()->user()->email === config('app.admin_email');
                        @endphp

                        @if($isPrincipal && !$amPrincipal)
                            {{-- Non-principal admin can't edit/delete principal --}}
                            <span class="w-8 h-8 rounded-lg bg-amber-500/10 text-amber-500 flex items-center justify-center cursor-not-allowed" title="Cuenta protegida">
                                <span class="material-symbols-outlined text-[16px]">shield</span>
                            </span>
                        @else
                            <a href="{{ route('usuarios.edit', $user) }}" class="w-8 h-8 rounded-lg bg-blue-500/10 hover:bg-blue-500 text-blue-500 hover:text-white flex items-center justify-center transition-all" title="Editar">
                                <span class="material-symbols-outlined text-[16px]">edit</span>
                            </a>
                            @if(!$isPrincipal && !$isSelf)
                                @if(!$user->is_admin || $amPrincipal)
                                <form action="{{ route('usuarios.destroy', $user) }}" method="POST" class="inline" onsubmit="return confirm('¿Eliminar este usuario?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="w-8 h-8 rounded-lg bg-red-500/10 hover:bg-red-500 text-red-500 hover:text-white flex items-center justify-center transition-all" title="Eliminar">
                                        <span class="material-symbols-outlined text-[16px]">delete</span>
                                    </button>
                                </form>
                                @else
                                <span class="w-8 h-8 rounded-lg bg-gray-100 dark:bg-white/5 text-gray-300 dark:text-gray-600 flex items-center justify-center cursor-not-allowed"><span class="material-symbols-outlined text-[16px]">lock</span></span>
                                @endif
                            @else
                                <span class="w-8 h-8 rounded-lg bg-gray-100 dark:bg-white/5 text-gray-300 dark:text-gray-600 flex items-center justify-center cursor-not-allowed"><span class="material-symbols-outlined text-[16px]">lock</span></span>
                            @endif
                        @endif
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="p-8 text-center text-gray-500 dark:text-gray-400 italic">No hay usuarios registrados con esos filtros.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- Paginación Premium --}}
@if($usuarios->hasPages())
<nav class="mt-6 flex items-center justify-between pagination-container" aria-label="Paginación">
    {{-- Anterior --}}
    @if($usuarios->onFirstPage())
        <span class="flex items-center gap-2 text-gray-300 dark:text-gray-600 text-sm font-semibold cursor-not-allowed select-none">
            <span class="material-symbols-outlined text-lg">arrow_back</span> Anterior
        </span>
    @else
        <a href="{{ $usuarios->previousPageUrl() }}" class="ajax-link flex items-center gap-2 text-[#064E3B] dark:text-emerald-300 text-sm font-bold hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">
            <span class="material-symbols-outlined text-lg">arrow_back</span> Anterior
        </a>
    @endif

    {{-- Números --}}
    <div class="flex items-center gap-1">
        @foreach($usuarios->getUrlRange(1, $usuarios->lastPage()) as $page => $url)
            @if($page == $usuarios->currentPage())
                <span class="w-9 h-9 flex items-center justify-center rounded-lg bg-emerald-500 text-white text-sm font-black shadow-md">{{ $page }}</span>
            @elseif($page == 1 || $page == $usuarios->lastPage() || abs($page - $usuarios->currentPage()) <= 1)
                <a href="{{ $url }}" class="ajax-link w-9 h-9 flex items-center justify-center rounded-lg text-sm font-semibold text-gray-500 dark:text-gray-400 hover:bg-emerald-50 dark:hover:bg-white/5 transition-colors">{{ $page }}</a>
            @elseif(abs($page - $usuarios->currentPage()) == 2)
                <span class="w-6 text-center text-gray-400 text-sm">…</span>
            @endif
        @endforeach
    </div>

    {{-- Siguiente --}}
    @if($usuarios->hasMorePages())
        <a href="{{ $usuarios->nextPageUrl() }}" class="ajax-link flex items-center gap-2 text-[#064E3B] dark:text-emerald-300 text-sm font-bold hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">
            Siguiente <span class="material-symbols-outlined text-lg">arrow_forward</span>
        </a>
    @else
        <span class="flex items-center gap-2 text-gray-300 dark:text-gray-600 text-sm font-semibold cursor-not-allowed select-none">
            Siguiente <span class="material-symbols-outlined text-lg">arrow_forward</span>
        </span>
    @endif
</nav>
@endif
