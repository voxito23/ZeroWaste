@extends('layouts.admin')

@section('title', 'Editar Usuario')
@section('page_title', 'Editar Usuario')

@section('content')
<div class="bg-white/80 dark:bg-[#0B1F18]/80 backdrop-blur-xl rounded-[2rem] p-8 lg:p-10 shadow-2xl border border-white/50 dark:border-emerald-800/30 relative overflow-hidden group max-w-4xl mx-auto">
    <div class="absolute -top-40 -right-40 w-96 h-96 bg-gradient-to-br from-emerald-400/20 to-teal-500/10 rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute -bottom-40 -left-40 w-96 h-96 bg-gradient-to-tr from-emerald-500/10 to-transparent rounded-full blur-3xl pointer-events-none"></div>
    
    <div class="flex items-center gap-4 mb-10 relative z-10 border-b border-gray-100 dark:border-emerald-800/30 pb-6">
        <div class="w-16 h-16 bg-gradient-to-br from-emerald-400 to-emerald-600 rounded-[1.25rem] flex items-center justify-center text-white shadow-lg shadow-emerald-500/30">
            <span class="material-symbols-outlined text-[32px]">manage_accounts</span>
        </div>
        <div>
            <h2 class="text-3xl font-black text-[#064E3B] dark:text-white tracking-tight">Editar Usuario</h2>
            <p class="text-gray-500 dark:text-emerald-200/70 text-sm font-medium mt-1">Actualiza los datos y configuración de la cuenta.</p>
        </div>
    </div>

    <form id="userEditForm" novalidate action="{{ route('usuarios.update', $user) }}" method="POST" enctype="multipart/form-data" class="flex flex-col gap-6 relative z-10">
        @csrf
        @method('PUT')        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Left col: Avatar -->
            <div class="col-span-1 flex flex-col items-center justify-start border-r-0 lg:border-r border-gray-100 dark:border-emerald-800/30 pr-0 lg:pr-8">
                <div class="relative group mt-4 mb-4">
                    @php
                        $currentFoto = $user->foto_perfil ?? 'perfil_default.png';
                    @endphp
                    <img id="preview-foto" src="{{ url('/static/img/perfiles/' . $currentFoto) }}"
                         alt="{{ $user->nombre }}" class="w-40 h-40 rounded-full object-cover border-[4px] border-white dark:border-[#0B1F18] shadow-2xl transition-transform duration-500 group-hover:scale-105 group-hover:shadow-emerald-500/20"
                         onerror="this.onerror=null; this.src='data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 120 120%22><rect fill=%22%2334D399%22 width=%22120%22 height=%22120%22 rx=%2260%22/><text x=%2250%%22 y=%2256%%22 text-anchor=%22middle%22 fill=%22%23064E3B%22 font-size=%2248%22 font-weight=%22bold%22 font-family=%22Inter%22>{{ strtoupper(substr($user->nombre, 0, 1)) }}</text></svg>';">
                    <label for="foto-input" class="absolute inset-0 flex items-center justify-center bg-black/50 rounded-full opacity-0 group-hover:opacity-100 transition-all duration-300 cursor-pointer backdrop-blur-sm">
                        <span class="material-symbols-outlined text-white text-3xl mb-1">photo_camera</span>
                    </label>
                </div>
                <input type="file" name="foto_perfil" id="foto-input" accept="image/*" class="hidden" onchange="previewImage(this)">
                <label for="foto-input" class="text-xs px-4 py-2 bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 font-bold rounded-full cursor-pointer hover:bg-emerald-100 dark:hover:bg-emerald-800/40 transition-colors border border-emerald-200 dark:border-emerald-700/50">Cambiar Fotografía</label>
            </div>

            <!-- Right col: Form -->
            <div class="col-span-1 lg:col-span-2 flex flex-col gap-5">
                <div>
                    <label class="block font-bold mb-1.5 text-sm text-gray-700 dark:text-emerald-200">Nombre Completo</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none"><span class="material-symbols-outlined text-gray-400 dark:text-emerald-500/50 text-lg">badge</span></div>
                        <input type="text" name="nombre" value="{{ old('nombre', $user->nombre) }}" required class="w-full bg-gray-50/50 dark:bg-[#064E3B]/10 border border-gray-200 dark:border-emerald-800/30 text-gray-900 dark:text-white text-sm rounded-xl focus:ring-emerald-500 focus:border-emerald-500 block pl-11 p-3 transition-all duration-300 hover:bg-white dark:hover:bg-[#064E3B]/20">
                    </div>
                    <span id="err-nombre" class="hidden text-red-500 text-xs mt-1.5 font-medium ml-1"></span>
                </div>

                <div>
                    <label class="block font-bold mb-1.5 text-sm text-gray-700 dark:text-emerald-200">Correo Electrónico</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none"><span class="material-symbols-outlined text-gray-400 dark:text-emerald-500/50 text-lg">mail</span></div>
                        <input type="email" name="email" value="{{ old('email', $user->email) }}" required class="w-full bg-gray-50/50 dark:bg-[#064E3B]/10 border border-gray-200 dark:border-emerald-800/30 text-gray-900 dark:text-white text-sm rounded-xl focus:ring-emerald-500 focus:border-emerald-500 block pl-11 p-3 transition-all duration-300 hover:bg-white dark:hover:bg-[#064E3B]/20">
                    </div>
                    <span id="err-email" class="hidden text-red-500 text-xs mt-1.5 font-medium ml-1"></span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="block font-bold mb-1.5 text-sm text-gray-700 dark:text-emerald-200">Ubicación</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none"><span class="material-symbols-outlined text-gray-400 dark:text-emerald-500/50 text-lg">location_on</span></div>
                            <input type="text" name="ubicacion" value="{{ old('ubicacion', $user->ubicacion) }}" required placeholder="Querétaro, Qro." class="w-full bg-gray-50/50 dark:bg-[#064E3B]/10 border border-gray-200 dark:border-emerald-800/30 text-gray-900 dark:text-white text-sm rounded-xl focus:ring-emerald-500 focus:border-emerald-500 block pl-11 p-3 transition-all duration-300 hover:bg-white dark:hover:bg-[#064E3B]/20">
                        </div>
                        <span id="err-ubicacion" class="hidden text-red-500 text-xs mt-1.5 font-medium ml-1"></span>
                    </div>
                    <div>
                        <label class="block font-bold mb-1.5 text-sm text-gray-700 dark:text-emerald-200">Título del Perfil</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none"><span class="material-symbols-outlined text-gray-400 dark:text-emerald-500/50 text-lg">psychology</span></div>
                            <input type="text" name="titulo_perfil" value="{{ old('titulo_perfil', $user->titulo_perfil) }}" required placeholder="Eco-guerrero..." class="w-full bg-gray-50/50 dark:bg-[#064E3B]/10 border border-gray-200 dark:border-emerald-800/30 text-gray-900 dark:text-white text-sm rounded-xl focus:ring-emerald-500 focus:border-emerald-500 block pl-11 p-3 transition-all duration-300 hover:bg-white dark:hover:bg-[#064E3B]/20">
                        </div>
                        <span id="err-titulo" class="hidden text-red-500 text-xs mt-1.5 font-medium ml-1"></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-gray-50/50 dark:bg-[#064E3B]/10 rounded-2xl p-6 border border-gray-200 dark:border-emerald-800/30 mt-2">
            <h3 class="text-lg font-bold text-gray-800 dark:text-emerald-100 mb-4 flex items-center gap-2">
                <span class="material-symbols-outlined text-emerald-500">lock_reset</span> Cambio de Contraseña (Opcional)
            </h3>
            
            <div class="mb-5 border-b border-gray-200 dark:border-emerald-800/30 pb-5">
                <label class="block font-bold mb-1.5 text-sm text-gray-700 dark:text-emerald-200">Contraseña Actual <span class="text-gray-400 dark:text-emerald-600/70 font-normal">(Requerido para guardar cambios en contraseña)</span></label>
                <div class="relative max-w-md">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none"><span class="material-symbols-outlined text-gray-400 dark:text-emerald-500/50 text-lg">key</span></div>
                    <input type="password" name="password_actual" id="old-password" class="w-full bg-white dark:bg-[#0B1F18]/50 border border-gray-200 dark:border-emerald-800/50 text-gray-900 dark:text-white text-sm rounded-xl focus:ring-emerald-500 focus:border-emerald-500 block pl-11 pr-12 p-3 transition-all duration-300 hover:bg-white dark:hover:bg-[#0B1F18]/80">
                    <button type="button" onclick="togglePass('old-password', this)" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-emerald-500 transition-colors bg-white dark:bg-[#0F2A20] rounded-lg p-1 flex items-center justify-center">
                        <span class="material-symbols-outlined text-[18px]">visibility_off</span>
                    </button>
                </div>
                @error('password_actual')
                    <span class="text-red-500 text-xs mt-1.5 font-medium ml-1">{{ $message }}</span>
                @enderror
                <span id="err-old-password" class="hidden text-red-500 text-xs mt-1.5 font-medium ml-1"></span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="block font-bold mb-1.5 text-sm text-gray-700 dark:text-emerald-200">Nueva Contraseña</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none"><span class="material-symbols-outlined text-gray-400 dark:text-emerald-500/50 text-lg">lock_open</span></div>
                        <input type="password" name="password" id="edit-password" class="w-full bg-white dark:bg-[#0B1F18]/50 border border-gray-200 dark:border-emerald-800/50 text-gray-900 dark:text-white text-sm rounded-xl focus:ring-emerald-500 focus:border-emerald-500 block pl-11 pr-12 p-3 transition-all duration-300 hover:bg-white dark:hover:bg-[#0B1F18]/80">
                        <button type="button" onclick="togglePass('edit-password', this)" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-emerald-500 transition-colors bg-white dark:bg-[#0F2A20] rounded-lg p-1 flex items-center justify-center">
                            <span class="material-symbols-outlined text-[18px]">visibility_off</span>
                        </button>
                    </div>
                    <span id="err-password" class="hidden text-red-500 text-xs mt-1.5 font-medium ml-1"></span>
                </div>
                <div>
                    <label class="block font-bold mb-1.5 text-sm text-gray-700 dark:text-emerald-200">Confirmar Nueva Contraseña</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none"><span class="material-symbols-outlined text-gray-400 dark:text-emerald-500/50 text-lg">lock_clock</span></div>
                        <input type="password" name="password_confirmation" id="confirm-password" class="w-full bg-white dark:bg-[#0B1F18]/50 border border-gray-200 dark:border-emerald-800/50 text-gray-900 dark:text-white text-sm rounded-xl focus:ring-emerald-500 focus:border-emerald-500 block pl-11 pr-12 p-3 transition-all duration-300 hover:bg-white dark:hover:bg-[#0B1F18]/80">
                        <button type="button" onclick="togglePass('confirm-password', this)" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-emerald-500 transition-colors bg-white dark:bg-[#0F2A20] rounded-lg p-1 flex items-center justify-center">
                            <span class="material-symbols-outlined text-[18px]">visibility_off</span>
                        </button>
                    </div>
                    <span id="err-confirm-password" class="hidden text-red-500 text-xs mt-1.5 font-medium ml-1"></span>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-6 pt-4">
            <label class="relative inline-flex items-center cursor-pointer bg-emerald-50 dark:bg-[#064E3B]/20 py-2.5 px-4 rounded-xl border border-emerald-100 dark:border-emerald-800/30">
                <input type="checkbox" name="is_admin" id="is_admin" {{ $user->is_admin ? 'checked' : '' }} class="sr-only peer">
                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-emerald-300 dark:peer-focus:ring-emerald-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[12px] after:left-[18px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-emerald-500 mr-3"></div>
                <span class="font-bold text-sm text-gray-700 dark:text-emerald-200 ml-1">Administrador</span>
            </label>
            
            <label class="relative inline-flex items-center cursor-pointer bg-red-50 dark:bg-red-900/10 py-2.5 px-4 rounded-xl border border-red-100 dark:border-red-900/30">
                <input type="checkbox" name="bloqueado" id="bloqueado" {{ $user->bloqueado ? 'checked' : '' }} class="sr-only peer">
                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-red-300 dark:peer-focus:ring-red-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[12px] after:left-[18px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-red-500 mr-3"></div>
                <span class="font-bold text-sm text-red-700 dark:text-red-400 ml-1">Bloquear Usuario</span>
            </label>
        </div>

        <div class="flex justify-between items-center mt-6 pt-6 border-t border-gray-100 dark:border-emerald-800/30">
            <a href="{{ route('usuarios.index') }}" class="px-6 py-3 rounded-xl font-bold text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-emerald-900/30 transition-colors flex items-center gap-2">
                <span class="material-symbols-outlined text-lg">arrow_back</span> Cancelar
            </a>
            <button type="submit" class="bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-400 hover:to-teal-500 text-white font-bold py-3 px-8 rounded-xl shadow-lg shadow-emerald-500/30 transition-all duration-300 hover:-translate-y-1 flex items-center gap-2">
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
    const form = document.getElementById('userEditForm');
    if (!form) return;

    form.addEventListener('submit', function(e) {
        const nombre = form.querySelector('input[name="nombre"]');
        const email = form.querySelector('input[name="email"]');
        const password = form.querySelector('input[name="password"]');
        const confirmPassword = form.querySelector('input[name="password_confirmation"]');
        const ubicacion = form.querySelector('input[name="ubicacion"]');
        const titulo = form.querySelector('input[name="titulo_perfil"]');

        const errNombre = document.getElementById('err-nombre');
        const passwordActual = form.querySelector('input[name="password_actual"]');
        const errPassActual = document.getElementById('err-old-password');
        const errConfirmPass = document.getElementById('err-confirm-password');
        const errUbicacion = document.getElementById('err-ubicacion');
        const errTitulo = document.getElementById('err-titulo');

        // Reset
        [errNombre, errEmail, errPass, errConfirmPass, errUbicacion, errTitulo, errPassActual].forEach(el => el.classList.add('hidden'));
        [nombre, email, password, confirmPassword, ubicacion, titulo, passwordActual].forEach(el => el.classList.remove('border-red-500'));

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

        // Solo validar contraseña si se ingresó algo (en edición es opcional)
        if (password.value) {
            if (password.value.length < 6) {
                errPass.textContent = 'La contraseña debe tener al menos 6 caracteres.';
                errPass.classList.remove('hidden');
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
            titulo.classList.add('border-red-500');
            isValid = false;
        }

        if (!isValid) {
            e.preventDefault();
        }
    });
});
</script>
@endpush
