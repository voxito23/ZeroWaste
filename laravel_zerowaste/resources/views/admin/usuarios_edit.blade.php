@extends('layouts.admin')

@section('title', 'Editar Usuario')
@section('page_title', 'Editar Usuario')

@push('styles')
<style>
    .edit-card{background:rgba(255,255,255,0.85);backdrop-filter:blur(20px);border:1px solid rgba(16,185,129,0.08);border-radius:1.5rem;transition:all .4s cubic-bezier(.4,0,.2,1)}
    .dark .edit-card{background:rgba(15,42,32,0.7);border-color:rgba(255,255,255,0.05)}
    .edit-card:hover{box-shadow:0 20px 50px rgba(0,0,0,0.08)}
    .dark .edit-card:hover{box-shadow:0 20px 50px rgba(0,0,0,0.3)}
    .avatar-ring{position:relative;width:120px;height:120px}
    .avatar-ring::before{content:'';position:absolute;inset:-4px;border-radius:50%;background:conic-gradient(from 0deg,#10B981,#06D6A0,#34D399,#10B981);padding:3px;-webkit-mask:linear-gradient(#fff 0 0) content-box,linear-gradient(#fff 0 0);-webkit-mask-composite:xor;mask-composite:exclude;animation:spinRing 4s linear infinite}
    @keyframes spinRing{to{transform:rotate(360deg)}}
    .field-group{position:relative}
    .field-group label{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;color:#6B7280;margin-bottom:6px;display:block}
    .dark .field-group label{color:#6ee7b7}
    .field-input{width:100%;padding:10px 14px 10px 42px;border-radius:12px;font-size:13px;font-weight:500;background:rgba(255,255,255,0.9);border:1.5px solid rgba(0,0,0,0.06);transition:all .25s;outline:none}
    .dark .field-input{background:rgba(6,78,59,0.1);border-color:rgba(255,255,255,0.06);color:#d1fae5}
    .field-input:focus{border-color:#10B981;box-shadow:0 0 0 3px rgba(16,185,129,0.12)}
    .field-input:hover{border-color:rgba(16,185,129,0.3)}
    .field-icon{position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#9CA3AF;font-size:18px;pointer-events:none}
    .dark .field-icon{color:rgba(52,211,153,0.5)}
    .section-divider{height:1px;background:linear-gradient(to right,transparent,rgba(16,185,129,0.15),transparent);margin:28px 0}
    .toggle-pill{position:relative;display:inline-flex;align-items:center;gap:10px;padding:8px 16px;border-radius:12px;cursor:pointer;transition:all .25s;border:1.5px solid rgba(0,0,0,0.06);background:rgba(255,255,255,0.9)}
    .dark .toggle-pill{background:rgba(6,78,59,0.1);border-color:rgba(255,255,255,0.06)}
    .toggle-pill:has(input:checked){border-color:rgba(16,185,129,0.3);background:rgba(16,185,129,0.06)}
    .toggle-pill.danger:has(input:checked){border-color:rgba(239,68,68,0.3);background:rgba(239,68,68,0.06)}
</style>
@endpush

@section('content')
<div class="max-w-4xl mx-auto">
    {{-- Header Card --}}
    <div class="edit-card p-6 mb-6 relative overflow-hidden">
        <div class="absolute -top-20 -right-20 w-60 h-60 bg-gradient-to-br from-emerald-400/10 to-teal-500/5 rounded-full blur-3xl pointer-events-none"></div>
        
        <div class="flex flex-col sm:flex-row items-center gap-6 relative z-10">
            {{-- Avatar --}}
            <div class="avatar-ring shrink-0">
                @php $currentFoto = $user->foto_perfil ?? 'perfil_default.png'; @endphp
                <img id="preview-foto" src="{{ url('/static/img/perfiles/' . $currentFoto) }}"
                     alt="{{ $user->nombre }}" class="w-full h-full rounded-full object-cover border-[3px] border-white dark:border-[#0B1F18] shadow-xl"
                     onerror="this.onerror=null; this.src='data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 120 120%22><rect fill=%22%2334D399%22 width=%22120%22 height=%22120%22 rx=%2260%22/><text x=%2250%%22 y=%2256%%22 text-anchor=%22middle%22 fill=%22%23064E3B%22 font-size=%2248%22 font-weight=%22bold%22 font-family=%22Inter%22>{{ strtoupper(substr($user->nombre, 0, 1)) }}</text></svg>';">
            </div>
            
            <div class="text-center sm:text-left flex-1">
                <h2 class="text-2xl font-black text-[#064E3B] dark:text-white tracking-tight">{{ $user->nombre }}</h2>
                <p class="text-sm text-gray-500 dark:text-emerald-300/60 font-medium mt-0.5">{{ $user->email }}</p>
                <div class="flex flex-wrap items-center gap-2 mt-3 justify-center sm:justify-start">
                    @if($user->is_admin)
                        <span class="badge-sm bg-violet-500/10 text-violet-600 dark:text-violet-400 border border-violet-500/20">
                            <span class="material-symbols-outlined text-[12px]">shield_person</span> Admin
                        </span>
                    @endif
                    @if($user->email === 'vichdz@gmail.com')
                        <span class="badge-sm bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20">
                            <span class="material-symbols-outlined text-[12px]">verified</span> Principal
                        </span>
                    @endif
                    <span class="badge-sm {{ ($user->bloqueado ?? false) ? 'bg-red-500/10 text-red-500 border border-red-500/20' : 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20' }}">
                        <span class="w-1.5 h-1.5 rounded-full {{ ($user->bloqueado ?? false) ? 'bg-red-500' : 'bg-emerald-500' }}"></span>
                        {{ ($user->bloqueado ?? false) ? 'Bloqueado' : 'Activo' }}
                    </span>
                    @if($user->titulo_perfil)
                        <span class="badge-sm bg-gray-100 dark:bg-white/5 text-gray-500 dark:text-gray-400">{{ $user->titulo_perfil }}</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <form id="userEditForm" novalidate action="{{ route('usuarios.update', $user) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        {{-- Info Card --}}
        <div class="edit-card p-6 mb-6">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-9 h-9 rounded-xl bg-emerald-500/10 border border-emerald-500/15 flex items-center justify-center">
                    <span class="material-symbols-outlined text-emerald-500 text-lg">person</span>
                </div>
                <div>
                    <h3 class="font-bold text-sm text-[#064E3B] dark:text-white">Información Personal</h3>
                    <p class="text-[11px] text-gray-400">Datos básicos de la cuenta</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                {{-- Nombre --}}
                <div class="field-group">
                    <label>Nombre Completo</label>
                    <div class="relative">
                        <span class="field-icon material-symbols-outlined">badge</span>
                        <input type="text" name="nombre" value="{{ old('nombre', $user->nombre) }}" required class="field-input">
                    </div>
                    <span id="err-nombre" class="hidden text-red-500 text-[11px] mt-1 font-medium"></span>
                </div>

                {{-- Email --}}
                <div class="field-group">
                    <label>Correo Electrónico</label>
                    <div class="relative">
                        <span class="field-icon material-symbols-outlined">mail</span>
                        <input type="email" name="email" value="{{ old('email', $user->email) }}" required class="field-input">
                    </div>
                    <span id="err-email" class="hidden text-red-500 text-[11px] mt-1 font-medium"></span>
                </div>

                {{-- Ubicación --}}
                <div class="field-group">
                    <label>Ubicación</label>
                    <div class="relative">
                        <span class="field-icon material-symbols-outlined">location_on</span>
                        <input type="text" name="ubicacion" value="{{ old('ubicacion', $user->ubicacion) }}" required placeholder="Querétaro, Qro." class="field-input">
                    </div>
                    <span id="err-ubicacion" class="hidden text-red-500 text-[11px] mt-1 font-medium"></span>
                </div>

                {{-- Título del Perfil --}}
                <div class="field-group">
                    <label>Título del Perfil</label>
                    <div class="relative custom-dropdown" tabindex="0">
                        <input type="hidden" name="titulo_perfil" id="titulo_perfil" value="{{ old('titulo_perfil', $user->titulo_perfil) }}" required>
                        @php
                            $iconMap = [
                                'Usuario Eco-consciente' => 'eco',
                                'Entusiasta del Desarrollo Sostenible' => 'public',
                                'Guardián de la Tierra' => 'landscape',
                                'Reciclador Estrella' => 'star',
                                'Eco-guerrero' => 'shield'
                            ];
                            $currentTitle = old('titulo_perfil', $user->titulo_perfil);
                            $currentIcon = $iconMap[$currentTitle] ?? 'psychology';
                        @endphp
                        <span class="field-icon material-symbols-outlined">psychology</span>
                        <div class="dropdown-selected field-input cursor-pointer flex justify-between items-center" onclick="this.nextElementSibling.classList.toggle('hidden')">
                            <span class="selected-text flex items-center gap-2">
                                <span class="material-symbols-outlined text-emerald-500 text-base">{{ $currentIcon }}</span> {{ $currentTitle ?: 'Selecciona un título...' }}
                            </span>
                            <span class="material-symbols-outlined text-gray-400 text-base">expand_more</span>
                        </div>
                        <div class="dropdown-options hidden absolute z-50 w-full mt-2 bg-white dark:bg-[#0F2A20] border border-gray-100 dark:border-emerald-800/50 rounded-xl shadow-xl overflow-hidden">
                            @foreach($iconMap as $title => $icon)
                            <div class="option-item p-3 hover:bg-emerald-50 dark:hover:bg-emerald-900/30 cursor-pointer flex items-center gap-2 text-gray-700 dark:text-gray-300 transition-colors text-sm" data-value="{{ $title }}">
                                <span class="material-symbols-outlined text-emerald-500 text-base">{{ $icon }}</span> {{ $title }}
                            </div>
                            @endforeach
                        </div>
                    </div>
                    <span id="err-titulo" class="hidden text-red-500 text-[11px] mt-1 font-medium"></span>
                </div>
            </div>

            {{-- Photo Upload --}}
            <div class="mt-5 flex items-center gap-4">
                <input type="file" name="foto_perfil" id="foto-input" accept="image/*" class="hidden" onchange="previewImage(this)">
                <label for="foto-input" class="inline-flex items-center gap-2 text-xs px-4 py-2.5 bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 font-bold rounded-xl cursor-pointer hover:bg-emerald-100 dark:hover:bg-emerald-800/40 transition-colors border border-emerald-200 dark:border-emerald-700/50">
                    <span class="material-symbols-outlined text-base">photo_camera</span> Cambiar Fotografía
                </label>
                <span class="text-[11px] text-gray-400 dark:text-emerald-600/60">Formatos: JPG, PNG, WEBP — Máx 250MB</span>
            </div>
        </div>

        {{-- Security Card --}}
        <div class="edit-card p-6 mb-6">
            <div class="flex items-center gap-3 mb-5">
                <div class="w-9 h-9 rounded-xl bg-blue-500/10 border border-blue-500/15 flex items-center justify-center">
                    <span class="material-symbols-outlined text-blue-500 text-lg">lock</span>
                </div>
                <div>
                    <h3 class="font-bold text-sm text-[#064E3B] dark:text-white">Seguridad</h3>
                    <p class="text-[11px] text-gray-400">Dejar en blanco si no deseas cambiar la contraseña</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                <div class="field-group">
                    <label>Contraseña Actual</label>
                    <div class="relative">
                        <span class="field-icon material-symbols-outlined">key</span>
                        <input type="password" name="password_actual" id="old-password" class="field-input" placeholder="••••••">
                        <button type="button" onclick="togglePass('old-password', this)" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-emerald-500 transition-colors">
                            <span class="material-symbols-outlined text-[16px]">visibility_off</span>
                        </button>
                    </div>
                    @error('password_actual')
                        <span class="text-red-500 text-[11px] mt-1 font-medium">{{ $message }}</span>
                    @enderror
                    <span id="err-old-password" class="hidden text-red-500 text-[11px] mt-1 font-medium"></span>
                </div>

                <div class="field-group">
                    <label>Nueva Contraseña</label>
                    <div class="relative">
                        <span class="field-icon material-symbols-outlined">lock_open</span>
                        <input type="password" name="password" id="edit-password" class="field-input" placeholder="Mínimo 6 caracteres">
                        <button type="button" onclick="togglePass('edit-password', this)" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-emerald-500 transition-colors">
                            <span class="material-symbols-outlined text-[16px]">visibility_off</span>
                        </button>
                    </div>
                    <span id="err-password" class="hidden text-red-500 text-[11px] mt-1 font-medium"></span>
                </div>

                <div class="field-group">
                    <label>Confirmar Contraseña</label>
                    <div class="relative">
                        <span class="field-icon material-symbols-outlined">lock_clock</span>
                        <input type="password" name="password_confirmation" id="confirm-password" class="field-input" placeholder="Repite la contraseña">
                        <button type="button" onclick="togglePass('confirm-password', this)" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-emerald-500 transition-colors">
                            <span class="material-symbols-outlined text-[16px]">visibility_off</span>
                        </button>
                    </div>
                    <span id="err-confirm-password" class="hidden text-red-500 text-[11px] mt-1 font-medium"></span>
                </div>
            </div>
        </div>

        {{-- Permissions & Actions --}}
        <div class="edit-card p-6 mb-6">
            <div class="flex items-center gap-3 mb-5">
                <div class="w-9 h-9 rounded-xl bg-violet-500/10 border border-violet-500/15 flex items-center justify-center">
                    <span class="material-symbols-outlined text-violet-500 text-lg">admin_panel_settings</span>
                </div>
                <div>
                    <h3 class="font-bold text-sm text-[#064E3B] dark:text-white">Permisos y Estado</h3>
                    <p class="text-[11px] text-gray-400">Configuración de rol y acceso</p>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-4">
                <label class="toggle-pill">
                    <input type="checkbox" name="is_admin" id="is_admin" {{ $user->is_admin ? 'checked' : '' }} class="sr-only peer">
                    <div class="w-10 h-5 bg-gray-200 rounded-full peer peer-checked:bg-emerald-500 after:content-[''] after:absolute after:top-[10px] after:left-[18px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:after:translate-x-5 shadow-inner"></div>
                    <div class="flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-emerald-500 text-base">shield_person</span>
                        <span class="font-bold text-xs text-gray-700 dark:text-emerald-200">Administrador</span>
                    </div>
                </label>
                
                <label class="toggle-pill danger">
                    <input type="checkbox" name="bloqueado" id="bloqueado" {{ $user->bloqueado ? 'checked' : '' }} class="sr-only peer">
                    <div class="w-10 h-5 bg-gray-200 rounded-full peer peer-checked:bg-red-500 after:content-[''] after:absolute after:top-[10px] after:left-[18px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:after:translate-x-5 shadow-inner"></div>
                    <div class="flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-red-500 text-base">block</span>
                        <span class="font-bold text-xs text-red-600 dark:text-red-400">Bloquear</span>
                    </div>
                </label>
            </div>
        </div>

        {{-- Action Buttons --}}
        <div class="flex items-center justify-between">
            <a href="{{ route('usuarios.index') }}" class="inline-flex items-center gap-2 text-sm font-bold text-gray-500 dark:text-gray-400 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors py-2.5 px-4 rounded-xl hover:bg-gray-50 dark:hover:bg-emerald-900/20">
                <span class="material-symbols-outlined text-lg">arrow_back</span> Cancelar
            </a>
            <button type="submit" class="btn-primary py-3 px-8 rounded-xl text-sm">
                <span class="material-symbols-outlined text-lg">save</span> Guardar Cambios
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
function togglePass(id, btn) {
    const inp = document.getElementById(id);
    const ico = btn.querySelector('.material-symbols-outlined');
    if (inp.type === 'password') { inp.type = 'text'; ico.textContent = 'visibility'; }
    else { inp.type = 'password'; ico.textContent = 'visibility_off'; }
}

function previewImage(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('preview-foto').src = e.target.result;
        }
        reader.readAsDataURL(input.files[0]);
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // Custom Dropdown JS
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.custom-dropdown')) {
            document.querySelectorAll('.dropdown-options').forEach(el => el.classList.add('hidden'));
        }
    });

    document.querySelectorAll('.option-item').forEach(item => {
        item.addEventListener('click', function(e) {
            e.stopPropagation();
            const dropdown = this.closest('.custom-dropdown');
            const hiddenInput = dropdown.querySelector('input[type="hidden"]');
            const selectedText = dropdown.querySelector('.selected-text');
            
            hiddenInput.value = this.dataset.value;
            selectedText.innerHTML = this.innerHTML;
            
            dropdown.querySelector('.dropdown-options').classList.add('hidden');
            const errSpan = document.getElementById('err-titulo');
            if(errSpan) errSpan.classList.add('hidden');
        });
    });

    const form = document.getElementById('userEditForm');
    if (!form) return;

    form.addEventListener('submit', function(e) {
        const nombre = form.querySelector('input[name="nombre"]');
        const email = form.querySelector('input[name="email"]');
        const password = form.querySelector('input[name="password"]');
        const confirmPassword = form.querySelector('input[name="password_confirmation"]');
        const ubicacion = form.querySelector('input[name="ubicacion"]');
        const titulo = form.querySelector('input[name="titulo_perfil"]');
        const passwordActual = form.querySelector('input[name="password_actual"]');

        const errNombre = document.getElementById('err-nombre');
        const errEmail = document.getElementById('err-email');
        const errPassword = document.getElementById('err-password');
        const errPassActual = document.getElementById('err-old-password');
        const errConfirmPass = document.getElementById('err-confirm-password');
        const errUbicacion = document.getElementById('err-ubicacion');
        const errTitulo = document.getElementById('err-titulo');

        // Reset
        [errNombre, errEmail, errPassword, errConfirmPass, errUbicacion, errTitulo, errPassActual].forEach(el => { if(el) el.classList.add('hidden'); });
        [nombre, email, password, confirmPassword, ubicacion, passwordActual].forEach(el => { if(el) el.classList.remove('border-red-500'); });

        let isValid = true;

        if (password.value || confirmPassword.value) {
            if (!passwordActual.value) {
                errPassActual.textContent = 'Debes ingresar la contraseña anterior para confirmar el cambio.';
                errPassActual.classList.remove('hidden');
                passwordActual.classList.add('border-red-500');
                isValid = false;
            }
        }

        if (!nombre.value.trim() || nombre.value.trim().length < 11) {
            errNombre.textContent = 'El nombre completo debe tener al menos 11 caracteres.';
            errNombre.classList.remove('hidden');
            nombre.classList.add('border-red-500');
            isValid = false;
        }

        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!email.value.trim() || !emailRegex.test(email.value.trim())) {
            errEmail.textContent = 'El correo debe tener @ y un dominio válido.';
            errEmail.classList.remove('hidden');
            email.classList.add('border-red-500');
            isValid = false;
        }

        if (password.value) {
            if (password.value.length < 6) {
                errPassword.textContent = 'La contraseña debe tener al menos 6 caracteres.';
                errPassword.classList.remove('hidden');
                password.classList.add('border-red-500');
                isValid = false;
            }
            if (password.value !== confirmPassword.value) {
                errConfirmPass.textContent = 'Las contraseñas no coinciden.';
                errConfirmPass.classList.remove('hidden');
                confirmPassword.classList.add('border-red-500');
                isValid = false;
            }
        }

        if (!ubicacion.value.trim()) {
            errUbicacion.textContent = 'La ubicación es obligatoria.';
            errUbicacion.classList.remove('hidden');
            ubicacion.classList.add('border-red-500');
            isValid = false;
        }

        if (!titulo.value.trim()) {
            errTitulo.textContent = 'El título del perfil es obligatorio.';
            errTitulo.classList.remove('hidden');
            isValid = false;
        }

        if (!isValid) {
            e.preventDefault();
        }
    });
});
</script>
@endpush
