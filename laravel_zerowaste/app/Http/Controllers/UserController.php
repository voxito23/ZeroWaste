<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    public function index()
    {
        $usuarios = User::all();
        return view('admin.usuarios', compact('usuarios'));
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
            'nombre' => 'required|string|min:10|max:20',
            'email' => 'required|email|max:100|unique:usuarios',
            'password' => 'required|string|min:6',
            'ubicacion' => 'required|string|min:10|max:20',
            'titulo_perfil' => 'required|string|min:10|max:30',
            'biografia' => 'nullable|string|max:100',
            'foto_perfil' => 'nullable|image|max:2048',
        ];

        $messages = [
            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.min' => 'El nombre debe tener al menos 10 caracteres.',
            'nombre.max' => 'El nombre no puede tener más de 20 caracteres.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'Debe ingresar un correo válido.',
            'email.unique' => 'Este correo ya está registrado.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.min' => 'La contraseña debe tener al menos 6 caracteres.',
            'ubicacion.required' => 'La ubicación es obligatoria.',
            'ubicacion.min' => 'La ubicación debe tener al menos 10 caracteres.',
            'ubicacion.max' => 'La ubicación no puede exceder los 20 caracteres.',
            'titulo_perfil.required' => 'El título del perfil es obligatorio.',
            'titulo_perfil.min' => 'El título del perfil debe tener al menos 10 caracteres.',
            'titulo_perfil.max' => 'El título del perfil no puede exceder los 30 caracteres.',
            'biografia.max' => 'La biografía no puede exceder los 100 caracteres.',
            'foto_perfil.image' => 'El archivo debe ser una imagen.',
            'foto_perfil.max' => 'La imagen no debe pesar más de 2MB.',
            'foto_perfil.uploaded' => 'Error al subir la imagen. Intenta con una de menor tamaño.',
        ];

        $request->validate($rules, $messages);

        $data = $request->only(['nombre', 'email', 'password', 'ubicacion', 'titulo_perfil', 'biografia']);
        $data['is_admin'] = $request->has('is_admin') ? true : false;
        $data['auth_provider'] = 'local';
        $data['profile_completed'] = true;

        // Manejar subida de foto de perfil
        if ($request->hasFile('foto_perfil')) {
            try {
                $foto = $request->file('foto_perfil');
                $nombreFoto = uniqid() . '.' . $foto->getClientOriginalExtension();

                // Guardar en el volumen compartido (named volume perfiles_compartidos)
                $destinoCompartido = public_path('img/perfiles');
                if (!file_exists($destinoCompartido)) { mkdir($destinoCompartido, 0777, true); }
                $foto->move($destinoCompartido, $nombreFoto);

                $data['foto_perfil'] = $nombreFoto;
            } catch (\Exception $e) {
                // Si falla la subida de imagen, continuar sin foto
                error_log('No se pudo guardar la foto de perfil: ' . $e->getMessage());
            }
        }

        User::create($data);
        return redirect()->route('usuarios.index')->with('success', 'Usuario creado correctamente.');
    }

    public function edit(User $user)
    {
        return view('admin.usuarios_edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $rules = [
            'nombre' => 'required|string|min:10|max:20',
            'email' => 'required|email|max:100|unique:usuarios,email,'.$user->id,
            'password' => 'nullable|string|min:6',
            'ubicacion' => 'required|string|min:10|max:20',
            'titulo_perfil' => 'required|string|min:10|max:30',
            'biografia' => 'nullable|string|max:100',
            'foto_perfil' => 'nullable|image|max:2048',
        ];

        $messages = [
            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.min' => 'El nombre debe tener al menos 10 caracteres.',
            'nombre.max' => 'El nombre no puede tener más de 20 caracteres.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'Debe ingresar un correo válido.',
            'email.unique' => 'Este correo ya está registrado.',
            'password.min' => 'La contraseña debe tener al menos 6 caracteres.',
            'ubicacion.required' => 'La ubicación es obligatoria.',
            'ubicacion.min' => 'La ubicación debe tener al menos 10 caracteres.',
            'ubicacion.max' => 'La ubicación no puede exceder los 20 caracteres.',
            'titulo_perfil.required' => 'El título del perfil es obligatorio.',
            'titulo_perfil.min' => 'El título del perfil debe tener al menos 10 caracteres.',
            'titulo_perfil.max' => 'El título del perfil no puede exceder los 30 caracteres.',
            'biografia.max' => 'La biografía no puede exceder los 100 caracteres.',
            'foto_perfil.image' => 'El archivo debe ser una imagen.',
            'foto_perfil.max' => 'La imagen no debe pesar más de 2MB.',
            'foto_perfil.uploaded' => 'Error al subir la imagen. Intenta con una de menor tamaño.',
        ];

        $request->validate($rules, $messages);

        $data = $request->only(['nombre', 'email', 'ubicacion', 'titulo_perfil', 'biografia']);
        $data['is_admin'] = $request->has('is_admin') ? true : false;

        if ($request->filled('password')) {
            $data['password'] = $request->input('password');
        }

        // Manejar subida de foto de perfil
        if ($request->hasFile('foto_perfil')) {
            try {
                $foto = $request->file('foto_perfil');
                $nombreFoto = uniqid() . '.' . $foto->getClientOriginalExtension();

                // Guardar en el volumen compartido (named volume perfiles_compartidos)
                $destinoCompartido = public_path('img/perfiles');
                if (!file_exists($destinoCompartido)) { mkdir($destinoCompartido, 0777, true); }
                $foto->move($destinoCompartido, $nombreFoto);

                $data['foto_perfil'] = $nombreFoto;
            } catch (\Exception $e) {
                error_log('No se pudo actualizar la foto de perfil: ' . $e->getMessage());
            }
        }

        $user->update($data);
        return redirect()->route('usuarios.index')->with('success', 'Usuario actualizado correctamente.');
    }

    public function destroy(User $user)
    {
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
        \Illuminate\Support\Facades\DB::table('password_reset_requests')->where('usuario_id', $user->id)->delete();

        $user->delete();
        return redirect()->route('usuarios.index')->with('success', 'Usuario y todos sus datos relacionados eliminados correctamente.');
    }
}
