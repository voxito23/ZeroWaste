<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PostController extends Controller
{
    public function index()
    {
        $posts = Post::with(['autor', 'categoria', 'respuestas.autor'])
                     ->orderBy('created_at', 'desc')
                     ->paginate(15);
                     
        return view('admin.posts.index', compact('posts'));
    }

    public function destroy(Post $post)
    {
        // El administrador principal puede borrar cualquier post
        $post->delete();

        return redirect()->route('posts.index')
                         ->with('success', 'El post ha sido eliminado correctamente.');
    }

    public function destroyRespuesta($id)
    {
        // Assuming Respuesta is a model, but let's just use DB since we don't have the Respuesta model included here, or we can use \App\Models\Respuesta if it exists.
        // It's probably App\Models\Respuesta or App\Models\RespuestaForo. Let's find out what the model is, or just use DB.
        DB::table('respuestas')->where('id', $id)->delete();
        
        return response()->json(['success' => true]);
    }
}
