<!DOCTYPE html>
<html lang="es">
<script>
    // Prevenir destello del modo oscuro — aplicar tema antes de renderizar
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
        * { transition: background-color 0.25s ease, color 0.25s ease, border-color 0.25s ease; }
        body { background: linear-gradient(135deg, #f0fdf4 0%, #ecfdf5 40%, #e0f2fe 100%); }
        .dark body, body.dark { background: #0B1F18; }
        .nav-link {
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            border-radius: 0.75rem;
            padding: 0.6rem 0.85rem;
            position: relative;
            font-size: 13px;
        }
        .nav-link:hover { background: rgba(16, 185, 129, 0.08); transform: translateX(3px); }
        .nav-link.active {
            background: linear-gradient(135deg, rgba(16,185,129,0.12), rgba(16,185,129,0.06));
            color: #10B981;
            font-weight: 800;
            box-shadow: inset 3px 0 0 #10B981;
        }
        .dark .nav-link.active { background: linear-gradient(135deg, rgba(16,185,129,0.15), rgba(16,185,129,0.05)); color: #34D399; }
        /* Sidebar collapsed */
        #sidebar.collapsed { width: 70px !important; min-width: 70px; }
        #sidebar.collapsed .nav-text,
        #sidebar.collapsed .nav-section-label,
        #sidebar.collapsed .sidebar-brand-text { display: none; }
        #sidebar.collapsed .nav-link { justify-content: center; padding: 0.65rem; }
        #sidebar.collapsed .nav-link .material-symbols-outlined { font-size: 22px; }
        #sidebar.collapsed .sidebar-logo-wrap { justify-content: center; padding: 0 0.5rem; }
        #sidebar.collapsed .nav-link:hover { transform: none; }
        .fade-in-up { animation: fadeInUp 0.5s cubic-bezier(0.4,0,0.2,1) forwards; opacity:0; transform:translateY(20px); }
        @keyframes fadeInUp { to { opacity:1; transform:translateY(0); } }
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: rgba(16,185,129,0.2); border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: rgba(16,185,129,0.35); }
        /* Shared Premium Components */
        .glass-card { background:rgba(255,255,255,0.85); backdrop-filter:blur(20px); border:1px solid rgba(0,0,0,0.04); border-radius:1.25rem; transition:all .3s ease; }
        .dark .glass-card { background:rgba(15,42,32,0.7); border-color:rgba(255,255,255,0.05); }
        .glass-card:hover { box-shadow:0 12px 40px rgba(0,0,0,0.06); }
        .dark .glass-card:hover { box-shadow:0 12px 40px rgba(0,0,0,0.25); }
        .premium-table { width:100%; text-align:left; font-size:0.8125rem; border-collapse:separate; border-spacing:0; }
        .premium-table thead { font-size:0.6875rem; text-transform:uppercase; letter-spacing:0.05em; font-weight:700; }
        .premium-table thead th { padding:0.75rem 1rem; color:#6b7280; border-bottom:1px solid rgba(0,0,0,0.05); }
        .dark .premium-table thead th { color:#6b7280; border-color:rgba(255,255,255,0.05); }
        .premium-table tbody tr { transition: background .2s ease; }
        .premium-table tbody tr:hover { background:rgba(16,185,129,0.03); }
        .dark .premium-table tbody tr:hover { background:rgba(255,255,255,0.02); }
        .premium-table tbody td { padding:0.75rem 1rem; border-bottom:1px solid rgba(0,0,0,0.03); }
        .dark .premium-table tbody td { border-color:rgba(255,255,255,0.03); }
        .badge-sm { display:inline-flex; align-items:center; gap:4px; padding:3px 10px; border-radius:8px; font-size:10px; font-weight:700; }
        .filter-btn { display:flex; align-items:center; gap:6px; padding:8px 14px; border-radius:10px; font-size:13px; font-weight:600; background:rgba(255,255,255,0.85); backdrop-filter:blur(12px); border:1px solid rgba(0,0,0,0.06); cursor:pointer; transition:all .2s; }
        .dark .filter-btn { background:rgba(15,42,32,0.7); border-color:rgba(255,255,255,0.06); color:#d1fae5; }
        .filter-btn:hover { border-color:rgba(16,185,129,0.3); box-shadow:0 4px 12px rgba(0,0,0,0.05); }
        .filter-dropdown { position:absolute; top:calc(100% + 6px); left:0; min-width:200px; background:rgba(255,255,255,0.95); backdrop-filter:blur(20px); border:1px solid rgba(0,0,0,0.06); border-radius:14px; padding:6px; z-index:50; box-shadow:0 20px 50px rgba(0,0,0,0.1); }
        .dark .filter-dropdown { background:rgba(15,42,32,0.95); border-color:rgba(255,255,255,0.08); }
        .filter-dropdown button { display:flex; align-items:center; gap:8px; width:100%; padding:8px 12px; border-radius:10px; font-size:13px; font-weight:600; text-align:left; transition:all .15s; border:none; background:none; cursor:pointer; color:inherit; }
        .filter-dropdown button:hover { background:rgba(16,185,129,0.08); }
        .filter-dropdown button.active-item { background:rgba(16,185,129,0.12); color:#10B981; font-weight:700; }
        .input-premium { padding:9px 14px; border-radius:10px; font-size:13px; font-weight:500; background:rgba(255,255,255,0.85); backdrop-filter:blur(12px); border:1px solid rgba(0,0,0,0.08); transition:all .2s; outline:none; width:100%; }
        .dark .input-premium { background:rgba(15,42,32,0.7); border-color:rgba(255,255,255,0.08); color:#d1fae5; }
        .input-premium:focus { border-color:#10B981; box-shadow:0 0 0 3px rgba(16,185,129,0.1); }
        .btn-primary { display:inline-flex; align-items:center; gap:6px; padding:9px 18px; border-radius:10px; font-size:13px; font-weight:700; background:linear-gradient(135deg,#10B981,#059669); color:#fff; border:none; cursor:pointer; transition:all .25s; box-shadow:0 4px 14px rgba(16,185,129,0.3); }
        .btn-primary:hover { transform:translateY(-1px); box-shadow:0 6px 20px rgba(16,185,129,0.4); }
        .btn-secondary { display:inline-flex; align-items:center; gap:6px; padding:9px 18px; border-radius:10px; font-size:13px; font-weight:700; background:rgba(255,255,255,0.85); backdrop-filter:blur(12px); border:1px solid rgba(0,0,0,0.08); cursor:pointer; transition:all .2s; color:#064E3B; }
        .dark .btn-secondary { background:rgba(15,42,32,0.7); border-color:rgba(255,255,255,0.08); color:#d1fae5; }
        .btn-secondary:hover { border-color:rgba(16,185,129,0.3); }
        .page-header { display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:1rem; margin-bottom:1.5rem; }
        .page-header h2 { font-size:1.5rem; font-weight:900; letter-spacing:-0.02em; }
        .dark .page-header h2 { color:#fff; }
    </style>
    @stack('styles')
</head>
<body class="dark:bg-forest-dark text-[#064E3B] dark:text-emerald-100 font-sans flex min-h-screen transition-colors duration-300">

    <!-- Barra lateral de navegación -->
    <aside id="sidebar" class="w-[250px] bg-white/80 dark:bg-forest-card/90 backdrop-blur-xl border-r border-gray-200/50 dark:border-emerald-900/30 flex flex-col hidden lg:flex transition-all duration-300 ease-in-out relative">
        <div class="h-16 flex items-center px-5 border-b border-gray-100 dark:border-emerald-900/30 gap-2.5 sidebar-logo-wrap">
            <img src="/static/img/logo.png" alt="ZeroWaste" class="h-8 w-auto" onerror="this.style.display='none'">
            <span class="font-black text-base text-secondary dark:text-white tracking-tight sidebar-brand-text">ZeroWaste</span>
        </div>
        <nav class="p-3 flex flex-col gap-0.5 flex-1 overflow-y-auto">
            <a href="{{ route('dashboard') }}" class="nav-link flex items-center gap-3 font-bold text-sm text-secondary dark:text-emerald-200 {{ request()->routeIs('dashboard') ? 'active' : '' }}" title="Dashboard">
                <span class="material-symbols-outlined text-lg">dashboard</span> <span class="nav-text">Dashboard</span>
            </a>
            <a href="{{ route('usuarios.index') }}" class="nav-link flex items-center gap-3 font-bold text-sm text-secondary dark:text-emerald-200 {{ request()->routeIs('usuarios.*') ? 'active' : '' }}" title="Usuarios">
                <span class="material-symbols-outlined text-lg">group</span> <span class="nav-text">Usuarios</span>
            </a>
            <a href="{{ route('campanas.index') }}" class="nav-link flex items-center gap-3 font-bold text-sm text-secondary dark:text-emerald-200 {{ request()->routeIs('campanas.*') ? 'active' : '' }}" title="Campañas">
                <span class="material-symbols-outlined text-lg">campaign</span> <span class="nav-text">Campañas</span>
            </a>
            <a href="{{ route('mapa.index') }}" class="nav-link flex items-center gap-3 font-bold text-sm text-secondary dark:text-emerald-200 {{ request()->routeIs('mapa.*') ? 'active' : '' }}" title="Mapa">
                <span class="material-symbols-outlined text-lg">map</span> <span class="nav-text">Mapa</span>
            </a>
            <a href="{{ route('reportes.index') }}" class="nav-link flex items-center gap-3 font-bold text-sm text-secondary dark:text-emerald-200 {{ request()->routeIs('reportes.*') ? 'active' : '' }}" title="Reportes">
                <span class="material-symbols-outlined text-lg">insert_chart</span> <span class="nav-text">Reportes</span>
            </a>

            <div class="border-t border-emerald-100 dark:border-emerald-900/50 my-3"></div>
            
            @if(\Illuminate\Support\Facades\Auth::check() && \Illuminate\Support\Facades\Auth::user()->email === 'vichdz@gmail.com')
                <span class="text-[10px] text-gray-400 dark:text-emerald-600 font-bold uppercase tracking-wider px-3 mb-1 mt-2 nav-section-label">Desarrollo</span>
                
                <a href="/docs" target="_blank" class="nav-link flex items-center gap-3 font-bold text-sm text-secondary dark:text-emerald-200" title="FastAPI Docs">
                    <span class="material-symbols-outlined text-lg">api</span> <span class="nav-text">FastAPI Docs</span>
                </a>

                <a href="/" target="_blank" class="nav-link flex items-center gap-3 font-bold text-sm text-secondary dark:text-emerald-200" title="ZeroWaste Cliente">
                    <span class="material-symbols-outlined text-lg">storefront</span> <span class="nav-text">ZeroWaste Cliente</span>
                </a>
                
                <div class="border-t border-emerald-100 dark:border-emerald-900/50 my-3"></div>
            @endif

            <span class="text-[10px] text-gray-400 dark:text-emerald-600 font-bold uppercase tracking-wider px-3 mb-1 mt-2 nav-section-label">Soporte</span>
            <a href="{{ route('mensajes.index') }}" class="nav-link flex items-center gap-3 font-bold text-sm text-secondary dark:text-emerald-200 {{ request()->routeIs('mensajes.*') ? 'active' : '' }}" title="Mensajes">
                <span class="material-symbols-outlined text-lg">mail</span> <span class="nav-text">Mensajes</span>
            </a>
            <a href="{{ route('recuperacion.index') }}" class="nav-link flex items-center gap-3 font-bold text-sm text-secondary dark:text-emerald-200 {{ request()->routeIs('recuperacion.*') ? 'active' : '' }}" title="Recuperación">
                <span class="material-symbols-outlined text-lg">restore</span> <span class="nav-text">Recuperación</span>
            </a>

            <div class="mt-auto pt-4 border-t border-emerald-100 dark:border-emerald-900/50">
                <button type="button" class="nav-link flex items-center justify-between w-full text-left mb-2 group" id="theme-switch-btn" title="Cambiar tema">
                    <div class="flex items-center gap-3 font-bold text-secondary dark:text-emerald-200 text-sm">
                        <span class="material-symbols-outlined text-xl dark:hidden text-secondary group-hover:text-primary transition-colors">dark_mode</span>
                        <span class="material-symbols-outlined text-xl hidden dark:block text-primary">light_mode</span>
                        <span class="dark:hidden nav-text">Modo Oscuro</span>
                        <span class="hidden dark:inline nav-text">Modo Claro</span>
                    </div>
                </button>

                <a href="{{ route('admin.logout') }}" class="nav-link flex items-center gap-3 font-bold text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20" title="Cerrar Sesión">
                    <span class="material-symbols-outlined text-xl">logout</span> <span class="nav-text">Cerrar Sesión</span>
                </a>
            </div>
        </nav>
    </aside>

    <main class="flex-1 p-4 md:p-6 lg:p-8 flex flex-col overflow-auto w-full transition-all duration-300">
        <header class="flex justify-between items-center mb-6">
            <div class="flex items-center gap-3">
                <button id="sidebar-toggle" class="p-2 rounded-xl bg-white/80 dark:bg-forest-card/80 backdrop-blur-sm border border-gray-200/50 dark:border-emerald-800/30 text-secondary dark:text-emerald-200 hover:bg-emerald-50 dark:hover:bg-emerald-900/40 transition-colors hidden lg:flex">
                    <span class="material-symbols-outlined transition-transform duration-300" id="sidebar-icon">menu_open</span>
                </button>
                <h1 class="text-2xl font-black dark:text-white truncate tracking-tight">@yield('page_title', 'Dashboard')</h1>
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
                    
                    <!-- Menú Desplegable -->
                    <div class="absolute right-0 mt-2 w-48 bg-white/95 dark:bg-forest-card/95 backdrop-blur-xl rounded-2xl shadow-2xl border border-gray-200/50 dark:border-emerald-800/30 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-300 transform origin-top-right z-50 overflow-hidden">
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

        // Lógica de alternancia de la barra lateral
        const sidebar = document.getElementById('sidebar');
        const sidebarToggle = document.getElementById('sidebar-toggle');
        const sidebarIcon = document.getElementById('sidebar-icon');

        if (sidebarToggle && sidebar) {
            if (localStorage.getItem('zw-sidebar-collapsed') === 'true') {
                sidebar.classList.add('collapsed');
                sidebarIcon.textContent = 'menu';
            }

            sidebarToggle.addEventListener('click', () => {
                sidebar.classList.toggle('collapsed');
                const isCollapsed = sidebar.classList.contains('collapsed');
                localStorage.setItem('zw-sidebar-collapsed', isCollapsed);
                sidebarIcon.textContent = isCollapsed ? 'menu' : 'menu_open';
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
                    cancelButtonText: `<span class="font-bold" style="color: ${isDark ? '#d1fae5' : '#1f2937'};">Cancelar</span>`,
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
                    cancelButtonText: `<span class="font-bold" style="color: ${isDark ? '#d1fae5' : '#1f2937'};">Cancelar</span>`,
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
    
    @if(session('success'))
        <script>
            {!! "const isDarkThemeToast = document.documentElement.classList.contains('dark');" !!}
            Swal.fire({
                toast: true,
                position: 'top',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: false,
                background: isDarkThemeToast ? '#0F2A20' : '#ffffff',
                color: isDarkThemeToast ? '#d1fae5' : '#064E3B',
                customClass: {
                    popup: 'rounded-full border border-emerald-100 dark:border-emerald-800/50 shadow-2xl mt-4 px-2 py-1',
                },
                showClass: { popup: 'animate__animated animate__fadeInDown animate__faster' },
                hideClass: { popup: 'animate__animated animate__fadeOutUp animate__faster' },
                html: `
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full bg-[#00E096] shadow-[0_0_15px_rgba(0,224,150,0.5)] flex items-center justify-center text-[#064E3B] shrink-0">
                        <span class="material-symbols-outlined text-[18px] font-black">done</span>
                    </div>
                    <div class="text-left pr-4">
                        <h4 class="text-[14px] font-black m-0 leading-none tracking-tight">{!! session('success') !!}</h4>
                    </div>
                </div>`
            });
        </script>
    @endif

    @if(session('error'))
        <script>
            {!! "const isDarkThemeErr = document.documentElement.classList.contains('dark');" !!}
            Swal.fire({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 4500,
                timerProgressBar: true,
                background: isDarkThemeErr ? '#0F2A20' : '#ffffff',
                customClass: {
                    popup: 'rounded-2xl border shadow-xl border-red-100 dark:border-red-900/50 p-2',
                },
                html: `
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full bg-red-100 dark:bg-red-900/40 flex items-center justify-center flex-shrink-0 border border-red-200 dark:border-red-500/30 text-red-500">
                        <span class="material-symbols-outlined text-[18px]">error</span>
                    </div>
                    <div>
                        <h4 class="text-sm font-bold text-red-700 dark:text-red-300 m-0 leading-tight">{!! session('error') !!}</h4>
                    </div>
                </div>`
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

    @if($errors->any())
        <script>
            {!! "const isDarkThemeValidation = document.documentElement.classList.contains('dark');" !!}
            Swal.fire({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 5000,
                timerProgressBar: true,
                background: isDarkThemeValidation ? '#0F2A20' : '#ffffff',
                customClass: {
                    popup: 'rounded-2xl border shadow-xl border-orange-100 dark:border-orange-900/50 p-2',
                },
                html: `
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full bg-orange-100 dark:bg-orange-900/40 flex items-center justify-center flex-shrink-0 border border-orange-200 dark:border-orange-500/30 text-orange-500">
                        <span class="material-symbols-outlined text-[18px]">warning</span>
                    </div>
                    <div class="text-left">
                        <h4 class="text-sm font-bold text-orange-700 dark:text-orange-300 m-0 leading-tight">Revise los campos</h4>
                        <p class="text-[11px] text-orange-600 dark:text-orange-400 m-0 mt-0.5 leading-tight">{!! $errors->first() !!}</p>
                    </div>
                </div>`
            });
        </script>
    @endif

    @stack('scripts')
</body>
</html>
