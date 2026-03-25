<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zero Waste - @yield('title', 'Admin')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: { primary: "#00E096", secondary: "#064E3B", accent: "#34D399" },
                    fontFamily: { sans: ['Inter', 'sans-serif'] }
                }
            }
        };
    </script>
</head>
<body class="bg-[#ECFDF5] text-[#064E3B] font-sans flex min-h-screen">

    <!-- Sidebar Replicable -->
    <aside class="w-64 bg-white/95 border-r border-emerald-100 shadow-xl flex flex-col hidden lg:flex">
        <div class="h-24 flex items-center justify-center border-b border-emerald-50">
            <span class="font-extrabold text-2xl text-secondary">ZERO WASTE</span>
        </div>
        <nav class="p-6 flex flex-col gap-4">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-3 font-bold text-primary hover:text-emerald-400"><span class="material-symbols-outlined">dashboard</span> Dashboard</a>
            <a href="{{ route('campanas.index') }}" class="flex items-center gap-3 font-bold text-secondary hover:text-primary"><span class="material-symbols-outlined">campaign</span> Campañas</a>
            <a href="{{ route('materiales.index') }}" class="flex items-center gap-3 font-bold text-secondary hover:text-primary"><span class="material-symbols-outlined">recycling</span> Materiales</a>
            <a href="{{ route('mapa.index') }}" class="flex items-center gap-3 font-bold text-secondary hover:text-primary"><span class="material-symbols-outlined">map</span> Mapa</a>
            <a href="/" class="flex items-center gap-3 font-bold text-secondary hover:text-red-500 mt-auto"><span class="material-symbols-outlined">logout</span> Salir</a>
        </nav>
    </aside>

    <main class="flex-1 p-10 flex flex-col">
        <header class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-extrabold">@yield('page_title', 'Dashboard')</h1>
            <div class="flex items-center gap-4">
                <div class="w-10 h-10 rounded-full bg-emerald-200 border-2 border-primary flex items-center justify-center text-secondary font-bold">
                    A
                </div>
            </div>
        </header>
        
        <div class="flex-1">
            @if(session('success'))
                <div class="bg-primary/20 text-secondary border border-primary p-4 rounded-xl mb-6">
                    {{ session('success') }}
                </div>
            @endif

            @yield('content')
        </div>
    </main>

    @stack('scripts')
</body>
</html>
