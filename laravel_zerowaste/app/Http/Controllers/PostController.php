<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;

class PostController extends Controller
{
    public function index()
    {
        $posts = Post::with(['autor', 'categoria'])
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
}
