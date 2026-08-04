<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Support\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\AuditLogger;

class PostController extends Controller
{
    public function index()
    {
        $posts = Post::with(['autor', 'categoria', 'respuestas.autor'])
                     ->orderBy('created_at', 'desc')
                     ->paginate(15);
                     
        return view('admin.posts.index', compact('posts'));
    }

    public function destroy(Request $request, Post $post)
    {
        $postId = $post->id;
        $image = $post->imagen;

        try {
            DB::transaction(function () use ($request, $postId, $image) {
                DB::table('posts')->where('id', $postId)->lockForUpdate()->firstOrFail();

                $replyIds = DB::table('respuestas')->where('post_id', $postId)->pluck('id');
                if ($replyIds->isNotEmpty()) {
                    DB::table('notificaciones')->whereIn('comment_id', $replyIds)->delete();
                }

                DB::table('notificaciones')->where('post_id', $postId)->delete();
                DB::table('likes_foro')->where('post_id', $postId)->delete();
                DB::table('respuestas')->where('post_id', $postId)->delete();
                DB::table('posts')->where('id', $postId)->delete();

                AuditLogger::record($request, 'forum_post.deleted', 'post', $postId, [
                    'had_image' => filled($image),
                ]);
            });
        } catch (\Throwable $error) {
            Log::error('No fue posible eliminar una publicación.', [
                'exception' => get_class($error),
                'post_id' => $postId,
            ]);

            return back()->with('error', 'No fue posible eliminar la publicación. Ningún cambio fue aplicado.');
        }

        try {
            Media::discard($image, 'foro');
        } catch (\Throwable $error) {
            Log::warning('La publicación se eliminó, pero su archivo no pudo limpiarse.', [
                'exception' => get_class($error),
                'post_id' => $postId,
            ]);
        }

        return redirect()->route('posts.index')
                         ->with('success', 'La publicación y sus comentarios se eliminaron correctamente.');
    }

    public function approve(Request $request, Post $post)
    {
        try {
            DB::transaction(function () use ($post, $request) {
                $lockedPost = DB::table('posts')->where('id', $post->id)->lockForUpdate()->firstOrFail();
                if ($lockedPost->aprobado) return;
                DB::table('posts')->where('id', $post->id)->update(['aprobado'=>DB::raw('TRUE'), 'aprobado_por'=>$request->user()->id, 'aprobado_at'=>now()]);
                $rule = DB::table('reglas_puntos')->where('codigo', 'POST_APROBADO')->whereRaw('activa = TRUE')->first();
                if ($rule && !DB::table('movimientos_puntos')->where(['usuario_id'=>$lockedPost->autor_id, 'referencia_tipo'=>'POST_FORO', 'referencia_id'=>(string)$lockedPost->id, 'regla_id'=>$rule->id])->exists()) {
                    $balance = DB::table('saldos_puntos')->where('usuario_id', $lockedPost->autor_id)->lockForUpdate()->first();
                    if (!$balance) { DB::table('saldos_puntos')->insert(['usuario_id'=>$lockedPost->autor_id,'puntos_disponibles'=>0,'impacto_historico'=>0,'updated_at'=>now()]); $balance = DB::table('saldos_puntos')->where('usuario_id', $lockedPost->autor_id)->lockForUpdate()->first(); }
                    DB::table('saldos_puntos')->where('usuario_id', $lockedPost->autor_id)->update(['puntos_disponibles'=>$balance->puntos_disponibles+$rule->puntos,'impacto_historico'=>$balance->impacto_historico+$rule->puntos,'updated_at'=>now()]);
                    DB::table('movimientos_puntos')->insert(['usuario_id'=>$lockedPost->autor_id,'tipo'=>'GANADO','cantidad'=>$rule->puntos,'saldo_anterior'=>$balance->puntos_disponibles,'saldo_nuevo'=>$balance->puntos_disponibles+$rule->puntos,'impacto_anterior'=>$balance->impacto_historico,'impacto_nuevo'=>$balance->impacto_historico+$rule->puntos,'referencia_tipo'=>'POST_FORO','referencia_id'=>(string)$lockedPost->id,'regla_id'=>$rule->id,'descripcion'=>'Publicación aprobada en el foro','administrador_id'=>$request->user()->id,'created_at'=>now()]);
                }
                AuditLogger::record($request, 'forum_post.approved', 'post', $lockedPost->id, ['points_rule' => $rule?->codigo]);
            });
        } catch (\Throwable $error) {
            Log::error('No fue posible aprobar una publicación.', ['exception' => get_class($error), 'post_id' => $post->id]);
            return back()->with('error', 'No fue posible aprobar la publicación. Ningún cambio fue aplicado.');
        }
        return back()->with('success', 'Publicación aprobada. Si la regla está activa, sus puntos quedaron registrados.');
    }

    public function destroyRespuesta($id)
    {
        // Assuming Respuesta is a model, but let's just use DB since we don't have the Respuesta model included here, or we can use \App\Models\Respuesta if it exists.
        // It's probably App\Models\Respuesta or App\Models\RespuestaForo. Let's find out what the model is, or just use DB.
        DB::table('respuestas')->where('id', $id)->delete();
        
        return response()->json(['success' => true]);
    }
}
