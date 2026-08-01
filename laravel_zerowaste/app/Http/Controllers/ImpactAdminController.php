<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Support\Media;
use App\Services\AuditLogger;

class ImpactAdminController extends Controller
{
    public function ranking()
    {
        $rows = DB::table('saldos_puntos')->join('usuarios', 'usuarios.id', '=', 'saldos_puntos.usuario_id')
            ->select('usuarios.id', 'usuarios.nombre', 'usuarios.foto_perfil', 'saldos_puntos.impacto_historico', 'saldos_puntos.puntos_disponibles')
            ->orderByDesc('saldos_puntos.impacto_historico')->paginate(50);
        return view('admin.impacto.ranking', compact('rows'));
    }

    public function rewards(Request $request)
    {
        $query = DB::table('recompensas')->whereNull('deleted_at');
        if ($request->filled('q')) $query->where(fn ($q) => $q->where('nombre', 'ilike', '%'.$request->q.'%')->orWhere('descripcion', 'ilike', '%'.$request->q.'%'));
        if ($request->estado === 'activa') $query->where('activa', true);
        if ($request->estado === 'inactiva') $query->where('activa', false);
        if ($request->stock === 'con') $query->where('stock', '>', 0);
        if ($request->stock === 'sin') $query->where('stock', 0);
        $sort = in_array($request->sort, ['nombre', 'costo_puntos', 'stock', 'orden'], true) ? $request->sort : 'orden';
        $rows = $query->orderBy($sort)->orderBy('id')->paginate(12)->withQueryString();
        return view('admin.impacto.recompensas', compact('rows'));
    }

    public function storeReward(Request $request)
    {
        $data = $this->validateReward($request, true);
        if ($request->hasFile('imagen_archivo')) $data['imagen'] = Media::store($request->file('imagen_archivo'), 'recompensas');
        $data['activa'] = $request->boolean('activa');
        $data['created_at'] = now(); $data['updated_at'] = now();
        $id = DB::table('recompensas')->insertGetId($data);
        AuditLogger::record($request, 'reward.created', 'recompensa', $id, ['nombre' => $data['nombre']]);
        return back()->with('success', 'La recompensa fue creada correctamente.');
    }

    public function updateReward(Request $request, int $id)
    {
        $data = $this->validateReward($request);
        $old = DB::table('recompensas')->where('id', $id)->whereNull('deleted_at')->firstOrFail();
        if ($request->hasFile('imagen_archivo')) $data['imagen'] = Media::store($request->file('imagen_archivo'), 'recompensas');
        $data['activa'] = $request->boolean('activa');
        $data['updated_at'] = now();
        DB::table('recompensas')->where('id', $id)->update($data);
        AuditLogger::record($request, 'reward.updated', 'recompensa', $id, ['fields' => array_keys($data)]);
        return back()->with('success', 'Los cambios fueron guardados.');
    }

    public function destroyReward(Request $request, int $id)
    {
        DB::table('recompensas')->where('id', $id)->whereNull('deleted_at')->update(['activa' => false, 'deleted_at' => now(), 'updated_at' => now()]);
        AuditLogger::record($request, 'reward.retired', 'recompensa', $id);
        return back()->with('success', 'La recompensa fue retirada.');
    }

