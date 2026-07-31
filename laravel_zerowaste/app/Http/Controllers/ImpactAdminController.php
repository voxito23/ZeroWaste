<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ImpactAdminController extends Controller
{
    public function ranking()
    {
        $rows = DB::table('saldos_puntos')->join('usuarios', 'usuarios.id', '=', 'saldos_puntos.usuario_id')
            ->select('usuarios.id', 'usuarios.nombre', 'usuarios.foto_perfil', 'saldos_puntos.impacto_historico', 'saldos_puntos.puntos_disponibles')
            ->orderByDesc('saldos_puntos.impacto_historico')->paginate(50);
        return view('admin.impacto.ranking', compact('rows'));
    }

    public function rewards()
    {
        $rows = DB::table('recompensas')->orderBy('orden')->orderBy('id')->paginate(50);
        return view('admin.impacto.recompensas', compact('rows'));
    }

    public function updateReward(Request $request, int $id)
    {
        $data = $request->validate([
            'nombre' => 'required|string|max:150', 'descripcion' => 'required|string|max:2000',
            'costo_puntos' => 'required|integer|min:1', 'stock' => 'required|integer|min:0',
            'imagen' => 'nullable|string|max:255', 'limite_por_usuario' => 'nullable|integer|min:1',
            'orden' => 'required|integer|min:0', 'activa' => 'nullable|boolean',
        ]);
        $data['activa'] = $request->boolean('activa');
        $data['updated_at'] = now();
        DB::table('recompensas')->where('id', $id)->update($data);
        return back()->with('success', 'Recompensa actualizada.');
    }

    public function redemptions()
    {
        $rows = DB::table('canjes')->join('usuarios', 'usuarios.id', '=', 'canjes.usuario_id')
            ->join('recompensas', 'recompensas.id', '=', 'canjes.recompensa_id')
            ->select('canjes.*', 'usuarios.nombre as usuario', 'recompensas.nombre as recompensa')
            ->orderByDesc('canjes.created_at')->paginate(50);
        return view('admin.impacto.canjes', compact('rows'));
    }

    public function updateRedemption(Request $request, int $id)
    {
        $data = $request->validate(['estado' => 'required|in:SOLICITADA,APROBADA,EN_PREPARACION,LISTA_PARA_ENTREGAR,ENTREGADA,RECHAZADA,CANCELADA', 'motivo' => 'nullable|string|max:255']);
        DB::transaction(function () use ($data, $id, $request) {
            $row = DB::table('canjes')->where('id', $id)->lockForUpdate()->firstOrFail();
            if (in_array($row->estado, ['ENTREGADA', 'RECHAZADA', 'CANCELADA'], true)) abort(409, 'El canje ya está cerrado.');
            if (in_array($data['estado'], ['RECHAZADA', 'CANCELADA'], true)) {
                $balance = DB::table('saldos_puntos')->where('usuario_id', $row->usuario_id)->lockForUpdate()->firstOrFail();
                DB::table('saldos_puntos')->where('usuario_id', $row->usuario_id)->update([
                    'puntos_disponibles' => $balance->puntos_disponibles + $row->puntos_utilizados,
                    'updated_at' => now(),
                ]);
                DB::table('recompensas')->where('id', $row->recompensa_id)->increment('stock', $row->cantidad);
                DB::table('movimientos_puntos')->insert([
                    'usuario_id'=>$row->usuario_id, 'tipo'=>'DEVOLUCIÓN', 'cantidad'=>$row->puntos_utilizados,
                    'saldo_anterior'=>$balance->puntos_disponibles, 'saldo_nuevo'=>$balance->puntos_disponibles + $row->puntos_utilizados,
                    'impacto_anterior'=>$balance->impacto_historico, 'impacto_nuevo'=>$balance->impacto_historico,
                    'referencia_tipo'=>'DEVOLUCION_CANJE', 'referencia_id'=>(string)$row->id, 'regla_id'=>null,
                    'descripcion'=>'Devolución por canje '.$data['estado'], 'administrador_id'=>$request->user()->id, 'created_at'=>now(),
                ]);
            }
            DB::table('canjes')->where('id', $id)->update(['estado'=>$data['estado'], 'administrador_id'=>$request->user()->id, 'updated_at'=>now()]);
            DB::table('historial_canjes')->insert(['canje_id'=>$id, 'estado_anterior'=>$row->estado, 'estado_nuevo'=>$data['estado'], 'administrador_id'=>$request->user()->id, 'motivo'=>$data['motivo'] ?? null, 'created_at'=>now()]);
        });
        return back()->with('success', 'Estado del canje actualizado.');
    }

    public function rules()
    {
        $rows = DB::table('reglas_puntos')->orderBy('id')->get();
        return view('admin.impacto.reglas', compact('rows'));
    }

    public function updateRule(Request $request, int $id)
    {
        $data = $request->validate(['puntos'=>'required|integer|min:0|max:100000', 'limite_diario'=>'nullable|integer|min:1|max:1000', 'descripcion'=>'required|string|max:255', 'activa'=>'nullable|boolean']);
        $data['activa'] = $request->boolean('activa'); $data['updated_by'] = $request->user()->id; $data['updated_at'] = now();
        DB::table('reglas_puntos')->where('id', $id)->update($data);
        return back()->with('success', 'Regla actualizada.');
    }

    public function movements()
    {
        $rows = DB::table('movimientos_puntos')->join('usuarios', 'usuarios.id', '=', 'movimientos_puntos.usuario_id')
            ->select('movimientos_puntos.*', 'usuarios.nombre as usuario')->orderByDesc('movimientos_puntos.created_at')->paginate(100);
        return view('admin.impacto.movimientos', compact('rows'));
    }
}
