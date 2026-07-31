<!DOCTYPE html>
<html lang="es">
<script>
    (function() {
        var t = localStorage.getItem('zw-admin-theme');
        if (t === 'dark') document.documentElement.classList.add('dark');
    })();
</script>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ZeroWaste Admin</title>
    <link rel="icon" type="image/svg+xml" href="/static/faviconZeroWaste.svg">
    <link rel="alternate icon" type="image/png" href="/static/faviconZeroWaste.png">
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
        <div class="flex items-center gap-3">
            <img src="{{ asset('img/logo.png') }}" alt="ZeroWaste" class="h-9 w-auto" onerror="this.style.display='none'">
            <span class="font-['Montserrat'] font-extrabold text-xl dark:text-white uppercase tracking-tighter">Zero Waste</span>
        </div>
        <button class="p-2 rounded-xl hover:bg-emerald-100 dark:hover:bg-emerald-900/30 transition-all border border-transparent dark:border-emerald-800" onclick="document.documentElement.classList.toggle('dark'); localStorage.setItem('zw-admin-theme', document.documentElement.classList.contains('dark') ? 'dark' : 'light')">
            <span class="material-symbols-outlined dark:hidden text-secondary">dark_mode</span>
            <span class="material-symbols-outlined hidden dark:block text-primary">light_mode</span>
        </button>
    </header>

    <main class="flex max-w-[335px] w-full flex-col-reverse lg:max-w-4xl lg:flex-row shadow-2xl rounded-3xl overflow-hidden border dark:border-emerald-900/30">
        <div class="flex-1 p-8 lg:p-16 bg-white dark:bg-[#161615] dark:text-[#EDEDEC] transition-colors">
            <span class="inline-block bg-primary/15 text-secondary dark:text-primary text-xs font-bold uppercase tracking-widest px-3 py-1 rounded-full mb-3">Panel de Administración Exclusivo</span>
            <h1 class="text-3xl font-bold mb-2">Hola de nuevo</h1>
            <p class="mb-8 text-gray-500 dark:text-gray-400">Ingresa tus credenciales para gestionar el sistema.</p>
            
            <form id="loginForm" novalidate method="POST" action="{{ route('admin.login') }}" class="space-y-4">
                @csrf
                @if ($errors->any())
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-3 rounded-md text-sm">
                    {{ $errors->first() }}
                </div>
                @endif
                <div class="space-y-1">
                    <label class="text-xs font-bold uppercase tracking-wider text-secondary dark:text-primary">Correo Electrónico</label>
                    <input type="email" name="email" id="login-email" value="{{ old('email') }}" class="w-full p-3 rounded-xl border-gray-200 dark:bg-emerald-900/10 dark:border-emerald-800 dark:text-white focus:ring-primary focus:border-primary transition-all" placeholder="victor@zerowaste.mx">
                    <span id="error-email" class="hidden text-red-500 text-sm mt-1 font-medium"></span>
                </div>
                <div class="space-y-1">
                    <label class="text-xs font-bold uppercase tracking-wider text-secondary dark:text-primary">Contraseña</label>
                    <div class="relative">
                        <input type="password" name="password" id="login-password" class="w-full p-3 pr-12 rounded-xl border-gray-200 dark:bg-emerald-900/10 dark:border-emerald-800 dark:text-white focus:ring-primary focus:border-primary transition-all" placeholder="••••••••">
                        <button type="button" onclick="togglePass('login-password', this)" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-primary transition-colors">
                            <span class="material-symbols-outlined">visibility_off</span>
                        </button>
                    </div>
                    <span id="error-password" class="hidden text-red-500 text-sm mt-1 font-medium"></span>
                </div>
                <button id="login-submit" type="submit" class="w-full py-4 bg-primary hover:bg-emerald-400 text-secondary font-bold rounded-xl shadow-lg shadow-emerald-500/20 transition-all transform hover:-translate-y-1 mt-4 disabled:opacity-60 disabled:cursor-not-allowed">
                    Iniciar Sesión
                </button>
            </form>
        </div>

        <div class="bg-primary dark:bg-secondary relative w-full lg:w-[380px] shrink-0 flex items-center justify-center p-12 transition-colors">
            <div class="text-white text-center z-10">
                <img src="{{ asset('img/logo.png') }}" alt="ZeroWaste" class="h-20 w-auto mx-auto mb-4 drop-shadow-lg" onerror="this.style.display='none'">
                <h2 class="text-2xl font-bold mb-4 font-['Montserrat']">ZeroWaste Admin</h2>
                <p class="text-sm opacity-90">Gestión inteligente de residuos para un futuro sostenible.</p>
            </div>
            <div class="absolute inset-0 opacity-10 flex items-center justify-center">
                <span class="material-symbols-outlined text-[200px]">eco</span>
            </div>
        </div>
    </main>

<script>
function togglePass(id, btn) {
    const inp = document.getElementById(id);
    const ico = btn.querySelector('.material-symbols-outlined');
    if (inp.type === 'password') { inp.type = 'text'; ico.textContent = 'visibility'; }
    else { inp.type = 'password'; ico.textContent = 'visibility_off'; }
}

document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('loginForm');
    if (!form) return;
    const submitButton = document.getElementById('login-submit');
    const initialRetry = Number(@json((int) session('retry_after', 0)));
    let retryAfter = Number.isFinite(initialRetry) ? initialRetry : 0;
    if (retryAfter > 0) {
        const updateCountdown = function() {
            submitButton.disabled = true;
            submitButton.textContent = `Espera ${retryAfter}s`;
            retryAfter -= 1;
            if (retryAfter < 0) {
                clearInterval(timer);
                submitButton.disabled = false;
                submitButton.textContent = 'Iniciar Sesión';
            }
        };
        updateCountdown();
        const timer = setInterval(updateCountdown, 1000);
    }

    form.addEventListener('submit', function(e) {
        if (submitButton.disabled) { e.preventDefault(); return; }
        const emailInput = document.getElementById('login-email');
        const passwordInput = document.getElementById('login-password');
        const errEmail = document.getElementById('error-email');
        const errPass = document.getElementById('error-password');

        // Limpiar
        errEmail.classList.add('hidden');
        errPass.classList.add('hidden');
        emailInput.classList.remove('border-red-500');
        passwordInput.classList.remove('border-red-500');

        const email = emailInput.value.trim();
        const password = passwordInput.value;
        let isValid = true;

        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!email || !emailRegex.test(email)) {
            errEmail.textContent = 'El correo es obligatorio y debe contener @.';
            errEmail.classList.remove('hidden');
            emailInput.classList.add('border-red-500');
            isValid = false;
        }

        if (!password) {
            errPass.textContent = 'La contraseña es obligatoria.';
            errPass.classList.remove('hidden');
            passwordInput.classList.add('border-red-500');
            isValid = false;
        }

        if (!isValid) {
            e.preventDefault();
        } else {
            submitButton.disabled = true;
            submitButton.textContent = 'Ingresando…';
        }
    });
});
</script>
</body>
</html>
