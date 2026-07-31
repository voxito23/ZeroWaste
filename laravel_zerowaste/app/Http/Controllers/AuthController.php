<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;

class AuthController extends Controller
{
    /**
     * Procesa el inicio de sesión del panel de administración.
     * Valida las credenciales contra la tabla de usuarios.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        $identifier = mb_strtolower(trim($credentials['email']));
        $accountKey = 'admin-login:account:'.hash('sha256', $identifier);
        $ipKey = 'admin-login:ip:'.hash('sha256', (string) $request->ip());
        $accountLockKey = $accountKey.':lock';
        $ipLockKey = $ipKey.':lock';
        $retryAfter = $this->lockRemaining($accountLockKey, $ipLockKey);
        if ($retryAfter > 0) {
            return $this->lockedResponse($request, $retryAfter);
        }

        // Buscar usuario
        $user = \App\Models\User::where('email', $credentials['email'])->first();

        if (!$user) {
            return $this->failedLogin($request, $accountKey, $ipKey, $accountLockKey, $ipLockKey);
        }

        $authenticated = false;

        // Verificar según el tipo de hash almacenado
        if (str_starts_with($user->password, '$2y$')) {
            // Hash bcrypt nativo — usar Auth::attempt normal
            try {
                $authenticated = Auth::attempt($credentials);
            } catch (\Exception $e) {
                $authenticated = false;
            }
        } elseif (str_starts_with($user->password, '$2b$') || str_starts_with($user->password, '$2a$')) {
            // FastAPI bcrypt — verificar manualmente y re-hashear a $2y$
            if (password_verify($credentials['password'], $user->password)) {
                $user->password = \Illuminate\Support\Facades\Hash::make($credentials['password']);
                $user->save();
                Auth::login($user);
                $authenticated = true;
            }
        } elseif (str_starts_with($user->password, 'pbkdf2:')) {
            // Hash werkzeug/pbkdf2 (creado por FastAPI/Flask antiguo) — verificar manualmente
            $authenticated = $this->verifyPbkdf2($credentials['password'], $user->password);
            if ($authenticated) {
                // Re-hashear a bcrypt para que futuros logins funcionen nativamente
                $user->password = \Illuminate\Support\Facades\Hash::make($credentials['password']);
                $user->save();
                Auth::login($user);
            }
        }

        if ($authenticated) {
            RateLimiter::clear($accountKey);
            // Verificar permisos de administrador
            if (!Auth::user()->is_admin) {
                Auth::logout();
                $request->session()->invalidate();
                return back()->withErrors(['email' => 'Acceso restringido. Solo administradores.']);
            }

            $request->session()->regenerate();
            return redirect()->intended(route('dashboard'));
        }

        return $this->failedLogin($request, $accountKey, $ipKey, $accountLockKey, $ipLockKey);
    }

    private function failedLogin(
        Request $request,
        string $accountKey,
        string $ipKey,
        string $accountLockKey,
        string $ipLockKey
    )
    {
        RateLimiter::hit($accountKey, 300);
        RateLimiter::hit($ipKey, 300);
        $locked = false;
        if (RateLimiter::attempts($accountKey) >= 5) {
            Cache::put($accountLockKey, time() + 60, 60);
            RateLimiter::clear($accountKey);
            $locked = true;
        }
        if (RateLimiter::attempts($ipKey) >= 20) {
            Cache::put($ipLockKey, time() + 60, 60);
            RateLimiter::clear($ipKey);
            $locked = true;
        }
        if ($locked) {
            return $this->lockedResponse($request, 60);
        }
        return back()->withErrors(['email' => 'Usuario o contraseña incorrectos.'])
            ->withInput(['email' => $request->input('email')]);
    }

    private function lockRemaining(string ...$lockKeys): int
    {
        $expiresAt = 0;
        foreach ($lockKeys as $key) {
            $expiresAt = max($expiresAt, (int) Cache::get($key, 0));
        }
        return max($expiresAt - time(), 0);
    }

    private function lockedResponse(Request $request, int $retryAfter)
    {
        return back()
            ->withErrors(['email' => 'Demasiados intentos. Espera un minuto antes de volver a intentarlo.'])
            ->with('retry_after', $retryAfter)
            ->withInput(['email' => $request->input('email')])
            ->withHeaders(['Retry-After' => (string) $retryAfter]);
    }

    /**
     * Verifica una contraseña contra un hash werkzeug pbkdf2:sha256.
     * Formato: pbkdf2:sha256:ITERATIONS$SALT$HASH
     */
    private function verifyPbkdf2(string $password, string $storedHash): bool
    {
        // Formato: "pbkdf2:sha256:ITERATIONS$SALT$HASH"
        if (!str_starts_with($storedHash, 'pbkdf2:')) {
            return false;
        }

        try {
            // Separar método e iteraciones del resto
            $parts = explode('$', $storedHash, 3);
            if (count($parts) !== 3) {
                return false;
            }

            $methodParts = explode(':', $parts[0]); // ["pbkdf2", "sha256", "ITERATIONS"]
            $algo = $methodParts[1] ?? 'sha256';
            $iterations = (int)($methodParts[2] ?? 150000);
            $salt = $parts[1];
            $storedHashValue = $parts[2];

            // Generar hash con los mismos parámetros
            $derived = hash_pbkdf2($algo, $password, $salt, $iterations, 32, true);
            $computedHash = base64_encode($derived);

            // Comparar de forma segura
            return hash_equals($storedHashValue, $computedHash);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Cierra la sesión del administrador.
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    }
}
