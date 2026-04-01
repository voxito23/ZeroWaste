<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use App\Models\ContactMessage;
use App\Models\ContactReply;
use App\Models\Notificacion;
use App\Models\User;

class ContactMessageController extends Controller
{
    public function index()
    {
        $mensajes = ContactMessage::orderByDesc('created_at')->get();
        return view('admin.mensajes', compact('mensajes'));
    }

    public function updateEstado(Request $request, $id)
    {
        $msg = ContactMessage::findOrFail($id);
        $msg->estado = $request->input('estado', 'revisado');
        $msg->save();
        return redirect()->route('mensajes.index')->with('success', 'Estado actualizado.');
    }

    public function responder(Request $request, $id)
    {
        $request->validate([
            'respuesta_admin' => 'required|string|min:2',
        ]);

        $msg = ContactMessage::findOrFail($id);
        $msg->respuesta_admin = $request->input('respuesta_admin');
        $msg->estado = 'respondido';
        $msg->save();

        // Save to thread table
        ContactReply::create([
            'contact_message_id' => $msg->id,
            'sender' => 'admin',
            'mensaje' => $request->input('respuesta_admin'),
        ]);

        $userId = $msg->usuario_id;
        if (!$userId && $msg->email) {
            $user = User::firstWhere('email', $msg->email);
            if ($user) {
                $userId = $user->id;
                $msg->usuario_id = $userId;
                $msg->save();
            }
        }

        if ($userId) {
            Notificacion::create([
                'user_id' => $userId,
                'titulo' => 'Soporte Administrativo',
                'mensaje' => 'Respuesta a tu mensaje: "' . mb_strimwidth($msg->respuesta_admin, 0, 60, '...') . '"',
                'url' => '/perfil?open_contact=1',
            ]);
        }

        return redirect()->route('mensajes.index')->with('success', 'Respuesta enviada y usuario notificado.');
    }

    /**
     * @param int $id
     * @return mixed
     */
    public function getThread($id)
    {
        $msg = ContactMessage::findOrFail($id);

        /** @var Collection $repliesRaw */
        $repliesRaw = ContactReply::where('contact_message_id', $id)
            ->orderBy('created_at', 'asc')
            ->get();

        $replies = [];
        foreach ($repliesRaw as $r) {
            /** @var ContactReply $r */
            $replies[] = [
                'sender' => $r->sender,
                'mensaje' => $r->mensaje,
                'created_at' => $r->created_at ? $r->created_at->format('d M Y H:i') : '',
            ];
        }

        /** @var \Illuminate\Support\Carbon|null $msgDate */
        $msgDate = $msg->created_at;

        $responseData = [
            'success' => true,
            'original' => [
                'nombre' => $msg->nombre,
                'mensaje' => $msg->mensaje,
                'created_at' => $msgDate ? $msgDate->format('d M Y H:i') : '',
            ],
            'replies' => $replies,
        ];

        header('Content-Type: application/json');
        echo json_encode($responseData);
        exit;
    }
}
