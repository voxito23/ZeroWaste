<!DOCTYPE html>
<html class="dark" lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@hasSection('titulo')@yield('titulo') | @endif MACUIN Admin</title>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0" rel="stylesheet">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- Flatpickr (Professional Calendar) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/dark.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/es.js"></script>

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
    tailwind.config = {
        darkMode: "class",
        theme: {
            fontFamily: {
                sans: ["Inter", "sans-serif"],
                mono: ["JetBrains Mono", "monospace"]
            }
        }
    }
    
    // Global API and Token definitions for child scripts
    var API = 'http://localhost:8001';
    var JWT_TOKEN = '{{ session("jwt_token", "") }}';
    </script>

    <style>
        body { background-color: #080808; color: #f8fafc; }
        
        /* Glassmorphism Soft (Telegram Style) */
        .glass-header {
            background: rgba(8, 8, 8, 0.7);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.03);
        }
        
        .sidebar-glass {
            background: rgba(12, 12, 12, 0.85);
            backdrop-filter: blur(20px);
            border-right: 1px solid rgba(255, 255, 255, 0.02);
        }

        /* Playful / Airbnb Rounded Cards */
        .card {
            background: #111111;
            border-radius: 2rem;
            border: 1px solid rgba(255, 255, 255, 0.04);
            box-shadow: 0 4px 40px rgba(0, 0, 0, 0.1);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .card:hover {
            transform: translateY(-4px);
            border-color: rgba(255, 255, 255, 0.1);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.2);
        }

        /* Scrollbar */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #333; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #facc15; }

        /* Flatpickr Custom Professional Theme (Purple Accent) */
        .flatpickr-calendar {
            background: #111 !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.7) !important;
            border-radius: 1.25rem !important;
            font-family: 'Inter', sans-serif !important;
        }
        .flatpickr-day.selected, .flatpickr-day.startRange, .flatpickr-day.endRange {
            background: #9333ea !important;
            border-color: #9333ea !important;
            color: #fff !important;
            font-weight: 800 !important;
        }
        .flatpickr-day.inRange {
            background: rgba(147, 51, 234, 0.15) !important;
            border-color: transparent !important;
            box-shadow: none !important;
        }
        .flatpickr-day:hover { background: rgba(147, 51, 234, 0.2) !important; color: #c084fc !important; }
        .flatpickr-day { color: #ccc !important; }
        .flatpickr-day.flatpickr-disabled { color: #333 !important; }
        .flatpickr-months .flatpickr-month { background: transparent !important; color: #fff !important; }
        .flatpickr-current-month .flatpickr-monthDropdown-months { font-weight: 800 !important; color: #fff !important; }
        .flatpickr-weekday { color: rgba(255, 255, 255, 0.3) !important; font-weight: 700 !important; }
        span.flatpickr-prev-month, span.flatpickr-next-month { color: #fff !important; fill: #fff !important; }
        .numInputWrapper span { display: none !important; }
    </style>
</head>

<body class="min-h-screen overflow-hidden flex relative selection:bg-yellow-400 selection:text-black">

    <!-- Global Background Elements (Soft Neons) -->
    <div class="fixed -z-10 top-[-10%] right-[-5%] w-[600px] h-[600px] bg-yellow-400/5 rounded-full blur-[150px] pointer-events-none"></div>
    <div class="fixed -z-10 bottom-[-10%] left-[-5%] w-[500px] h-[500px] bg-blue-500/5 rounded-full blur-[150px] pointer-events-none"></div>

    <!-- SIDEBAR -->
    <aside class="w-20 lg:w-72 sidebar-glass flex flex-col justify-between h-screen shrink-0 relative z-50 transition-all duration-300">
        <div>
            <div class="h-24 flex items-center justify-center lg:justify-start lg:px-8">
                <a href="/admin" class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-yellow-400/10 rounded-2xl flex items-center justify-center shadow-[0_0_15px_rgba(255,193,7,0.3)] p-1 overflow-hidden">
                        <img src="/images/nuevologo.png" class="w-full h-full object-contain" alt="MACUIN">
                    </div>
                    <div class="hidden lg:block">
                        <h1 class="font-extrabold text-xl tracking-tight leading-none text-white">MACUIN</h1>
                    </div>
                </a>
            </div>

            @php $currentPath = request()->path(); @endphp
            <nav class="mt-4 px-4 space-y-2">
                <a href="/admin" class="{{ request()->is('admin') ? 'text-yellow-400 bg-white/5 shadow-inner' : 'text-gray-400 hover:text-white hover:bg-white/5' }} w-full flex items-center px-4 py-3.5 rounded-[1.2rem] font-bold transition group">
                    <span class="material-symbols-rounded text-[22px] {{ request()->is('admin') ? '' : 'text-gray-500 group-hover:text-gray-300' }} transition">dashboard</span>
                    <span class="hidden lg:block ml-4">Dashboard</span>
                </a>

                <a href="/admin/cuentas" class="{{ request()->is('admin/cuentas') ? 'text-yellow-400 bg-white/5 shadow-inner' : 'text-gray-400 hover:text-white hover:bg-white/5' }} w-full flex items-center px-4 py-3.5 rounded-[1.2rem] font-bold transition group">
                    <span class="material-symbols-rounded text-[22px] {{ request()->is('admin/cuentas') ? '' : 'text-gray-500 group-hover:text-gray-300' }} transition">group</span>
                    <span class="hidden lg:block ml-4">Cuentas</span>
                </a>

                <a href="/inventario" class="{{ request()->is('inventario*') ? 'text-yellow-400 bg-white/5 shadow-inner' : 'text-gray-400 hover:text-white hover:bg-white/5' }} w-full flex items-center px-4 py-3.5 rounded-[1.2rem] font-bold transition group">
                    <span class="material-symbols-rounded text-[22px] {{ request()->is('inventario*') ? '' : 'text-gray-500 group-hover:text-gray-300' }} transition">inventory_2</span>
                    <span class="hidden lg:block ml-4">Inventario</span>
                </a>
                
                <a href="/pedidos" class="flex items-center px-4 py-3.5 rounded-[1.2rem] {{ str_starts_with($currentPath, 'pedidos') ? 'bg-white/10 text-white font-semibold' : 'text-gray-400 hover:text-white hover:bg-white/5 font-medium' }} transition group">
                    <span class="material-symbols-rounded {{ str_starts_with($currentPath, 'pedidos') ? 'text-yellow-400' : '' }} group-hover:text-yellow-400 transition">shopping_bag</span>
                    <span class="hidden lg:block ml-4">Órdenes</span>
                </a>
                
                <a href="/reportes" class="flex items-center px-4 py-3.5 rounded-[1.2rem] {{ str_starts_with($currentPath, 'reportes') ? 'bg-white/10 text-white font-semibold' : 'text-gray-400 hover:text-white hover:bg-white/5 font-medium' }} transition group">
                    <span class="material-symbols-rounded {{ str_starts_with($currentPath, 'reportes') ? 'text-yellow-400' : '' }} group-hover:text-yellow-400 transition">insights</span>
                    <span class="hidden lg:block ml-4">Analíticas</span>
                </a>
            </nav>
        </div>

        <div class="p-6">
            <button onclick="confirmarSalida()" class="w-full flex items-center justify-center lg:justify-start px-4 py-3.5 rounded-[1.2rem] bg-red-500/10 text-red-400 hover:bg-red-500/20 hover:text-red-300 transition group">
                <span class="material-symbols-rounded">logout</span>
                <span class="hidden lg:block ml-4 font-bold">Cerrar Sesión</span>
            </button>
        </div>
    </aside>

    <!-- MAIN WRAPPER -->
    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        
        <!-- HEADER (Telegram Glass Blur) -->
        <header class="h-24 glass-header flex items-center justify-between px-8 sm:px-10 sticky top-0 z-40">
            <div class="flex items-center gap-3">
                <h2 class="text-2xl font-bold tracking-tight hidden sm:block">@hasSection('titulo') @yield('titulo') @else Vista General @endif</h2>
                <span class="px-3 py-1 bg-yellow-400/10 text-yellow-400 text-xs font-bold rounded-full hidden md:block border border-yellow-400/20">En Vivo</span>
            </div>

            <div class="flex items-center gap-5">
                
                <div class="flex items-center gap-3 bg-white/5 pl-4 pr-2 py-2 rounded-full border border-white/5">
                    <div class="text-right hidden md:block">
                        <p class="text-sm font-bold text-white leading-tight">{{ session('user_name', 'Admin') }}</p>
                    </div>
                    <label class="cursor-pointer relative group">
                        <div id="img-perfil-container" class="w-10 h-10 rounded-full overflow-hidden border-2 border-yellow-400 group-hover:opacity-70 transition bg-white/5 flex items-center justify-center">
                            @if(session('imagen_perfil'))
                                <img id="img-perfil-admin" src="{{ session('imagen_perfil') }}" class="w-full h-full object-cover">
                            @else
                                <span id="img-perfil-icon" class="material-symbols-rounded text-gray-500 text-2xl">account_circle</span>
                                <img id="img-perfil-admin" src="" class="absolute inset-0 w-full h-full object-cover hidden">
                            @endif
                        </div>
                        <div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition">
                            <span class="material-symbols-rounded text-white text-[18px]">edit</span>
                        </div>
                        <input type="file" id="input-perfil-admin" accept="image/*" class="hidden" onchange="uploadProfilePic(event)">
                    </label>
                </div>
            </div>
        </header>

        <!-- CONTENT SCROLLABLE -->
        <main class="flex-1 overflow-y-auto px-6 sm:px-10 pt-8 pb-16 space-y-8">
            
            @hasSection('contenido')
                @yield('contenido')
            @else
            <!-- STATS BLOCK (Airbnb Large Cards) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                
                <div class="card p-8 group">
                    <div class="w-14 h-14 bg-emerald-500/10 rounded-[1.2rem] flex items-center justify-center mb-6 text-emerald-400">
                        <span class="material-symbols-rounded text-[28px]">payments</span>
                    </div>
                    <p class="text-gray-400 text-sm font-medium mb-1">Volumen Generado</p>
                    <p class="text-[2rem] font-bold tracking-tight text-white leading-none" id="stat-ventas">$0.00</p>
                </div>

                <div class="card p-8 group">
                    <div class="w-14 h-14 bg-yellow-400/10 rounded-[1.2rem] flex items-center justify-center mb-6 text-yellow-400">
                        <span class="material-symbols-rounded text-[28px]">receipt_long</span>
                    </div>
                    <p class="text-gray-400 text-sm font-medium mb-1">Órdenes Activas</p>
                    <p class="text-[2rem] font-bold tracking-tight text-white leading-none" id="stat-pendientes">0</p>
                    <p class="text-xs text-yellow-400 mt-2 font-medium flex items-center gap-1"><span class="material-symbols-rounded text-[14px]">trending_up</span> Requieren atención</p>
                </div>

                <div class="card p-8 group">
                    <div class="w-14 h-14 bg-red-500/10 rounded-[1.2rem] flex items-center justify-center mb-6 text-red-500">
                        <span class="material-symbols-rounded text-[28px]">warning</span>
                    </div>
                    <p class="text-gray-400 text-sm font-medium mb-1">Alertas de Stock</p>
                    <p class="text-[2rem] font-bold tracking-tight text-white leading-none" id="stat-stock-bajo">0</p>
                </div>

                <div class="card p-8 group">
                    <div class="w-14 h-14 bg-blue-500/10 rounded-[1.2rem] flex items-center justify-center mb-6 text-blue-400">
                        <span class="material-symbols-rounded text-[28px]">group</span>
                    </div>
                    <p class="text-gray-400 text-sm font-medium mb-1">Cuentas Creadas</p>
                    <p class="text-[2rem] font-bold tracking-tight text-white leading-none" id="stat-clientes">0</p>
                </div>

            </div>

            <!-- CHARTS ROW -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Pedidos Chart -->
                <div class="card p-8 min-h-[350px] flex flex-col">
                    <div class="flex items-center justify-between mb-8">
                        <h3 class="font-bold text-xl tracking-tight">Estadística de Pedidos</h3>
                        <span class="p-2 bg-white/5 rounded-full"><span class="material-symbols-rounded text-gray-400 text-[20px]">bar_chart</span></span>
                    </div>
                    <div class="flex-1 relative w-full">
                        <canvas id="chart-pedidos"></canvas>
                    </div>
                </div>

                <!-- Usuarios / Role Management Table -->
                <div class="card p-8 flex flex-col min-h-[350px]">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h3 class="font-bold text-xl tracking-tight">Directorio de Cuentas</h3>
                            <p class="text-sm text-gray-400">Gestión de roles y accesos</p>
                        </div>
                        <span class="p-2 bg-white/5 rounded-full"><span class="material-symbols-rounded text-gray-400 text-[20px]">manage_accounts</span></span>
                    </div>

                    <div class="flex-1 overflow-x-auto">
                        <table class="w-full text-left whitespace-nowrap">
                            <thead>
                                <tr class="text-xs text-gray-500 uppercase tracking-wider border-b border-gray-800">
                                    <th class="pb-3 font-medium">Usuario</th>
                                    <th class="pb-3 font-medium">Contacto</th>
                                    <th class="pb-3 font-medium">Permisos</th>
                                    <th class="pb-3 font-medium text-right">Acción</th>
                                </tr>
                            </thead>
                            <tbody id="tabla-usuarios" class="divide-y divide-gray-800/60 text-sm">
                                <tr><td colspan="4" class="py-10 text-center text-gray-500">Cargando cuentas...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            @endif

        </main>
    </div>

    <script>
        // SweetAlert Custom Modern Theme

        // SweetAlert Custom Modern Theme
        const swalModern = {
            background: '#111111',
            color: '#fff',
            customClass: {
                popup: 'rounded-[2rem] border border-gray-800 shadow-2xl',
                confirmButton: 'text-black font-bold px-8 py-3.5 rounded-full bg-yellow-400 hover:bg-yellow-500 shadow-lg',
                cancelButton: 'text-white font-bold px-8 py-3.5 rounded-full bg-[#222] hover:bg-[#333] ml-3 border border-gray-700'
            }
        };

        const Toast = Swal.mixin({
            toast: true,
            position: 'bottom-end',
            showConfirmButton: false,
            timer: 3000,
            background: '#1a1a1a',
            color: '#fff',
            customClass: { popup: 'rounded-2xl border border-gray-800' }
        });

        function confirmarSalida() {
            Swal.fire({
                ...swalModern,
                title: '¿Cerrar sesión?',
                text: 'Regresarás a la pantalla principal.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, salir',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) window.location.href = "/logout";
            });
        }

        // Render Users Table inline logic
        async function fetchUsuarios() {
            const tbody = document.getElementById('tabla-usuarios');
            try {
                const res = await fetch(API + '/usuarios/', {
                    headers: JWT_TOKEN ? { 'Authorization': 'Bearer ' + JWT_TOKEN } : {}
                });
                const data = await res.json();
                
                if(!Array.isArray(data)) throw new Error('Invalid format');
                
                tbody.innerHTML = data.map(u => {
                    const isAdmin = u.rol === 'admin';
                    const badgeClass = isAdmin ? 'bg-yellow-400/10 text-yellow-400 border-yellow-400/20' : 'bg-white/5 text-gray-300 border-white/5';
                    const btnClass = isAdmin ? 'text-gray-500 hover:text-red-400' : 'text-blue-400 hover:text-yellow-400 font-medium';
                    const btnText = isAdmin ? '<span class="material-symbols-rounded text-lg">person_cancel</span>' : 'Hacer Admin';
                    const targetRol = isAdmin ? 'cliente' : 'admin';
                    const tooltipMsg = isAdmin ? 'Revocar administrador' : 'Otorgar permisos Master';

                    return `
                    <tr class="hover:bg-white/[0.02] transition">
                        <td class="py-4 font-semibold text-white">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-white/5 border border-white/10 flex items-center justify-center text-gray-500">
                                    <span class="material-symbols-rounded text-lg">${u.rol === 'admin' ? 'admin_panel_settings' : 'person'}</span>
                                </div>
                                <span>${u.nombre}</span>
                            </div>
                        </td>
                        <td class="py-4 text-gray-400">${u.email}</td>
                        <td class="py-4">
                            <span class="px-3 py-1 rounded-full text-xs font-bold border ${badgeClass}">${u.rol.toUpperCase()}</span>
                        </td>
                        <td class="py-4 text-right">
                            <button onclick="cambiarRol(${u.id}, '${targetRol}', '${u.nombre}')" title="${tooltipMsg}" class="px-4 py-1.5 rounded-full bg-white/5 border border-transparent hover:border-white/10 transition text-xs ${btnClass}">
                                ${btnText}
                            </button>
                        </td>
                    </tr>`;
                }).join('');
            } catch(e) {
                tbody.innerHTML = '<tr><td colspan="4" class="py-4 text-center text-red-400">No se pudieron cargar las cuentas</td></tr>';
            }
        }

        // Change Role Function calling FastAPI PUT
        function cambiarRol(userId, nuevoRol, nombre) {
            Swal.fire({
                ...swalModern,
                icon: 'question',
                title: 'Modificar Permisos',
                text: `¿Convertir a ${nombre} en ${nuevoRol.toUpperCase()}?`,
                showCancelButton: true,
                confirmButtonText: 'Sí, aplicar',
                cancelButtonText: 'Cancelar'
            }).then(async (result) => {
                if (result.isConfirmed) {
                    try {
                        const formData = new URLSearchParams();
                        // Asumimos que FastAPI requiere nombre, email, etc. En un PUT robusto podríamos hacer PATCH o enviar los dados anteriores
                        // Ya que el API pide: nombre, email, telefono, rol. Haremos un fetch previo o mandaremos data parcial si la API lo permite.
                        // Según router.put("/{usuario_id}") pide Form(...). Pasaremos valores genéricos para los mantenibles. 
                        
                        // Para simplificar y no borrar datos, jalamos los datos actuales usando GET /usuarios/{id}
                        const uRes = await fetch(API + '/usuarios/' + userId, {
                            headers: JWT_TOKEN ? { 'Authorization': 'Bearer ' + JWT_TOKEN } : {}
                        });
                        const userInfo = await uRes.json();

                        formData.append('nombre', userInfo.nombre);
                        formData.append('email', userInfo.email);
                        formData.append('telefono', userInfo.telefono || 'N/A');
                        formData.append('rol', nuevoRol);

                        const res = await fetch(`${API}/usuarios/${userId}`, {
                            method: 'PUT',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                                ...(JWT_TOKEN ? { 'Authorization': 'Bearer ' + JWT_TOKEN } : {})
                            },
                            body: formData.toString()
                        });

                        if(res.ok) {
                            Toast.fire({icon: 'success', title: 'Permisos actualizados correctamente'});
                            fetchUsuarios(); // Refresh list
                        } else {
                            throw new Error();
                        }
                    } catch(e) {
                        Swal.fire({...swalModern, icon: 'error', title: 'Error', text: 'No se pudo actualizar el rol. Verifica la API.'});
                    }
                }
            });
        }

        async function loadDashboard() {
            try {
                const authHeaders = JWT_TOKEN ? { 'Authorization': 'Bearer ' + JWT_TOKEN } : {};
                
                // Fetch basic stats
                const [autopartes, pedidos] = await Promise.all([
                    fetch(API + '/autopartes/', { headers: authHeaders }).then(r => r.json()),
                    fetch(API + '/pedidos/', { headers: authHeaders }).then(r => r.json())
                ]);

                let totalVentas = 0;
                const apMap = {};
                if(Array.isArray(autopartes)) autopartes.forEach(a => apMap[a.id] = a);

                let monthlyCounts = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
                
                if(Array.isArray(pedidos)) {
                    pedidos.forEach(p => {
                        if(p.estado !== 'cancelado') {
                            (p.productos || []).forEach(prod => {
                                if(apMap[prod.autoparte_id]) totalVentas += apMap[prod.autoparte_id].precio * prod.cantidad;
                            });
                            
                            // Agrupar órdenes por mes
                            if(p.fecha) {
                                const fechaStr = p.fecha.toString();
                                const sanitizedDate = fechaStr.includes('T') ? fechaStr : fechaStr.replace(' ', 'T');
                                const m = new Date(sanitizedDate).getMonth();
                                if(m >= 0 && m <= 11) monthlyCounts[m]++;
                            }
                        }
                    });
                }

                document.getElementById('stat-ventas').textContent = '$' + totalVentas.toLocaleString('en-US', {minimumFractionDigits: 2});
                document.getElementById('stat-pendientes').textContent = (Array.isArray(pedidos) ? pedidos : []).filter(p => ['recibido', 'en_proceso'].includes(p.estado)).length;
                document.getElementById('stat-stock-bajo').textContent = (Array.isArray(autopartes) ? autopartes : []).filter(a => a.stock < 5).length;
                
                // Chart Setup (Modern Minimalist)
                Chart.defaults.color = '#71717a';
                Chart.defaults.font.family = 'Inter';

                const ctx = document.getElementById('chart-pedidos').getContext('2d');
                
                // Gradient for chart
                let gradient = ctx.createLinearGradient(0, 0, 0, 400);
                gradient.addColorStop(0, 'rgba(250, 204, 21, 0.5)'); // yellow-400
                gradient.addColorStop(1, 'rgba(250, 204, 21, 0.0)');

                const etiquetas = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
                if (window.pedidosChart) window.pedidosChart.destroy();
                window.pedidosChart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: etiquetas,
                        datasets: [{
                            label: 'Órdenes',
                            data: monthlyCounts,
                            borderColor: '#facc15',
                            backgroundColor: gradient,
                            borderWidth: 3,
                            fill: true,
                            tension: 0.4,
                            pointBackgroundColor: '#111',
                            pointBorderColor: '#facc15',
                            pointBorderWidth: 2,
                            pointRadius: 5,
                            pointHoverRadius: 7
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false }, tooltip: { mode: 'index', intersect: false } },
                        scales: {
                            x: { grid: { display: false }, border: { display: false } },
                            y: { beginAtZero: true, border: { display: false }, grid: { color: 'rgba(255,255,255,0.03)' }, ticks: { precision: 0 } }
                        }
                    }
                });

                // Load users via FastAPI
                fetchUsuarios();
                const uRes = await fetch(API + '/usuarios/', { headers: authHeaders });
                const usrData = await uRes.json();
                if(Array.isArray(usrData)) document.getElementById('stat-clientes').textContent = usrData.length;

                // Actualización en tiempo real (cada 5 segundos)
                if (!window.realtimeInterval) {
                    window.realtimeInterval = setInterval(async () => {
                        try {
                            const [autoRes, pedRes] = await Promise.all([
                                fetch(API + '/autopartes/', { headers: authHeaders }),
                                fetch(API + '/pedidos/', { headers: authHeaders })
                            ]);
                            if (autoRes.ok && pedRes.ok) {
                                const npedidos = await pedRes.json();
                                const nautopartes = await autoRes.json();
                                
                                let ntotalVentas = 0;
                                const napMap = {};
                                if (Array.isArray(nautopartes)) nautopartes.forEach(a => napMap[a.id] = a);

                                let nMonthlyCounts = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];

                                if (Array.isArray(npedidos)) {
                                    npedidos.forEach(p => {
                                        if(p.estado !== 'cancelado') {
                                            (p.productos || []).forEach(prod => {
                                                if(napMap[prod.autoparte_id]) ntotalVentas += napMap[prod.autoparte_id].precio * prod.cantidad;
                                            });
                                            if(p.fecha) {
                                                const fechaStr = p.fecha.toString();
                                                const sanitizedDate = fechaStr.includes('T') ? fechaStr : fechaStr.replace(' ', 'T');
                                                const m = new Date(sanitizedDate).getMonth();
                                                if(m >= 0 && m <= 11) nMonthlyCounts[m]++;
                                            }
                                        }
                                    });
                                }

                                document.getElementById('stat-ventas').textContent = '$' + ntotalVentas.toLocaleString('en-US', {minimumFractionDigits: 2});
                                document.getElementById('stat-pendientes').textContent = (Array.isArray(npedidos) ? npedidos : []).filter(p => ['recibido', 'en_proceso'].includes(p.estado)).length;
                                document.getElementById('stat-stock-bajo').textContent = (Array.isArray(nautopartes) ? nautopartes : []).filter(a => a.stock < 5).length;
                                
                                if (window.pedidosChart) {
                                    window.pedidosChart.data.datasets[0].data = nMonthlyCounts;
                                    window.pedidosChart.update();
                                }
                            }
                        } catch(err) { console.error("Error en update real-time", err); }
                    }, 5000);
                }

            } catch(e) {
                console.error(e);
            }
        }

        async function uploadProfilePic(event) {
            const file = event.target.files[0];
            if (!file) return;

            const formData = new FormData();
            formData.append('file', file);

            Toast.fire({ icon: 'info', title: 'Subiendo imagen...', timer: 1500 });

            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
                const res = await fetch('/admin/upload-perfil', {
                    method: 'POST',
                    headers: csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {},
                    body: formData
                });
                const data = await res.json();

                if (res.ok && data.url) {
                    Toast.fire({ icon: 'success', title: 'Foto de perfil actualizada' });
                    const img = document.getElementById('img-perfil-admin');
                    const icon = document.getElementById('img-perfil-icon');
                    img.src = data.url;
                    img.classList.remove('hidden');
                    if(icon) icon.classList.add('hidden');
                } else {
                    throw new Error(data.error || 'Error al subir');
                }
            } catch (e) {
                console.error(e);
                Toast.fire({ icon: 'error', title: 'Error al subir la imagen' });
            }
        }

        // Only run dashboard logic when dashboard elements exist (not on child pages)
        if (document.getElementById('stat-ventas')) {
            loadDashboard();
        }
    </script>
</body>
</html>