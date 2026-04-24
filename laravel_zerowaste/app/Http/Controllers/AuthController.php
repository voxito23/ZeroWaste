<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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

        // Buscar usuario
        $user = \App\Models\User::where('email', $credentials['email'])->first();

        if (!$user) {
            return back()->withErrors([
                'email' => 'Las credenciales proporcionadas no son correctas.',
            ])->withInput(['email' => $request->input('email')]);
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
            // Verificar permisos de administrador
            if (!Auth::user()->is_admin) {
                Auth::logout();
                $request->session()->invalidate();
                return back()->withErrors(['email' => 'Acceso restringido. Solo administradores.']);
            }

            $request->session()->regenerate();
            return redirect()->intended(route('dashboard'));
        }

        return back()->withErrors([
            'email' => 'Las credenciales proporcionadas no son correctas.',
        ])->withInput(['email' => $request->input('email')]);
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
