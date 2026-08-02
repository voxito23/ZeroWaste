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

    public function approve(Request $request, Post $post)
    {
        DB::transaction(function () use ($post, $request) {
            $lockedPost = DB::table('posts')->where('id', $post->id)->lockForUpdate()->firstOrFail();
            if ($lockedPost->aprobado) return;
            DB::table('posts')->where('id', $post->id)->update(['aprobado'=>true, 'aprobado_por'=>$request->user()->id, 'aprobado_at'=>now()]);
            $rule = DB::table('reglas_puntos')->where('codigo', 'POST_APROBADO')->whereRaw('activa = TRUE')->first();
            if (!$rule || DB::table('movimientos_puntos')->where(['usuario_id'=>$lockedPost->autor_id, 'referencia_tipo'=>'POST_FORO', 'referencia_id'=>(string)$lockedPost->id, 'regla_id'=>$rule->id])->exists()) return;
            $balance = DB::table('saldos_puntos')->where('usuario_id', $lockedPost->autor_id)->lockForUpdate()->first();
            if (!$balance) { DB::table('saldos_puntos')->insert(['usuario_id'=>$lockedPost->autor_id,'puntos_disponibles'=>0,'impacto_historico'=>0,'updated_at'=>now()]); $balance = DB::table('saldos_puntos')->where('usuario_id', $lockedPost->autor_id)->lockForUpdate()->first(); }
            DB::table('saldos_puntos')->where('usuario_id', $lockedPost->autor_id)->update(['puntos_disponibles'=>$balance->puntos_disponibles+$rule->puntos,'impacto_historico'=>$balance->impacto_historico+$rule->puntos,'updated_at'=>now()]);
            DB::table('movimientos_puntos')->insert(['usuario_id'=>$lockedPost->autor_id,'tipo'=>'GANADO','cantidad'=>$rule->puntos,'saldo_anterior'=>$balance->puntos_disponibles,'saldo_nuevo'=>$balance->puntos_disponibles+$rule->puntos,'impacto_anterior'=>$balance->impacto_historico,'impacto_nuevo'=>$balance->impacto_historico+$rule->puntos,'referencia_tipo'=>'POST_FORO','referencia_id'=>(string)$lockedPost->id,'regla_id'=>$rule->id,'descripcion'=>'Publicación aprobada en el foro','administrador_id'=>$request->user()->id,'created_at'=>now()]);
        });
        return back()->with('success', 'Publicación aprobada y puntos registrados.');
    }

    public function destroyRespuesta($id)
    {
        // Assuming Respuesta is a model, but let's just use DB since we don't have the Respuesta model included here, or we can use \App\Models\Respuesta if it exists.
        // It's probably App\Models\Respuesta or App\Models\RespuestaForo. Let's find out what the model is, or just use DB.
        DB::table('respuestas')->where('id', $id)->delete();
        
        return response()->json(['success' => true]);
    }
}
