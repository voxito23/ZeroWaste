<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Zero Waste - Panel Admin</title>
    
    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght@300;400;500;600&display=swap" rel="stylesheet" />
    
    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        primary: "#00E096",
                        secondary: "#064E3B",
                        "surface-dark": "#062C25",
                        "forest-dark": "#022C22",
                    }
                }
            }
        };
    </script>
</head>
<body class="bg-[#F8FAFC] dark:bg-forest-dark transition-colors duration-500 flex h-screen overflow-hidden font-['Inter'] text-[#1b1b18] dark:text-white">

    <aside class="w-64 bg-white dark:bg-surface-dark border-r border-gray-200 dark:border-emerald-900/50 flex flex-col transition-colors">
        <div class="p-6 flex items-center gap-3">
            <div class="bg-primary/20 p-2 rounded-lg">
                <span class="material-symbols-outlined text-primary font-bold">recycling</span>
            </div>
            <span class="font-bold text-lg">Zero Waste</span>
        </div>
        
        <nav class="flex-1 px-4 mt-4 space-y-1">
            <a href="#" class="flex items-center gap-3 p-3 bg-primary text-secondary rounded-xl font-bold shadow-md shadow-emerald-500/10">
                <span class="material-symbols-outlined">grid_view</span> Resumen
            </a>
            <a href="#" class="flex items-center gap-3 p-3 text-gray-500 dark:text-gray-400 hover:bg-emerald-50 dark:hover:bg-emerald-900/20 rounded-xl transition-all">
                <span class="material-symbols-outlined">map</span> Mapa
            </a>
            <a href="#" class="flex items-center gap-3 p-3 text-gray-500 dark:text-gray-400 hover:bg-emerald-50 dark:hover:bg-emerald-900/20 rounded-xl transition-all">
                <span class="material-symbols-outlined">group</span> Usuarios
            </a>
        </nav>

        <div class="p-4 border-t dark:border-emerald-900">
            <a href="{{ route('login') }}" class="flex items-center gap-2 text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 p-3 rounded-xl transition-colors">
                <span class="material-symbols-outlined">logout</span> Cerrar Sesión
            </a>
        </div>
    </aside>

    <div class="flex-1 flex flex-col">
        <header class="h-20 bg-white/80 dark:bg-surface-dark/80 backdrop-blur-md border-b dark:border-emerald-900 flex justify-between items-center px-8 transition-colors">
            <h2 class="text-xl font-bold">Actividad en Querétaro</h2>
            
            <div class="flex items-center gap-4">
                <button class="w-10 h-10 rounded-xl border dark:border-emerald-800 flex items-center justify-center hover:bg-gray-100 dark:hover:bg-emerald-900/50 transition-all" onclick="document.documentElement.classList.toggle('dark')">
                    <span class="material-symbols-outlined dark:hidden text-secondary">dark_mode</span>
                    <span class="material-symbols-outlined hidden dark:block text-primary">light_mode</span>
                </button>
                
                <div class="flex items-center gap-3 border-l pl-4 dark:border-emerald-800">
                    <div class="text-right hidden sm:block">
                        <p class="text-xs font-bold">Victor Rodriguez</p>
                        <p class="text-[10px] text-primary">Super Admin</p>
                    </div>
                    <img class="w-10 h-10 rounded-xl border-2 border-primary" src="https://ui-avatars.com/api/?name=Victor+Rodriguez&background=00E096&color=064E3B" alt="Avatar">
                </div>
            </div>
        </header>

        <main class="p-8 overflow-y-auto">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="bg-white dark:bg-surface-dark p-6 rounded-2xl border dark:border-emerald-900 transition-colors">
                    <p class="text-xs font-bold text-gray-400 uppercase">Residuos Totales</p>
                    <p class="text-2xl font-bold mt-1">425.8 <span class="text-xs text-primary font-normal">Ton.</span></p>
                </div>
                </div>

            <div class="bg-white dark:bg-surface-dark rounded-3xl border dark:border-emerald-900 overflow-hidden transition-colors">
                <div class="p-6 border-b dark:border-emerald-900">
                    <h3 class="font-bold">Reportes Recientes</h3>
                </div>
                <table class="w-full text-left">
                    <thead class="bg-gray-50 dark:bg-emerald-900/10 text-[10px] uppercase font-bold text-gray-400">
                        <tr>
                            <th class="p-4">Municipio</th>
                            <th class="p-4">Tipo</th>
                            <th class="p-4">Estado</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm">
                        <tr class="border-b dark:border-emerald-900/50 hover:bg-gray-50 dark:hover:bg-emerald-900/5">
                            <td class="p-4">Corregidora</td>
                            <td class="p-4">Plástico</td>
                            <td class="p-4"><span class="px-2 py-1 bg-yellow-100 dark:bg-yellow-900/30 text-yellow-600 text-[10px] font-bold rounded-full">PENDIENTE</span></td>
                        </tr>
                        </tbody>
                </table>
            </div>
        </main>
    </div>
</body>
</html>