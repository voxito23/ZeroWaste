<!DOCTYPE html>
<html lang="es">
<script>
    // Prevent dark mode flash — apply theme before paint
    (function() {
        var t = localStorage.getItem('zw-admin-theme');
        if (t === 'dark') {
            document.documentElement.classList.add('dark');
            document.documentElement.classList.remove('light');
        } else {
            document.documentElement.classList.add('light');
            document.documentElement.classList.remove('dark');
        }
    })();
</script>
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
        .nav-link { 
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); 
            border-radius: 0.85rem; 
            padding: 0.7rem 1rem; 
            position: relative;
            overflow: hidden;
        }
        /* Efecto Ripple en clic */
        .nav-link::after {
            content: '';
            position: absolute;
            background: rgba(0, 224, 150, 0.3);
            border-radius: 50%;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            transform: translate(-50%, -50%);
            transition: width 0.4s ease-out, height 0.4s ease-out, opacity 0.4s ease-out;
            opacity: 0;
        }
        .nav-link:active::after {
            width: 300px;
            height: 300px;
            opacity: 1;
            transition: 0s;
        }
        .nav-link:hover { 
            background: rgba(0, 224, 150, 0.08); 
            transform: translateX(6px); 
        }
        .nav-link.active { 
            background: rgba(0, 224, 150, 0.15); 
            color: #00E096; 
            transform: scale(1.02);
            box-shadow: inset 4px 0 0 #00E096;
        }
        /* Page Entry Animation */
        .fade-in-up {
            animation: fadeInUp 0.5s cubic-bezier(0.4, 0, 0.2, 1) forwards;
            opacity: 0;
            transform: translateY(20px);
        }
        @keyframes fadeInUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
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
    <aside id="sidebar" class="w-64 bg-white/95 dark:bg-forest-card border-r border-emerald-100 dark:border-emerald-900/50 shadow-xl flex flex-col hidden lg:flex transition-all duration-300 ease-in-out relative">
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
            
            @if(\Illuminate\Support\Facades\Auth::check() && \Illuminate\Support\Facades\Auth::user()->email === 'vichdz@gmail.com')
                <span class="text-xs text-gray-400 dark:text-emerald-600 font-bold uppercase tracking-wider px-3 mb-1">Desarrollo</span>
                
                <a href="/docs" target="_blank" class="nav-link flex items-center gap-3 font-bold text-secondary dark:text-emerald-200">
                    <span class="material-symbols-outlined text-xl">api</span> FastAPI Docs
                </a>

                <a href="/" target="_blank" class="nav-link flex items-center gap-3 font-bold text-secondary dark:text-emerald-200">
                    <span class="material-symbols-outlined text-xl">storefront</span> ZeroWaste Cliente
                </a>

                <div class="border-t border-emerald-100 dark:border-emerald-900/50 my-3"></div>
            @endif

            <span class="text-xs text-gray-400 dark:text-emerald-600 font-bold uppercase tracking-wider px-3 mb-1">Soporte</span>

            <a href="{{ route('mensajes.index') }}" class="nav-link flex items-center gap-3 font-bold text-secondary dark:text-emerald-200 {{ request()->routeIs('mensajes.*') ? 'active' : '' }}">
                <span class="material-symbols-outlined text-xl">mail</span> Mensajes
            </a>
            <a href="{{ route('recuperacion.index') }}" class="nav-link flex items-center gap-3 font-bold text-secondary dark:text-emerald-200 {{ request()->routeIs('recuperacion.*') ? 'active' : '' }}">
                <span class="material-symbols-outlined text-xl">lock_reset</span> Recuperación
            </a>

            <div class="mt-auto pt-4 border-t border-emerald-100 dark:border-emerald-900/50">
                <!-- Toggle Dark Mode -->
                <!-- Toggle Dark Mode -->
                <button type="button" class="nav-link flex items-center justify-between w-full text-left mb-2 group" id="theme-switch-btn">
                    <div class="flex items-center gap-3 font-bold text-secondary dark:text-emerald-200 text-sm">
                        <span class="material-symbols-outlined text-xl dark:hidden text-secondary group-hover:text-primary transition-colors">dark_mode</span>
                        <span class="material-symbols-outlined text-xl hidden dark:block text-primary">light_mode</span>
                        <span class="dark:hidden">Modo Oscuro</span>
                        <span class="hidden dark:inline">Modo Claro</span>
                    </div>
                </button>

                <a href="{{ route('admin.logout') }}" class="nav-link flex items-center gap-3 font-bold text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20">
                    <span class="material-symbols-outlined text-xl">logout</span> Cerrar Sesión
                </a>
            </div>
        </nav>
    </aside>

    <main class="flex-1 p-4 md:p-6 lg:p-10 flex flex-col overflow-auto w-full transition-all duration-300">
        <header class="flex justify-between items-center mb-8">
            <div class="flex items-center gap-4">
                <button id="sidebar-toggle" class="p-2 rounded-xl bg-white dark:bg-forest-card shadow-sm border border-emerald-100 dark:border-emerald-800/50 text-secondary dark:text-emerald-200 hover:bg-emerald-50 dark:hover:bg-emerald-900/40 transition-colors hidden lg:flex">
                    <span class="material-symbols-outlined transition-transform duration-300" id="sidebar-icon">menu_open</span>
                </button>
                <h1 class="text-3xl font-extrabold dark:text-white truncate">@yield('page_title', 'Dashboard')</h1>
            </div>
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

        <div class="flex-1 fade-in-up">
            @yield('content')
        </div>
    </main>

    <!-- Script Dark Mode -->
    <script>
        const themeBtn = document.getElementById('theme-switch-btn');
        const html = document.documentElement;

        // Restaurar preferencia guardada
        if (localStorage.getItem('zw-admin-theme') === 'dark') {
            html.classList.add('dark');
            html.classList.remove('light');
        }

        // Sidebar Toggle Logic
        const sidebar = document.getElementById('sidebar');
        const sidebarToggle = document.getElementById('sidebar-toggle');
        const sidebarIcon = document.getElementById('sidebar-icon');

        if (sidebarToggle && sidebar) {
            // Restore state
            if (localStorage.getItem('zw-sidebar-collapsed') === 'true') {
                sidebar.classList.add('-ml-64');
                sidebarIcon.textContent = 'menu';
                sidebarIcon.style.transform = 'scaleX(-1)';
            } else {
                sidebarIcon.style.transform = 'scaleX(1)';
            }

            sidebarToggle.addEventListener('click', () => {
                sidebar.classList.toggle('-ml-64');
                const isCollapsed = sidebar.classList.contains('-ml-64');
                localStorage.setItem('zw-sidebar-collapsed', isCollapsed);
                
                if (isCollapsed) {
                    sidebarIcon.textContent = 'menu';
                    sidebarIcon.style.transform = 'scaleX(-1)';
                } else {
                    sidebarIcon.textContent = 'menu_open';
                    sidebarIcon.style.transform = 'scaleX(1)';
                }
            });
        }

        if (themeBtn) {
            themeBtn.addEventListener('click', () => {
                html.classList.toggle('dark');
                if (html.classList.contains('dark')) {
                    html.classList.remove('light');
                    localStorage.setItem('zw-admin-theme', 'dark');
                } else {
                    html.classList.add('light');
                    localStorage.setItem('zw-admin-theme', 'light');
                }
            });
        }

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
                    html: `<div class="text-center">
                        <div class="w-20 h-20 rounded-full mx-auto mb-5 flex items-center justify-center" style="background: linear-gradient(135deg, rgba(239,68,68,0.1), rgba(239,68,68,0.2)); border: 2px solid rgba(239,68,68,0.2);">
                            <span class="material-symbols-outlined text-red-500" style="font-size: 36px;">delete_forever</span>
                        </div>
                        <h3 style="font-size: 1.3rem; font-weight: 900; margin-bottom: 8px;">¿Eliminar registro?</h3>
                        <p style="font-size: 0.875rem; opacity: 0.7;">${text}</p>
                    </div>`,
                    showCancelButton: true,
                    confirmButtonColor: '#EF4444',
                    cancelButtonColor: isDark ? '#1a3a2d' : '#E5E7EB',
                    confirmButtonText: '<span class="font-bold flex items-center gap-2"><span class="material-symbols-outlined text-base">delete</span>Eliminar</span>',
                    cancelButtonText: '<span class="font-bold">Cancelar</span>',
                    background: isDark ? '#0F2A20' : '#ffffff',
                    color: isDark ? '#d1fae5' : '#064E3B',
                    width: 380,
                    customClass: {
                        popup: 'rounded-[2rem] border shadow-2xl',
                        confirmButton: 'rounded-full px-6 py-2.5',
                        cancelButton: 'rounded-full px-6 py-2.5'
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
                    html: `<div class="text-center">
                        <div class="w-20 h-20 rounded-full mx-auto mb-5 flex items-center justify-center" style="background: linear-gradient(135deg, rgba(59,130,246,0.1), rgba(59,130,246,0.2)); border: 2px solid rgba(59,130,246,0.2);">
                            <span class="material-symbols-outlined text-blue-500" style="font-size: 36px;">edit_note</span>
                        </div>
                        <h3 style="font-size: 1.3rem; font-weight: 900; margin-bottom: 8px;">¿Editar registro?</h3>
                        <p style="font-size: 0.875rem; opacity: 0.7;">Serás redirigido al formulario de edición.</p>
                    </div>`,
                    showCancelButton: true,
                    confirmButtonColor: '#3B82F6',
                    cancelButtonColor: isDark ? '#1a3a2d' : '#E5E7EB',
                    confirmButtonText: '<span class="font-bold flex items-center gap-2"><span class="material-symbols-outlined text-base">edit</span>Editar</span>',
                    cancelButtonText: '<span class="font-bold">Cancelar</span>',
                    background: isDark ? '#0F2A20' : '#ffffff',
                    color: isDark ? '#d1fae5' : '#064E3B',
                    width: 380,
                    customClass: {
                        popup: 'rounded-[2rem] border shadow-2xl',
                        confirmButton: 'rounded-full px-6 py-2.5',
                        cancelButton: 'rounded-full px-6 py-2.5'
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
