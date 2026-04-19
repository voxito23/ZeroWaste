<!DOCTYPE html>
<html lang="es" class="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ZeroWaste Admin - @yield('title', 'Dashboard')</title>
    <link href="/static/faviconZeroWaste.svg" rel="icon" type="image/svg+xml">
    <link rel="alternate icon" type="image/png" href="/static/faviconZeroWaste.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        primary: "#00E096",
                        secondary: "#064E3B",
                        accent: "#34D399",
                        'forest-dark': '#0B1F18',
                        'forest-card': '#0F2A20',
                    },
                    fontFamily: { sans: ['Inter', 'sans-serif'] }
                }
            }
        };
    </script>
    <style>
        /* Transiciones suaves para dark mode */
        * { transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease; }
        .nav-link { transition: all 0.2s ease; border-radius: 0.75rem; padding: 0.6rem 1rem; }
        .nav-link:hover { background: rgba(0, 224, 150, 0.1); transform: translateX(4px); }
        .nav-link.active { background: rgba(0, 224, 150, 0.15); color: #00E096; }
        /* Toggle dark mode switch */
        .theme-toggle { position: relative; width: 48px; height: 26px; border-radius: 9999px; cursor: pointer; }
        .theme-toggle input { display: none; }
        .theme-toggle .slider { position: absolute; inset: 0; background: #cbd5e1; border-radius: 9999px; transition: 0.3s; }
        .theme-toggle .slider::before { content: ''; position: absolute; height: 20px; width: 20px; left: 3px; bottom: 3px; background: white; border-radius: 50%; transition: 0.3s; }
        .theme-toggle input:checked + .slider { background: #00E096; }
        .theme-toggle input:checked + .slider::before { transform: translateX(22px); }
    </style>
    @stack('styles')
</head>
<body class="bg-[#ECFDF5] dark:bg-forest-dark text-[#064E3B] dark:text-emerald-100 font-sans flex min-h-screen transition-colors duration-300">

    <!-- Barra lateral de navegación -->
    <aside class="w-64 bg-white/95 dark:bg-forest-card border-r border-emerald-100 dark:border-emerald-900/50 shadow-xl flex flex-col hidden lg:flex">
        <div class="h-24 flex items-center justify-center border-b border-emerald-50 dark:border-emerald-900/50 gap-3">
            <img src="/static/img/logo.png" alt="ZeroWaste" class="h-10 w-auto" onerror="this.style.display='none'">
            <span class="font-extrabold text-xl text-secondary dark:text-white">ZEROWASTE</span>
        </div>
        <nav class="p-4 flex flex-col gap-1 flex-1">
            <a href="{{ route('dashboard') }}" class="nav-link flex items-center gap-3 font-bold text-secondary dark:text-emerald-200 {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <span class="material-symbols-outlined text-xl">dashboard</span> Dashboard
            </a>
            <a href="{{ route('usuarios.index') }}" class="nav-link flex items-center gap-3 font-bold text-secondary dark:text-emerald-200 {{ request()->routeIs('usuarios.*') ? 'active' : '' }}">
                <span class="material-symbols-outlined text-xl">group</span> Usuarios
            </a>
            <a href="{{ route('campanas.index') }}" class="nav-link flex items-center gap-3 font-bold text-secondary dark:text-emerald-200 {{ request()->routeIs('campanas.*') ? 'active' : '' }}">
                <span class="material-symbols-outlined text-xl">campaign</span> Campañas
            </a>
            <a href="{{ route('mapa.index') }}" class="nav-link flex items-center gap-3 font-bold text-secondary dark:text-emerald-200 {{ request()->routeIs('mapa.*') ? 'active' : '' }}">
                <span class="material-symbols-outlined text-xl">map</span> Mapa
            </a>
            <a href="{{ route('reportes.index') }}" class="nav-link flex items-center gap-3 font-bold text-secondary dark:text-emerald-200 {{ request()->routeIs('reportes.*') ? 'active' : '' }}">
                <span class="material-symbols-outlined text-xl">insert_chart</span> Reportes
            </a>


            <div class="border-t border-emerald-100 dark:border-emerald-900/50 my-3"></div>
            <span class="text-xs text-gray-400 dark:text-emerald-600 font-bold uppercase tracking-wider px-3 mb-1">Desarrollo</span>
            
            <a href="/docs" target="_blank" class="nav-link flex items-center gap-3 font-bold text-secondary dark:text-emerald-200">
                <span class="material-symbols-outlined text-xl">api</span> FastAPI Docs
            </a>

            <div class="border-t border-emerald-100 dark:border-emerald-900/50 my-3"></div>
            <span class="text-xs text-gray-400 dark:text-emerald-600 font-bold uppercase tracking-wider px-3 mb-1">Soporte</span>

            <a href="{{ route('mensajes.index') }}" class="nav-link flex items-center gap-3 font-bold text-secondary dark:text-emerald-200 {{ request()->routeIs('mensajes.*') ? 'active' : '' }}">
                <span class="material-symbols-outlined text-xl">mail</span> Mensajes
            </a>
            <a href="{{ route('recuperacion.index') }}" class="nav-link flex items-center gap-3 font-bold text-secondary dark:text-emerald-200 {{ request()->routeIs('recuperacion.*') ? 'active' : '' }}">
                <span class="material-symbols-outlined text-xl">lock_reset</span> Recuperación
            </a>

            <div class="mt-auto pt-4 border-t border-emerald-100 dark:border-emerald-900/50">
                <!-- Toggle Dark Mode -->
                <div class="nav-link flex items-center justify-between mb-2">
                    <div class="flex items-center gap-3 font-bold text-secondary dark:text-emerald-200 text-sm">
                        <span class="material-symbols-outlined text-xl" id="theme-icon">dark_mode</span>
                        <span id="theme-label">Modo Oscuro</span>
                    </div>
                    <label class="theme-toggle">
                        <input type="checkbox" id="theme-switch">
                        <span class="slider"></span>
                    </label>
                </div>

                <a href="{{ route('admin.logout') }}" class="nav-link flex items-center gap-3 font-bold text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20">
                    <span class="material-symbols-outlined text-xl">logout</span> Cerrar Sesión
                </a>
            </div>
        </nav>
    </aside>

    <main class="flex-1 p-4 md:p-6 lg:p-10 flex flex-col overflow-auto">
        <header class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-extrabold dark:text-white">@yield('page_title', 'Dashboard')</h1>
            <div class="flex items-center gap-3">
                @php
                    $isAuth = \Illuminate\Support\Facades\Auth::check();
                    $uName = $isAuth ? \Illuminate\Support\Facades\Auth::user()->nombre : 'Visitante';
                    $uRole = ($isAuth && \Illuminate\Support\Facades\Auth::user()->is_admin) ? 'Administrador' : 'Usuario';
                    $fotoPerfil = ($isAuth && \Illuminate\Support\Facades\Auth::user()->foto_perfil) ? \Illuminate\Support\Facades\Auth::user()->foto_perfil : 'default.png';
                $fotoUrl = url('/static/img/perfiles/' . $fotoPerfil);
                @endphp
                <div class="relative group cursor-pointer">
                    <div class="flex items-center gap-3">
                        <div class="text-right hidden sm:block">
                            <h3 class="text-sm font-bold text-secondary dark:text-emerald-100">{{ $uName }}</h3>
                            <p class="text-xs text-gray-400 dark:text-emerald-500">{{ $uRole }}</p>
                        </div>
                        <img src="{{ $fotoUrl }}" alt="Avatar"
                             class="w-11 h-11 rounded-full border-[3px] border-primary object-cover shadow-lg shadow-primary/20 transition-transform group-hover:scale-105"
                             onerror="this.onerror=null; this.src='/static/img/perfiles/default.png';">
                    </div>
                    
                    <!-- Dropdown Menu -->
                    <div class="absolute right-0 mt-2 w-48 bg-white dark:bg-forest-card rounded-2xl shadow-xl border border-emerald-50 dark:border-emerald-800/50 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-300 transform origin-top-right z-50 overflow-hidden">
                        <a href="{{ route('admin.perfil.edit') }}" class="flex items-center gap-2 px-4 py-3 text-sm font-bold text-secondary dark:text-emerald-100 hover:bg-emerald-50 dark:hover:bg-emerald-900/40 transition-colors">
                            <span class="material-symbols-outlined text-lg">manage_accounts</span>
                            Configuración
                        </a>
                        <a href="{{ route('admin.logout') }}" class="flex items-center gap-2 px-4 py-3 text-sm font-bold text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors border-t border-gray-50 dark:border-emerald-800/50">
                            <span class="material-symbols-outlined text-lg">logout</span>
                            Cerrar Sesión
                        </a>
                    </div>
                </div>
        </header>

        <div class="flex-1">
            @yield('content')
        </div>
    </main>

    <!-- Script Dark Mode -->
    <script>
        const themeSwitch = document.getElementById('theme-switch');
        const html = document.documentElement;
        const themeIcon = document.getElementById('theme-icon');
        const themeLabel = document.getElementById('theme-label');

        // Restaurar preferencia guardada
        if (localStorage.getItem('zw-admin-theme') === 'dark') {
            html.classList.add('dark');
            html.classList.remove('light');
            themeSwitch.checked = true;
            themeIcon.textContent = 'light_mode';
            themeLabel.textContent = 'Modo Claro';
        }

        themeSwitch.addEventListener('change', () => {
            if (themeSwitch.checked) {
                html.classList.add('dark');
                html.classList.remove('light');
                localStorage.setItem('zw-admin-theme', 'dark');
                themeIcon.textContent = 'light_mode';
                themeLabel.textContent = 'Modo Claro';
            } else {
                html.classList.remove('dark');
                html.classList.add('light');
                localStorage.setItem('zw-admin-theme', 'light');
                themeIcon.textContent = 'dark_mode';
                themeLabel.textContent = 'Modo Oscuro';
            }
        });

        // Reemplazar todos los onsubmit nativos confirmables por SweetAlert al cargar la página
        document.querySelectorAll('form[onsubmit*="confirm"]').forEach(form => {
            const onsubmitStr = form.getAttribute('onsubmit');
            const msgMatch = onsubmitStr.match(/confirm\(['"]([^'"]+)['"]\)/);
            const text = msgMatch ? msgMatch[1] : '¿Estás seguro de realizar esta acción?';
            
            // Eliminar el comportamiento nativo del navegador (la alerta de localhost)
            form.removeAttribute('onsubmit');
            
            // Asignar nuestro listener personalizado
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                const isDark = html.classList.contains('dark');
                
                Swal.fire({
                    title: 'Confirmación',
                    text: text,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#00E096',
                    cancelButtonColor: '#ef4444',
                    confirmButtonText: 'Sí, continuar',
                    cancelButtonText: 'Cancelar',
                    background: isDark ? '#0F2A20' : '#ffffff',
                    color: isDark ? '#d1fae5' : '#064E3B',
                    customClass: {
                        popup: 'rounded-3xl border border-emerald-100 dark:border-emerald-800/50 shadow-2xl',
                        confirmButton: 'rounded-xl',
                        cancelButton: 'rounded-xl'
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });
        });

        // Intercepción de enlaces de edición
        document.addEventListener('click', function(e) {
            const link = e.target.closest('a[href*="/edit"]');
            if (link && !link.hasAttribute('data-confirmed')) {
                e.preventDefault();
                const url = link.getAttribute('href');
                const isDark = html.classList.contains('dark');
                
                Swal.fire({
                    title: '¿Editar registro?',
                    text: '¿Estás seguro de que deseas editar este elemento?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#00E096',
                    cancelButtonColor: '#ef4444',
                    confirmButtonText: 'Sí, editar',
                    cancelButtonText: 'Cancelar',
                    background: isDark ? '#0F2A20' : '#ffffff',
                    color: isDark ? '#d1fae5' : '#064E3B',
                    customClass: {
                        popup: 'rounded-3xl border border-emerald-100 dark:border-emerald-800/50 shadow-2xl',
                        confirmButton: 'rounded-xl',
                        cancelButton: 'rounded-xl'
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = url;
                    }
                });
            }
        });
    </script>
    
    @php
        $swalBg = "document.documentElement.classList.contains('dark') ? '#0F2A20' : '#ffffff'";
        $swalColor = "document.documentElement.classList.contains('dark') ? '#d1fae5' : '#064E3B'";
    @endphp

    @if(session('success'))
        <script>
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: "{!! session('success') !!}",
                showConfirmButton: false,
                timer: 3500,
                timerProgressBar: true,
                background: eval({!! json_encode($swalBg) !!}),
                color: eval({!! json_encode($swalColor) !!})
            });
        </script>
    @endif

    @if(session('error'))
        <script>
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'error',
                title: "{!! session('error') !!}",
                showConfirmButton: false,
                timer: 4500,
                timerProgressBar: true,
                background: eval({!! json_encode($swalBg) !!}),
                color: eval({!! json_encode($swalColor) !!})
            });
        </script>
    @endif

    @if(session('error_admin'))
        <script>
            {!! "const isDarkTheme = document.documentElement.classList.contains('dark');" !!}
            Swal.fire({
                icon: 'error',
                title: 'Acceso Denegado',
                text: '{!! session("error_admin") !!}',
                footer: 'Contáctanos a <b>soporte@zerowaste-qro.com</b> para dar seguimiento a tu caso.',
                confirmButtonColor: '#059669',
                background: isDarkTheme ? '#0F2A20' : '#ffffff',
                color: isDarkTheme ? '#d1fae5' : '#064E3B',
                customClass: {
                    popup: 'rounded-[2rem] border border-emerald-100 dark:border-emerald-800/50 shadow-2xl',
                    confirmButton: 'rounded-xl font-bold px-6 py-2.5',
                    footer: 'border-t border-emerald-50 dark:border-emerald-800/50 text-gray-500 dark:text-gray-400'
                }
            });
        </script>
    @endif

    @stack('scripts')
</body>
</html>
