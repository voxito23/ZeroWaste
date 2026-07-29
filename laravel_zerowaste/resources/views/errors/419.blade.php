<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>419 Sesión Expirada - Zero Waste</title>
    <link rel="icon" type="image/svg+xml" href="/static/faviconZeroWaste.svg">
    <link rel="alternate icon" type="image/png" href="/static/faviconZeroWaste.png">

    <script>
        // Tema oscuro
        if (localStorage.getItem('zw-admin-theme') === 'dark') {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
            localStorage.setItem('zw-admin-theme', 'light');
        }
    </script>

    <script src="https://cdn.tailwindcss.com?plugins=forms,typography,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Montserrat:wght@400;500;600;700;800;900&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        primary: "#00E096",
                        "primary-hover": "#00C281",
                        secondary: "#064E3B",
                        "forest-dark": "#022C22",
                        "surface-light": "#ECFDF5",
                        "surface-dark": "#062C25",
                        "text-light": "#064E3B",
                        "text-dark": "#D1FAE5",
                        accent: "#34D399",
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                        display: ['Montserrat', 'sans-serif'],
                    },
                },
            },
        };
    </script>
    <style>
        @keyframes drooping {
            0%, 100% { transform: rotate(-3deg); }
            50% { transform: rotate(4deg); }
        }
        @keyframes float-clock {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-8px) rotate(5deg); }
        }
        @keyframes spin-slow {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        @keyframes pulse-soft {
            0%, 100% { opacity: 0.6; transform: scale(0.98); }
            50% { opacity: 1; transform: scale(1.03); }
        }

        .plant-droop {
            transform-origin: 50% 90%;
            animation: drooping 6s ease-in-out infinite;
        }
        .clock-float {
            animation: float-clock 4s ease-in-out infinite;
        }
        .clock-spin {
            transform-origin: center;
            animation: spin-slow 12s linear infinite;
        }
        .pulse-time {
            animation: pulse-soft 3s infinite;
        }
    </style>
</head>

<body class="bg-surface-light dark:bg-surface-dark text-text-light dark:text-text-dark font-sans transition-colors duration-300 flex flex-col min-h-screen">

<main class="flex-grow flex items-center justify-center px-6 relative z-10 w-full">
    <div class="max-w-2xl w-full text-center flex flex-col items-center">
        <!-- SVG Plantita con Reloj / Tiempo Agotado -->
        <div class="w-64 h-64 mb-8 pt-6 relative flex justify-center items-end overflow-visible">
            <!-- Maceta -->
            <svg class="absolute bottom-0 w-32 h-24 overflow-visible z-10" viewBox="0 0 100 80" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M10 0 L25 80 L75 80 L90 0 Z" fill="#b45309" class="dark:fill-orange-900 transition-colors duration-500"/>
                <path d="M5 0 L95 0 L92 10 L8 10 Z" fill="#92400e" class="dark:fill-orange-800 transition-colors duration-500"/>
                <!-- Detalles de maceta -->
                <path class="pulse-time" d="M30 40 Q50 48 70 40" stroke="#78350f" stroke-width="2" fill="none" stroke-linecap="round"/>
            </svg>

            <!-- Tallo y hojas (plant-droop) -->
            <div class="absolute bottom-16 w-40 h-48 plant-droop overflow-visible z-0 flex flex-col items-center">
                <svg viewBox="0 0 150 200" width="100%" height="100%" fill="none" xmlns="http://www.w3.org/2000/svg" class="overflow-visible">
                    <path d="M75 200 Q85 100 115 50" stroke="#10b981" stroke-width="6" fill="none" stroke-linecap="round" class="dark:stroke-emerald-700 transition-colors duration-500"/>
                    <path d="M85 150 Q120 170 110 200 Q85 180 85 150 Z" fill="#059669" class="dark:fill-emerald-800 opacity-80" />
                    <path d="M105 100 Q145 115 135 145 Q105 125 105 100 Z" fill="#34d399" class="dark:fill-emerald-600 opacity-80" />
                    <path d="M110 60 Q155 65 145 100 Q105 80 110 60 Z" fill="#6ee7b7" class="dark:fill-emerald-500 opacity-60" />
                </svg>
            </div>

            <!-- Reloj de Tiempo Agotado flotante -->
            <div class="absolute top-4 right-8 w-20 h-20 clock-float z-20">
                <svg viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-full h-full drop-shadow-lg">
                    <circle cx="50" cy="50" r="42" fill="#ECFDF5" class="dark:fill-forest-dark transition-colors duration-500" stroke="#00E096" stroke-width="6"/>
                    <circle cx="50" cy="50" r="34" stroke="#059669" stroke-width="2" stroke-dasharray="4 4" class="dark:stroke-emerald-400 opacity-60"/>
                    <!-- Manecillas girando -->
                    <g class="clock-spin">
                        <line x1="50" y1="50" x2="50" y2="24" stroke="#064E3B" class="dark:stroke-emerald-200" stroke-width="4" stroke-linecap="round"/>
                        <line x1="50" y1="50" x2="72" y2="50" stroke="#00E096" stroke-width="3" stroke-linecap="round"/>
                    </g>
                    <circle cx="50" cy="50" r="4" fill="#064E3B" class="dark:fill-emerald-200"/>
                </svg>
            </div>
        </div>

        <h1 class="text-7xl md:text-9xl font-black font-display text-primary drop-shadow-sm mb-4 tracking-tighter">419</h1>
        <h2 class="text-2xl md:text-3xl font-bold text-secondary dark:text-emerald-100 mb-6 drop-shadow-sm">¡Uy! Tu sesión se ha marchitado...</h2>

        <p class="text-gray-600 dark:text-gray-300 text-lg md:text-xl max-w-lg mx-auto mb-10 leading-relaxed font-medium">
            Por motivos de seguridad y ahorro de recursos, tu sesión expiró tras un tiempo sin actividad. Al igual que una planta esperando su riego a tiempo, solo necesitas refrescar la página.
        </p>

        <div class="flex flex-wrap items-center justify-center gap-4">
            <button onclick="window.location.reload()" class="group relative inline-flex items-center justify-center gap-3 px-8 py-4 bg-primary text-secondary font-bold text-lg rounded-full shadow-lg shadow-primary/30 hover:bg-primary-hover hover:shadow-primary/50 transition-all duration-300 transform hover:-translate-y-1 cursor-pointer">
                <span class="material-symbols-outlined group-hover:rotate-180 transition-transform duration-500">refresh</span>
                Recargar Página
            </button>

            <a href="{{ url('/zw-interno/login') }}" class="group relative inline-flex items-center justify-center gap-3 px-8 py-4 bg-white dark:bg-forest-dark text-secondary dark:text-text-dark border-2 border-primary/30 hover:border-primary font-bold text-lg rounded-full shadow-md hover:shadow-lg transition-all duration-300 transform hover:-translate-y-1">
                <span class="material-symbols-outlined group-hover:block transition-transform">login</span>
                Iniciar Sesión
            </a>
        </div>
    </div>
</main>

</body>
</html>
