@extends('layouts.admin')

@section('title', 'Mi Perfil')
@section('page_title', 'Ajustes de Perfil')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="bg-white dark:bg-forest-card rounded-[2rem] border-2 border-emerald-100 dark:border-emerald-800/50 shadow-sm p-8 hover:shadow-2xl transition-all duration-500">
        
        @if ($errors->any())
            <div class="mb-6 bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 p-4 rounded-xl border border-red-200 dark:border-red-800/50">
                <ul class="list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li class="font-bold text-sm">{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form id="perfilForm" novalidate action="{{ route('admin.perfil.update') }}" method="POST" enctype="multipart/form-data" class="space-y-8">
            @csrf
            @method('PUT')

            <div class="flex flex-col md:flex-row items-center gap-8 mb-8 pb-8 border-b border-emerald-50 dark:border-emerald-800/50">
                @php
                    $isAuth = \Illuminate\Support\Facades\Auth::check();
                    $fotoPerfil = ($isAuth && \Illuminate\Support\Facades\Auth::user()->foto_perfil) ? \Illuminate\Support\Facades\Auth::user()->foto_perfil : 'default.png';
                    $fotoUrl = url('/static/img/perfiles/' . $fotoPerfil);
                @endphp
                
                <div class="relative group">
                    <img id="avatar-preview" src="{{ $fotoUrl }}" alt="Mi Foto" class="w-32 h-32 rounded-full border-4 border-primary object-cover shadow-xl group-hover:scale-105 transition-transform duration-300">
                    <label for="foto_perfil" class="absolute bottom-0 right-0 bg-secondary hover:bg-forest-dark text-white p-3 rounded-full cursor-pointer shadow-lg transform hover:scale-110 transition-all">
                        <span class="material-symbols-outlined text-sm">edit</span>
                    </label>
                    <input type="file" id="foto_perfil" name="foto_perfil" accept="image/*" class="hidden" onchange="previewImage(this)">
                </div>
                
                <div>
                    <h3 class="text-2xl font-black text-secondary dark:text-emerald-100">{{ $isAuth ? \Illuminate\Support\Facades\Auth::user()->nombre : 'Admin' }}</h3>
                    <p class="text-gray-500 dark:text-gray-400 font-medium">Administrador Principal</p>
                    <p class="text-xs text-primary font-bold mt-2">La foto seleccionada se recortará en formato redondo.</p>
                </div>
            </div>

            <div>
                <h4 class="font-bold text-lg text-secondary dark:text-emerald-100 mb-4 flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary">lock_reset</span>
                    Cambiar Contraseña
                </h4>
                <div class="p-6 bg-emerald-50/50 dark:bg-emerald-900/20 rounded-2xl border border-emerald-100 dark:border-emerald-800/50 space-y-5">
                    <p class="text-sm text-gray-500 dark:text-gray-400 font-medium mb-4">Déjalo en blanco si solo deseas actualizar la foto de perfil.</p>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-bold text-secondary dark:text-emerald-100 mb-2">Contraseña Anterior</label>
                            <div class="relative">
                                <input type="password" id="admin-password-current" name="password_actual" placeholder="Ingresa tu contraseña actual" class="w-full px-4 py-3 pr-12 rounded-xl bg-white dark:bg-black/20 border-2 border-emerald-100 dark:border-emerald-700 focus:border-primary dark:focus:border-primary focus:ring-4 focus:ring-primary/10 text-secondary dark:text-white outline-none transition-all">
                                <button type="button" onclick="togglePass('admin-password-current', this)" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-primary transition-colors">
                                    <span class="material-symbols-outlined">visibility_off</span>
                                </button>
                            </div>
                            @error('password_actual')
                                <span class="text-red-500 text-sm mt-1 font-medium">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-bold text-secondary dark:text-emerald-100 mb-2">Nueva Contraseña</label>
                                <div class="relative">
                                    <input type="password" id="admin-password" name="password" placeholder="Mínimo 6 caracteres" class="w-full px-4 py-3 pr-12 rounded-xl bg-white dark:bg-black/20 border-2 border-emerald-100 dark:border-emerald-700 focus:border-primary dark:focus:border-primary focus:ring-4 focus:ring-primary/10 text-secondary dark:text-white outline-none transition-all">
                                    <button type="button" onclick="togglePass('admin-password', this)" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-primary transition-colors">
                                        <span class="material-symbols-outlined">visibility_off</span>
                                    </button>
                                </div>
                                <span id="err-password" class="hidden text-red-500 text-sm mt-1 font-medium"></span>
                            </div>

                            <div>
                                <label class="block text-sm font-bold text-secondary dark:text-emerald-100 mb-2">Confirmar Contraseña</label>
                                <div class="relative">
                                    <input type="password" id="admin-password-confirm" name="password_confirmation" placeholder="Repite la contraseña" class="w-full px-4 py-3 pr-12 rounded-xl bg-white dark:bg-black/20 border-2 border-emerald-100 dark:border-emerald-700 focus:border-primary dark:focus:border-primary focus:ring-4 focus:ring-primary/10 text-secondary dark:text-white outline-none transition-all">
                                    <button type="button" onclick="togglePass('admin-password-confirm', this)" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-primary transition-colors">
                                        <span class="material-symbols-outlined">visibility_off</span>
                                    </button>
                                </div>
                                <span id="err-password-confirm" class="hidden text-red-500 text-sm mt-1 font-medium"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-4">
                <a href="{{ route('dashboard') }}" class="px-6 py-3 rounded-xl text-gray-500 dark:text-gray-400 font-bold hover:bg-gray-100 dark:hover:bg-white/5 transition-colors">Volver</a>
                <button type="submit" class="px-8 py-3 bg-primary hover:bg-[#00c281] text-secondary font-black rounded-xl shadow-lg shadow-primary/30 transition-all hover:-translate-y-1 flex items-center gap-2">
                    <span class="material-symbols-outlined text-xl">save</span>
                    Guardar Cambios
                </button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
    function previewImage(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('avatar-preview').src = e.target.result;
            }
            reader.readAsDataURL(input.files[0]);
        }
    }
    function togglePass(id, btn) {
        const inp = document.getElementById(id);
        const ico = btn.querySelector('.material-symbols-outlined');
        if (inp.type === 'password') { inp.type = 'text'; ico.textContent = 'visibility'; }
        else { inp.type = 'password'; ico.textContent = 'visibility_off'; }
    }

    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('perfilForm');
        if (!form) return;

        form.addEventListener('submit', function(e) {
            const current = document.getElementById('admin-password-current');
            const password = document.getElementById('admin-password');
            const confirm = document.getElementById('admin-password-confirm');
            const errPass = document.getElementById('err-password');
            const errConfirm = document.getElementById('err-password-confirm');

            // Reset
            errPass.classList.add('hidden');
            errConfirm.classList.add('hidden');
            if (current) current.classList.remove('border-red-500');
            password.classList.remove('border-red-500');
            confirm.classList.remove('border-red-500');

            let isValid = true;

            // Solo validar si se ingresó algo en alguno de los campos de nueva contraseña
            if (password.value || confirm.value) {
                // frontend client-side required current password check
                if (!current.value) {
                    current.classList.add('border-red-500');
                    isValid = false;
                }

                if (password.value.length < 6) {
                    errPass.textContent = 'La contrase\u00f1a debe tener al menos 6 caracteres.';
                    errPass.classList.remove('hidden');
                    password.classList.add('border-red-500');
                    isValid = false;
                }

                if (password.value !== confirm.value) {
                    errConfirm.textContent = 'Las contrase\u00f1as no coinciden.';
                    errConfirm.classList.remove('hidden');
                    confirm.classList.add('border-red-500');
                    isValid = false;
                }
            }

            if (!isValid) {
                e.preventDefault();
            }
        });
    });
</script>
@endpush
