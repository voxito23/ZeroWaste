@extends('layouts.admin')

@section('title', 'Editar Usuario')
@section('page_title', 'Editar Usuario')

@section('content')
<div class="bg-white dark:bg-forest-card rounded-3xl shadow-lg border border-emerald-100 dark:border-emerald-800/50 p-8 max-w-2xl mx-auto">
    <form id="userEditForm" novalidate action="{{ route('usuarios.update', $user) }}" method="POST" enctype="multipart/form-data" class="flex flex-col gap-6">
        @csrf
        @method('PUT')

        @if ($errors->any())
        <div class="bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-300 p-4 rounded-xl text-sm font-bold">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>• {{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <!-- Foto de perfil con preview -->
        <div class="flex flex-col items-center gap-4 pb-6 border-b border-emerald-100 dark:border-emerald-800/50">
            <div class="relative group">
                @php
                    $currentFoto = $user->foto_perfil ?? 'perfil_default.png';
                @endphp
                <img id="preview-foto" src="{{ url('/static/img/perfiles/' . $currentFoto) }}"
                     alt="{{ $user->nombre }}" class="w-24 h-24 rounded-full object-cover border-[3px] border-primary shadow-lg shadow-primary/20"
                     onerror="this.onerror=null; this.src='data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 120 120%22><rect fill=%22%2334D399%22 width=%22120%22 height=%22120%22 rx=%2260%22/><text x=%2250%%22 y=%2256%%22 text-anchor=%22middle%22 fill=%22%23064E3B%22 font-size=%2248%22 font-weight=%22bold%22 font-family=%22Inter%22>{{ strtoupper(substr($user->nombre, 0, 1)) }}</text></svg>';">
                <label for="foto-input" class="absolute inset-0 flex items-center justify-center bg-black/40 rounded-full opacity-0 group-hover:opacity-100 transition-opacity cursor-pointer">
                    <span class="material-symbols-outlined text-white text-2xl">photo_camera</span>
                </label>
            </div>
            <input type="file" name="foto_perfil" id="foto-input" accept="image/*" class="hidden" onchange="previewImage(this)">
            <label for="foto-input" class="text-xs text-primary font-bold cursor-pointer hover:underline">Cambiar foto de perfil</label>
        </div>

        <div>
            <label class="block font-bold mb-2 dark:text-emerald-200">Nombre Completo</label>
            <input type="text" name="nombre" value="{{ old('nombre', $user->nombre) }}" required class="w-full border-2 border-emerald-100 dark:border-emerald-800 dark:bg-forest-dark dark:text-white rounded-xl p-3 focus:border-emerald-400 outline-none transition-colors">
            <span id="err-nombre" class="hidden text-red-500 text-sm mt-1 font-medium"></span>
        </div>

        <div>
            <label class="block font-bold mb-2 dark:text-emerald-200">Correo Electrónico</label>
            <input type="email" name="email" value="{{ old('email', $user->email) }}" required class="w-full border-2 border-emerald-100 dark:border-emerald-800 dark:bg-forest-dark dark:text-white rounded-xl p-3 focus:border-emerald-400 outline-none transition-colors">
            <span id="err-email" class="hidden text-red-500 text-sm mt-1 font-medium"></span>
        </div>

        <div>
            <label class="block font-bold mb-2 dark:text-emerald-200">Nueva Contraseña <span class="text-gray-400 dark:text-emerald-600 text-xs font-normal">(Dejar en blanco para mantener la actual)</span></label>
            <div class="relative">
                <input type="password" name="password" id="edit-password" class="w-full border-2 border-emerald-100 dark:border-emerald-800 dark:bg-forest-dark dark:text-white rounded-xl p-3 pr-12 focus:border-emerald-400 outline-none transition-colors">
                <button type="button" onclick="togglePass('edit-password', this)" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-primary transition-colors">
                    <span class="material-symbols-outlined">visibility_off</span>
                </button>
            </div>
            <span id="err-password" class="hidden text-red-500 text-sm mt-1 font-medium"></span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block font-bold mb-2 dark:text-emerald-200">Ubicación</label>
                <input type="text" name="ubicacion" value="{{ old('ubicacion', $user->ubicacion) }}" required placeholder="Querétaro, Qro." class="w-full border-2 border-emerald-100 dark:border-emerald-800 dark:bg-forest-dark dark:text-white rounded-xl p-3 focus:border-emerald-400 outline-none transition-colors">
                <span id="err-ubicacion" class="hidden text-red-500 text-sm mt-1 font-medium"></span>
            </div>
            <div>
                <label class="block font-bold mb-2 dark:text-emerald-200">Título del Perfil</label>
                <input type="text" name="titulo_perfil" value="{{ old('titulo_perfil', $user->titulo_perfil) }}" required placeholder="Eco-guerrero..." class="w-full border-2 border-emerald-100 dark:border-emerald-800 dark:bg-forest-dark dark:text-white rounded-xl p-3 focus:border-emerald-400 outline-none transition-colors">
                <span id="err-titulo" class="hidden text-red-500 text-sm mt-1 font-medium"></span>
            </div>
        </div>

        <div class="flex items-center gap-3 pt-2">
            <input type="checkbox" name="is_admin" id="is_admin" {{ $user->is_admin ? 'checked' : '' }} class="w-5 h-5 accent-emerald-500 rounded">
            <label for="is_admin" class="font-bold text-sm text-gray-700 dark:text-emerald-200">Permisos de Administrador</label>
        </div>

        <div class="flex justify-end gap-4 mt-6">
            <a href="{{ route('usuarios.index') }}" class="py-3 px-6 text-gray-500 dark:text-gray-400 font-bold hover:bg-gray-100 dark:hover:bg-emerald-900/30 rounded-xl">Cancelar</a>
            <button type="submit" class="bg-primary hover:bg-emerald-500 text-secondary font-bold py-3 px-8 rounded-xl shadow-md transition-transform hover:-translate-y-1">Guardar Cambios</button>
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
        const ubicacion = form.querySelector('input[name="ubicacion"]');
        const titulo = form.querySelector('input[name="titulo_perfil"]');

        const errNombre = document.getElementById('err-nombre');
        const errEmail = document.getElementById('err-email');
        const errPass = document.getElementById('err-password');
        const errUbicacion = document.getElementById('err-ubicacion');
        const errTitulo = document.getElementById('err-titulo');

        // Reset
        [errNombre, errEmail, errPass, errUbicacion, errTitulo].forEach(el => el.classList.add('hidden'));
        [nombre, email, password, ubicacion, titulo].forEach(el => el.classList.remove('border-red-500'));

        let isValid = true;

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
        if (password.value && password.value.length < 6) {
            errPass.textContent = 'La contraseña debe tener al menos 6 caracteres.';
            errPass.classList.remove('hidden');
            password.classList.add('border-red-500');
            isValid = false;
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
