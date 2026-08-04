<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Support\Media;

class UserController extends Controller
{
    public function index(Request $request)
    {
        // Global counts for the cards (ignoring search/pagination)
        $totalCount = User::count();
        $adminCount = User::where('is_admin', 'true')->orWhere('rol', 'admin')->count();
        $userCount  = User::where('is_admin', 'false')->where('rol', 'usuario')->count();
        $recolectorCount = User::where('rol', 'recolector')->count();
        $blockedCount = User::where('bloqueado', 'true')->count();

        // Query for the table
        $query = User::query();

        // Apply Tab Filter
        if ($request->has('tab') && $request->tab !== 'all') {
            if ($request->tab === 'admin') $query->where(function($q){ $q->where('is_admin', 'true')->orWhere('rol', 'admin'); });
            if ($request->tab === 'user') $query->where('is_admin', 'false')->where('rol', 'usuario');
            if ($request->tab === 'recolector') $query->where('rol', 'recolector');
            if ($request->tab === 'blocked') $query->where('bloqueado', 'true');
        }

        // Apply Search Filter
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('ubicacion', 'like', "%{$search}%");
            });
        }

        $usuarios = $query->orderByDesc('created_at')->paginate(6);

        if ($request->ajax()) {
            return view('admin.partials.usuarios_table', compact('usuarios'))->render();
        }

        return view('admin.usuarios', compact('usuarios', 'totalCount', 'adminCount', 'userCount', 'recolectorCount', 'blockedCount'));
    }

    public function checkEmail(Request $request)
    {
        $email = $request->input('email');
        $exists = User::where('email', $email)->count() > 0;
        
        header('Content-Type: application/json');
        echo json_encode(['exists' => $exists]);
        exit;
    }

    public function create()
    {
        return view('admin.usuarios_create');
    }

    public function store(Request $request)
    {
        $rules = [
            'nombre' => 'required|string|min:2|max:30',
            'email' => 'required|email|max:100|unique:usuarios',
            'password' => 'required|string|min:6',
            'ubicacion' => 'nullable|string|max:30',
            'titulo_perfil' => 'nullable|string|max:100',
            'biografia' => 'nullable|string|max:500',
            'foto_perfil' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:15360',
        ];

        $messages = [
            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.min' => 'El nombre debe tener al menos 2 caracteres.',
            'nombre.max' => 'El nombre no puede tener más de 30 caracteres.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'Debe ingresar un correo válido.',
            'email.unique' => 'Este correo ya está registrado.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.min' => 'La contraseña debe tener al menos 6 caracteres.',
            'ubicacion.max' => 'La ubicación no puede exceder los 30 caracteres.',
            'titulo_perfil.max' => 'El título del perfil no puede exceder los 100 caracteres.',
            'biografia.max' => 'La biografía no puede exceder los 500 caracteres.',
            'foto_perfil.image' => 'El archivo debe ser una imagen.',
            'foto_perfil.mimes' => 'La imagen debe ser JPEG, PNG o WebP.',
            'foto_perfil.max' => 'La imagen no debe pesar más de 15 MB.',
            'foto_perfil.uploaded' => 'Error al subir la imagen. Intenta con una de menor tamaño.',
        ];

        $request->validate($rules, $messages);

        $data = $request->only(['nombre', 'password', 'ubicacion', 'titulo_perfil', 'biografia']);
        $data['email'] = strtolower(trim((string) $request->input('email')));
        $data['is_admin'] = $request->boolean('is_admin');
        $data['rol'] = $request->has('is_recolector') ? 'recolector' : ($request->has('is_admin') ? 'admin' : 'usuario');
        $data['auth_provider'] = 'local';
        $data['profile_completed'] = true;
        $data['bloqueado'] = false;
        $data['email_verified_at'] = now();
        $data['foto_perfil'] = 'perfil_default.png';

        $newImage = null;
        if ($request->hasFile('foto_perfil')) {
            try {
                $newImage = Media::store(
                    $request->file('foto_perfil'),
                    'perfiles',
                    15 * 1024 * 1024
                );
                $data['foto_perfil'] = $newImage;
            } catch (\Throwable $error) {
                Log::error('No fue posible guardar la foto del nuevo usuario.', [
                    'exception' => get_class($error),
                ]);

                return back()->withInput()->withErrors([
                    'foto_perfil' => $error instanceof \RuntimeException
                        ? $error->getMessage()
                        : 'No fue posible guardar la fotografía. Puedes crear la cuenta sin imagen.',
                ]);
            }
        }

        try {
            User::create($data);
        } catch (\Throwable $error) {
            Media::discard($newImage, 'perfiles');
            Log::error('No fue posible crear el usuario desde el panel.', [
                'exception' => get_class($error),
            ]);

            return back()->withInput()->withErrors([
                'usuario' => 'No fue posible crear la cuenta. Revisa los datos e inténtalo nuevamente.',
            ]);
        }
        return redirect()->route('usuarios.index')->with('success', 'Usuario creado correctamente.');
    }

    public function edit(User $user)
    {
        // Non-principal admins cannot edit the principal admin
        if ($user->email === config('app.admin_email') && auth()->check() && auth()->user()->email !== config('app.admin_email')) {
            return redirect()->route('usuarios.index')->with('error_admin', 'Solo el administrador principal puede editar su propia cuenta.');
        }
        return view('admin.usuarios_edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        // Non-principal admins cannot update the principal admin
        if ($user->email === config('app.admin_email') && auth()->check() && auth()->user()->email !== config('app.admin_email')) {
            return redirect()->route('usuarios.index')->with('error_admin', 'Solo el administrador principal puede editar su propia cuenta.');
        }

        $rules = [
            'nombre' => 'required|string|min:2|max:30',
            'email' => 'required|email|max:100|unique:usuarios,email,'.$user->id,
            'password' => 'nullable|string|min:6|confirmed',
            'ubicacion' => 'nullable|string|max:30',
            'titulo_perfil' => 'nullable|string|max:100',
            'biografia' => 'nullable|string|max:500',
            'foto_perfil' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:15360',
        ];

        $messages = [
            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.min' => 'El nombre debe tener al menos 2 caracteres.',
            'nombre.max' => 'El nombre no puede tener más de 30 caracteres.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'Debe ingresar un correo válido.',
            'email.unique' => 'Este correo ya está registrado.',
            'password_actual' => 'required_with:password|string',
            'password.min' => 'La contraseña debe tener al menos 6 caracteres.',
            'ubicacion.max' => 'La ubicación no puede exceder los 30 caracteres.',
            'titulo_perfil.max' => 'El título del perfil no puede exceder los 100 caracteres.',
            'biografia.max' => 'La biografía no puede exceder los 500 caracteres.',
            'foto_perfil.image' => 'El archivo debe ser una imagen.',
            'foto_perfil.mimes' => 'La imagen debe ser JPEG, PNG o WebP.',
            'foto_perfil.max' => 'La imagen no debe pesar más de 15 MB.',
            'foto_perfil.uploaded' => 'Error al subir la imagen. Intenta con una de menor tamaño.',
        ];

        $request->validate($rules, $messages);

        $data = $request->only(['nombre', 'email', 'ubicacion', 'titulo_perfil', 'biografia']);
        $data['is_admin'] = $request->has('is_admin') ? 'true' : 'false';
        $data['bloqueado'] = $request->has('bloqueado') ? 'true' : 'false';
        $data['rol'] = $request->has('is_recolector') ? 'recolector' : ($request->has('is_admin') ? 'admin' : 'usuario');

        if ($request->filled('password')) {
            if (!\Illuminate\Support\Facades\Hash::check($request->input('password_actual'), $user->password)) {
                return back()->withErrors(['password_actual' => 'La contraseña anterior es incorrecta.'])->withInput();
            }
            $data['password'] = \Illuminate\Support\Facades\Hash::make($request->input('password'));
        }

        $previousImage = $user->foto_perfil;
        $newImage = null;
        if ($request->hasFile('foto_perfil')) {
            try {
                $newImage = Media::store(
                    $request->file('foto_perfil'),
                    'perfiles',
                    15 * 1024 * 1024
                );
                $data['foto_perfil'] = $newImage;
            } catch (\Throwable $error) {
                Log::error('No fue posible guardar la foto del usuario desde el panel.', [
                    'exception' => get_class($error),
                    'user_id' => $user->id,
                ]);

                return back()->withInput()->withErrors([
                    'foto_perfil' => $error instanceof \RuntimeException
                        ? $error->getMessage()
                        : 'No fue posible guardar la fotografía. Intenta con otra imagen.',
                ]);
            }
        }

        try {
            $user->update($data);
        } catch (\Throwable $error) {
            Media::discard($newImage, 'perfiles');
            Log::error('No fue posible actualizar el usuario desde el panel.', [
                'exception' => get_class($error),
                'user_id' => $user->id,
            ]);

            return back()->withInput()->withErrors([
                'perfil' => 'No fue posible guardar los cambios del usuario. Inténtalo nuevamente.',
            ]);
        }
        if ($newImage !== null && is_string($previousImage) && $previousImage !== $newImage) {
            Media::discard(
                basename(parse_url($previousImage, PHP_URL_PATH) ?: $previousImage),
                'perfiles'
            );
        }
        return redirect()->route('usuarios.index')->with('success', 'Usuario actualizado correctamente.');
    }

    public function destroy(User $user)
    {
        // Proteger al admin principal
        if ($user->email === config('app.admin_email')) {
            return redirect()->route('usuarios.index')->with('error_admin', 'No se puede eliminar la cuenta de administrador principal.');
        }

        // Proteger al admin logueado de eliminarse a sí mismo
        if (auth()->check() && auth()->id() === $user->id) {
            return redirect()->route('usuarios.index')->with('error_admin', 'No puedes eliminar tu propia cuenta.');
        }

        // Cualquier admin que no sea el principal no puede eliminar a otro admin
        if ($user->is_admin && auth()->check() && auth()->user()->email !== config('app.admin_email')) {
            return redirect()->route('usuarios.index')->with('error_admin', 'Solo el administrador principal puede eliminar a otros administradores.');
        }

        // Limpiar posteos y datos relacionados con posteos
        $postIds = \Illuminate\Support\Facades\DB::table('posts')->where('autor_id', $user->id)->pluck('id');
        if ($postIds->isNotEmpty()) {
            \Illuminate\Support\Facades\DB::table('likes_foro')->whereIn('post_id', $postIds)->delete();
            \Illuminate\Support\Facades\DB::table('respuestas')->whereIn('post_id', $postIds)->delete();
            \Illuminate\Support\Facades\DB::table('posts')->where('autor_id', $user->id)->delete();
        }

        // Limpiar mensajes de contacto (si existen)
        $contactIds = \Illuminate\Support\Facades\DB::table('contact_messages')->where('usuario_id', $user->id)->pluck('id');
        if ($contactIds->isNotEmpty()) {
             \Illuminate\Support\Facades\DB::table('contact_replies')->whereIn('contact_message_id', $contactIds)->delete();
             \Illuminate\Support\Facades\DB::table('contact_messages')->where('usuario_id', $user->id)->delete();
        }

        // Limpiar actividad adicional (ratings, notis, etc)
        \Illuminate\Support\Facades\DB::table('likes_foro')->where('usuario_id', $user->id)->delete();
        \Illuminate\Support\Facades\DB::table('respuestas')->where('autor_id', $user->id)->delete();
        \Illuminate\Support\Facades\DB::table('calificaciones_puntos')->where('usuario_id', $user->id)->delete();
        \Illuminate\Support\Facades\DB::table('actividades')->where('usuario_id', $user->id)->delete();
        \Illuminate\Support\Facades\DB::table('notificaciones')->where('user_id', $user->id)->delete();
        \Illuminate\Support\Facades\DB::table('password_reset_requests')->where('email', $user->email)->delete();

        $user->delete();
        return redirect()->route('usuarios.index')->with('success', 'Usuario y todos sus datos relacionados eliminados correctamente.');
    }
}