    private function validateReward(Request $request, bool $imageRequired = false): array
    {
        return $request->validate([
            'nombre' => ['required','string','max:150'], 'descripcion' => ['required','string','max:2000'],
            'costo_puntos' => ['required','integer','min:1'], 'stock' => ['required','integer','min:0'],
            'imagen_archivo' => [$imageRequired ? 'required' : 'nullable','image','mimes:jpg,jpeg,png,webp','max:5120'],
            'limite_por_usuario' => ['nullable','integer','min:1'], 'orden' => ['required','integer','min:0'],
            'activa' => ['nullable','boolean'], 'available_at' => ['nullable','date'],
        ]);
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

    public function rules(Request $request)
    {
        $query = DB::table('reglas_puntos');
        if ($request->filled('q')) $query->where(fn ($q) => $q->where('codigo', 'ilike', '%'.$request->q.'%')->orWhere('descripcion', 'ilike', '%'.$request->q.'%'));
        if ($request->estado === 'activa') $query->where('activa', true);
        if ($request->estado === 'inactiva') $query->where('activa', false);
        $rows = $query->orderBy('id')->paginate(20)->withQueryString();
        $history = DB::table('point_rule_history')->leftJoin('usuarios', 'usuarios.id', '=', 'point_rule_history.administrator_id')->select('point_rule_history.*', 'usuarios.nombre as administrator')->orderByDesc('point_rule_history.created_at')->limit(20)->get();
        return view('admin.impacto.reglas', compact('rows', 'history'));
    }

    public function storeRule(Request $request)
    {
        $data = $request->validate(['codigo'=>['required','string','max:60','regex:/^[A-Z0-9_]+$/','unique:reglas_puntos,codigo'], 'puntos'=>'required|integer|min:0|max:100000', 'limite_diario'=>'nullable|integer|min:1|max:1000', 'descripcion'=>'required|string|max:255', 'activa'=>'nullable|boolean']);
        $data['activa'] = $request->boolean('activa'); $data['updated_by'] = $request->user()->id; $data['created_at'] = now(); $data['updated_at'] = now();
        $id = DB::table('reglas_puntos')->insertGetId($data);
        AuditLogger::record($request, 'point_rule.created', 'regla_puntos', $id, ['codigo' => $data['codigo']]);
        return back()->with('success', 'La regla fue creada correctamente.');
    }

    public function updateRule(Request $request, int $id)
    {
        $data = $request->validate(['puntos'=>'required|integer|min:0|max:100000', 'limite_diario'=>'nullable|integer|min:1|max:1000', 'descripcion'=>'required|string|max:255', 'activa'=>'nullable|boolean']);
        $before = DB::table('reglas_puntos')->where('id', $id)->firstOrFail();
        $data['activa'] = $request->boolean('activa'); $data['updated_by'] = $request->user()->id; $data['updated_at'] = now();
        DB::transaction(function () use ($data, $before, $id, $request) {
            DB::table('reglas_puntos')->where('id', $id)->update($data);
            DB::table('point_rule_history')->insert(['rule_id'=>$id, 'before_values'=>json_encode($before), 'after_values'=>json_encode($data), 'administrator_id'=>$request->user()->id, 'created_at'=>now()]);
        });
        AuditLogger::record($request, 'point_rule.updated', 'regla_puntos', $id, ['codigo' => $before->codigo]);
        return back()->with('success', 'Los cambios fueron guardados.');
    }

    public function movements(Request $request)
    {
        $query = $this->movementQuery($request);
        $rows = $query->paginate(50)->withQueryString();
        return view('admin.impacto.movimientos', compact('rows'));
    }

    public function exportMovements(Request $request)
    {
        AuditLogger::record($request, 'points.exported', 'movimientos_puntos', null, ['filters' => $request->except(['_token'])]);
        $rows = $this->movementQuery($request)->limit(10000)->get();
        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Usuario','Tipo','Cantidad','Saldo anterior','Saldo nuevo','Impacto anterior','Impacto nuevo','Referencia','Fecha','Responsable','Motivo']);
            foreach ($rows as $row) fputcsv($out, [$row->usuario,$row->tipo,$row->cantidad,$row->saldo_anterior,$row->saldo_nuevo,$row->impacto_anterior,$row->impacto_nuevo,trim(($row->referencia_tipo ?? '').' '.($row->referencia_id ?? '')),$row->created_at,$row->responsable,$row->descripcion]);
            fclose($out);
        }, 'movimientos-puntos-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function movementQuery(Request $request)
    {
        $query = DB::table('movimientos_puntos')->join('usuarios', 'usuarios.id', '=', 'movimientos_puntos.usuario_id')->leftJoin('usuarios as admins', 'admins.id', '=', 'movimientos_puntos.administrador_id')->select('movimientos_puntos.*', 'usuarios.nombre as usuario', 'admins.nombre as responsable');
        if ($request->filled('q')) $query->where(fn ($q) => $q->where('usuarios.nombre', 'ilike', '%'.$request->q.'%')->orWhere('usuarios.email', 'ilike', '%'.$request->q.'%'));
        if ($request->filled('tipo')) $query->where('movimientos_puntos.tipo', $request->tipo);
        if ($request->filled('referencia')) $query->where(fn ($q) => $q->where('referencia_tipo', 'ilike', '%'.$request->referencia.'%')->orWhere('referencia_id', 'ilike', '%'.$request->referencia.'%'));
        if ($request->filled('desde')) $query->whereDate('movimientos_puntos.created_at', '>=', $request->desde);
        if ($request->filled('hasta')) $query->whereDate('movimientos_puntos.created_at', '<=', $request->hasta);
        return $query->orderByDesc('movimientos_puntos.created_at');
    }
}
