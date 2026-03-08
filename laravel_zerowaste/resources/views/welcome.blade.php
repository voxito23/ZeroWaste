<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Zero Waste - Bienvenido</title>
    
    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Montserrat:wght@800&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet" />
    
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
<body class="bg-[#FDFDFC] dark:bg-[#0a0a0a] text-[#1b1b18] flex p-6 lg:p-8 items-center lg:justify-center min-h-screen flex-col transition-colors duration-500">
    <header class="w-full lg:max-w-4xl max-w-[335px] flex justify-between items-center mb-6">
        <div class="flex items-center gap-2">
            <span class="material-symbols-outlined text-primary text-3xl font-bold">recycling</span>
            <span class="font-['Montserrat'] font-extrabold text-xl dark:text-white uppercase tracking-tighter">Zero Waste</span>
        </div>
        <button class="p-2 rounded-xl hover:bg-emerald-100 dark:hover:bg-emerald-900/30 transition-all border border-transparent dark:border-emerald-800" onclick="document.documentElement.classList.toggle('dark')">
            <span class="material-symbols-outlined dark:hidden text-secondary">dark_mode</span>
            <span class="material-symbols-outlined hidden dark:block text-primary">light_mode</span>
        </button>
    </header>

    <main class="flex max-w-[335px] w-full flex-col-reverse lg:max-w-4xl lg:flex-row shadow-2xl rounded-3xl overflow-hidden border dark:border-emerald-900/30">
        <div class="flex-1 p-8 lg:p-16 bg-white dark:bg-[#161615] dark:text-[#EDEDEC] transition-colors">
            <h1 class="text-3xl font-bold mb-2">Hola de nuevo</h1>
            <p class="mb-8 text-gray-500 dark:text-gray-400">Ingresa tus credenciales para gestionar el sistema.</p>
            
            <form action="{{ route('dashboard') }}" class="space-y-4">
                <div class="space-y-1">
                    <label class="text-xs font-bold uppercase tracking-wider text-secondary dark:text-primary">Correo Electrónico</label>
                    <input type="email" class="w-full p-3 rounded-xl border-gray-200 dark:bg-emerald-900/10 dark:border-emerald-800 dark:text-white focus:ring-primary focus:border-primary" placeholder="victor@zerowaste.mx">
                </div>
                <div class="space-y-1">
                    <label class="text-xs font-bold uppercase tracking-wider text-secondary dark:text-primary">Contraseña</label>
                    <input type="password" class="w-full p-3 rounded-xl border-gray-200 dark:bg-emerald-900/10 dark:border-emerald-800 dark:text-white focus:ring-primary focus:border-primary" placeholder="••••••••">
                </div>
                <button type="submit" class="w-full py-4 bg-primary hover:bg-emerald-400 text-secondary font-bold rounded-xl shadow-lg shadow-emerald-500/20 transition-all transform hover:-translate-y-1 mt-4">
                    Iniciar Sesión
                </button>
            </form>
        </div>

        <div class="bg-primary dark:bg-secondary relative w-full lg:w-[380px] shrink-0 flex items-center justify-center p-12 transition-colors">
            <div class="text-white text-center z-10">
                <h2 class="text-2xl font-bold mb-4 font-['Montserrat']">Querétaro Limpio</h2>
                <p class="text-sm opacity-90">Gestión inteligente de residuos para un futuro sostenible.</p>
            </div>
            <div class="absolute inset-0 opacity-20 flex items-center justify-center">
                <span class="material-symbols-outlined text-[200px]">eco</span>
            </div>
        </div>
    </main>
</body>
</html>