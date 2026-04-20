@extends('layouts.admin')

@section('title', 'Nuevo Usuario')
@section('page_title', 'Crear Usuario')

@section('content')
<div class="max-w-2xl bg-white dark:bg-forest-card rounded-3xl shadow-lg border border-emerald-100 dark:border-emerald-800/50 p-8">
    <h2 class="text-xl font-bold mb-6 text-secondary dark:text-white">Ingresa los datos del nuevo usuario</h2>
    


    <form id="userForm" novalidate action="{{ route('usuarios.store') }}" method="POST" enctype="multipart/form-data" class="flex flex-col gap-5">
        @csrf

        <!-- Foto de perfil con preview -->
        <div class="flex flex-col items-center gap-4 pb-6 border-b border-emerald-100 dark:border-emerald-800/50">
            <div class="relative group">
                <img id="preview-foto" src="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 120 120%22><rect fill=%22%2334D399%22 width=%22120%22 height=%22120%22 rx=%2260%22/><text x=%2250%%22 y=%2256%%22 text-anchor=%22middle%22 fill=%22%23064E3B%22 font-size=%2248%22 font-weight=%22bold%22 font-family=%22Inter%22>?</text></svg>"
                     alt="Preview" class="w-24 h-24 rounded-full object-cover border-[3px] border-primary shadow-lg shadow-primary/20">
                <label for="foto-input" class="absolute inset-0 flex items-center justify-center bg-black/40 rounded-full opacity-0 group-hover:opacity-100 transition-opacity cursor-pointer">
                    <span class="material-symbols-outlined text-white text-2xl">photo_camera</span>
                </label>
            </div>
            <input type="file" name="foto_perfil" id="foto-input" accept="image/*" class="hidden" onchange="previewImage(this)">
            <label for="foto-input" class="text-xs text-primary font-bold cursor-pointer hover:underline">Seleccionar foto de perfil</label>
        </div>

        <div>
            <label class="block font-bold mb-2 text-sm text-gray-600 dark:text-emerald-300">Nombre completo</label>
            <input type="text" name="nombre" required placeholder="Ej. María Martínez" class="w-full border-2 border-emerald-100 dark:border-emerald-800 dark:bg-forest-dark dark:text-white rounded-xl p-3 outline-none focus:border-primary transition-colors">
            <span id="err-nombre" class="hidden text-red-500 text-sm mt-1 font-medium"></span>
        </div>
        
        <div>
            <label class="block font-bold mb-2 text-sm text-gray-600 dark:text-emerald-300">Email</label>
            <input type="email" name="email" required placeholder="correo@ejemplo.com" class="w-full border-2 border-emerald-100 dark:border-emerald-800 dark:bg-forest-dark dark:text-white rounded-xl p-3 outline-none focus:border-primary transition-colors">
            <span id="err-email" class="hidden text-red-500 text-sm mt-1 font-medium"></span>
        </div>
        
        <div>
            <label class="block font-bold mb-2 text-sm text-gray-600 dark:text-emerald-300">Contraseña</label>
            <div class="relative">
                <input type="password" name="password" id="user-password" required placeholder="••••••••" class="w-full border-2 border-emerald-100 dark:border-emerald-800 dark:bg-forest-dark dark:text-white rounded-xl p-3 pr-12 outline-none focus:border-primary transition-colors">
                <button type="button" onclick="togglePass('user-password', this)" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-primary transition-colors">
                    <span class="material-symbols-outlined">visibility_off</span>
                </button>
            </div>
            <span id="err-password" class="hidden text-red-500 text-sm mt-1 font-medium"></span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block font-bold mb-2 text-sm text-gray-600 dark:text-emerald-300">Ubicación</label>
                <input type="text" name="ubicacion" required placeholder="Querétaro, Qro." class="w-full border-2 border-emerald-100 dark:border-emerald-800 dark:bg-forest-dark dark:text-white rounded-xl p-3 outline-none focus:border-primary transition-colors">
                <span id="err-ubicacion" class="hidden text-red-500 text-sm mt-1 font-medium"></span>
            </div>
            <div>
                <label class="block font-bold mb-2 text-sm text-gray-600 dark:text-emerald-300">Título del Perfil</label>
                <input type="text" name="titulo_perfil" required placeholder="Eco-guerrero, Reciclador..." class="w-full border-2 border-emerald-100 dark:border-emerald-800 dark:bg-forest-dark dark:text-white rounded-xl p-3 outline-none focus:border-primary transition-colors">
                <span id="err-titulo" class="hidden text-red-500 text-sm mt-1 font-medium"></span>
            </div>
        </div>

        <div class="flex items-center gap-3 pt-2">
            <input type="checkbox" name="is_admin" id="is_admin" class="w-5 h-5 accent-emerald-500 rounded">
            <label for="is_admin" class="font-bold text-sm text-gray-700 dark:text-emerald-200">Otorgar permisos de Administrador</label>
        </div>
        
        <div class="flex justify-end gap-4 mt-4 pt-4 border-t border-emerald-50 dark:border-emerald-800/50">
            <a href="{{ route('usuarios.index') }}" class="px-6 py-3 rounded-xl font-bold text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-emerald-900/30 transition-colors">Cancelar</a>
            <button type="submit" class="bg-primary hover:bg-emerald-500 text-secondary font-bold py-3 px-8 rounded-xl shadow-lg transition-transform hover:-translate-y-1">Crear Cuenta</button>
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
    const form = document.getElementById('userForm');
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

        if (!password.value || password.value.length < 6) {
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
            return;
        }

        e.preventDefault();

        fetch(`/admin/usuarios/check-email?email=${encodeURIComponent(email.value.trim())}`)
            .then(res => res.json())
            .then(data => {
                if (data.exists) {
                    errEmail.textContent = 'Este correo ya está registrado, cambia de correo de favor.';
                    errEmail.classList.remove('hidden');
                    email.classList.add('border-red-500');
                } else {
                    form.submit();
                }
            })
            .catch(() => {
                form.submit(); // Fallback on fetch failure
            });
    });
});
</script>
@endpush
