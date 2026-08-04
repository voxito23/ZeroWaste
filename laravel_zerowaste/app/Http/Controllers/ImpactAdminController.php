<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
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
        if ($request->estado === 'activa') $query->whereRaw('activa = TRUE');
        if ($request->estado === 'inactiva') $query->whereRaw('activa = FALSE');
        if ($request->stock === 'con') $query->where('stock', '>', 0);
        if ($request->stock === 'sin') $query->where('stock', 0);
        $sort = in_array($request->sort, ['nombre', 'costo_puntos', 'stock', 'orden'], true) ? $request->sort : 'orden';
        $rows = $query->orderBy($sort)->orderBy('id')->paginate(12)->withQueryString();
        return view('admin.impacto.recompensas', compact('rows'));
    }

    public function editReward(int $id): View
    {
        $reward = DB::table('recompensas')->where('id', $id)->whereNull('deleted_at')->firstOrFail();

        return view('admin.impacto.recompensas-edit', compact('reward'));
    }

    public function storeReward(Request $request)
    {
        $data = $this->validateReward($request, true);
        unset($data['imagen_archivo']);
        $newImage = null;

        try {
            if ($request->hasFile('imagen_archivo')) {
                $newImage = Media::store($request->file('imagen_archivo'), 'recompensas');
                $data['imagen'] = $newImage;
            }
            $data['nombre'] = trim($data['nombre']);
            $data['descripcion'] = trim($data['descripcion']);
            $data['activa'] = DB::raw($request->boolean('activa') ? 'TRUE' : 'FALSE');
            $data['created_at'] = now();
            $data['updated_at'] = now();

            DB::transaction(function () use ($data, $request) {
                $id = DB::table('recompensas')->insertGetId($data);
                AuditLogger::record($request, 'reward.created', 'recompensa', $id, ['nombre' => $data['nombre']]);
            });
        } catch (\Throwable $error) {
            Media::discard($newImage, 'recompensas');
            Log::error('No fue posible crear una recompensa.', ['exception' => get_class($error)]);

            return back()->withInput()->with('error', 'No fue posible crear la recompensa. Verifica el almacenamiento de imágenes e inténtalo nuevamente.');
        }

        return back()->with('success', 'La recompensa fue creada correctamente.');
    }

    public function updateReward(Request $request, int $id): RedirectResponse
    {
        $data = $this->validateReward($request);
        unset($data['imagen_archivo']);
        $newImage = null;
        try {
            if ($request->hasFile('imagen_archivo')) {
                $newImage = Media::store($request->file('imagen_archivo'), 'recompensas');
                $data['imagen'] = $newImage;
            }
            $data['nombre'] = trim($data['nombre']);
            $data['descripcion'] = trim($data['descripcion']);
            $data['activa'] = DB::raw($request->boolean('activa') ? 'TRUE' : 'FALSE');
            $data['updated_at'] = now();
            DB::transaction(function () use ($data, $id, $request) {
                DB::table('recompensas')->where('id', $id)->whereNull('deleted_at')->lockForUpdate()->firstOrFail();
                DB::table('recompensas')->where('id', $id)->update($data);
                AuditLogger::record($request, 'reward.updated', 'recompensa', $id, ['fields' => array_keys($data)]);
            });
        } catch (\Throwable $error) {
            Media::discard($newImage, 'recompensas');
            Log::error('No fue posible actualizar una recompensa.', ['exception' => get_class($error), 'reward_id' => $id]);
            return back()->withInput()->with('error', 'No fue posible guardar la recompensa. Inténtalo nuevamente.');
        }

        return redirect()->route('impacto.recompensas')->with('success', 'Los cambios de la recompensa fueron guardados.');
    }

    public function destroyReward(Request $request, int $id)
    {
        DB::table('recompensas')->where('id', $id)->whereNull('deleted_at')->update(['activa' => DB::raw('FALSE'), 'deleted_at' => now(), 'updated_at' => now()]);
        AuditLogger::record($request, 'reward.retired', 'recompensa', $id);
        return back()->with('success', 'La recompensa fue retirada.');
    }

    private function validateReward(Request $request, bool $imageRequired = false): array
    {
        return $request->validate([
            'nombre' => ['required','string','max:150',$this->maximumWords(12)],
            'descripcion' => ['required','string','max:2000',$this->maximumWords(120)],
            'costo_puntos' => ['required','integer','min:1'], 'stock' => ['required','integer','min:0'],
            'imagen_archivo' => [$imageRequired ? 'required' : 'nullable','image','mimes:jpg,jpeg,png,webp','max:5120'],
            'limite_por_usuario' => ['nullable','integer','min:1'], 'orden' => ['required','integer','min:0'],
            'activa' => ['nullable','boolean'], 'available_at' => ['nullable','date'],
        ], [
            'nombre.required' => 'Escribe el nombre de la recompensa.',
            'descripcion.required' => 'Escribe una descripción para la tienda.',
            'imagen_archivo.required' => 'Selecciona la imagen que se mostrará en la tienda.',
            'imagen_archivo.max' => 'La imagen no debe superar 5 MB.',
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
        $changed = DB::transaction(function () use ($data, $id, $request) {
            $row = DB::table('canjes')->where('id', $id)->lockForUpdate()->firstOrFail();
            if ($row->estado === $data['estado']) {
                return false;
            }
            if (in_array($row->estado, ['ENTREGADA', 'RECHAZADA', 'CANCELADA'], true)) {
                throw ValidationException::withMessages([
                    'estado' => 'Este canje ya tiene un estado final y no puede modificarse nuevamente.',
                ]);
            }
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
            AuditLogger::record($request, 'redemption.status_updated', 'canje', $id, [
                'estado_anterior' => $row->estado,
                'estado_nuevo' => $data['estado'],
            ]);

            return true;
        });
        return back()->with('success', $changed ? 'El estado del canje fue actualizado.' : 'El canje ya se encontraba en ese estado.');
    }

    public function rules(Request $request)
    {
        $query = DB::table('reglas_puntos');
        if ($request->filled('q')) $query->where(fn ($q) => $q->where('codigo', 'ilike', '%'.$request->q.'%')->orWhere('descripcion', 'ilike', '%'.$request->q.'%'));
        if ($request->estado === 'activa') $query->whereRaw('activa = TRUE');
        if ($request->estado === 'inactiva') $query->whereRaw('activa = FALSE');
        $rows = $query->orderBy('id')->paginate(20)->withQueryString();
        $history = DB::table('point_rule_history')->leftJoin('usuarios', 'usuarios.id', '=', 'point_rule_history.administrator_id')->select('point_rule_history.*', 'usuarios.nombre as administrator')->orderByDesc('point_rule_history.created_at')->limit(20)->get();
        return view('admin.impacto.reglas', compact('rows', 'history'));
    }

    public function editRule(int $id): View
    {
        $rule = DB::table('reglas_puntos')->where('id', $id)->firstOrFail();

        return view('admin.impacto.reglas-edit', compact('rule'));
    }

    public function storeRule(Request $request)
    {
        $data = $request->validate(['codigo'=>['required','string','max:60','regex:/^[A-Z0-9_]+$/','unique:reglas_puntos,codigo'], 'puntos'=>'required|integer|min:0|max:100000', 'limite_diario'=>'nullable|integer|min:1|max:1000', 'descripcion'=>['required','string','max:255',$this->maximumWords(35)], 'activa'=>'nullable|boolean']);
        $data['descripcion'] = trim($data['descripcion']);
        $data['activa'] = DB::raw($request->boolean('activa') ? 'TRUE' : 'FALSE'); $data['updated_by'] = $request->user()->id; $data['created_at'] = now(); $data['updated_at'] = now();
        $id = DB::table('reglas_puntos')->insertGetId($data);
        AuditLogger::record($request, 'point_rule.created', 'regla_puntos', $id, ['codigo' => $data['codigo']]);
        return back()->with('success', 'La regla fue creada correctamente.');
    }

    public function updateRule(Request $request, int $id): RedirectResponse
    {
        $validated = $request->validate([
            'puntos' => ['required', 'integer', 'min:0', 'max:100000'],
            'limite_diario' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'descripcion' => ['required', 'string', 'max:255', $this->maximumWords(35)],
            'activa' => ['nullable', 'boolean'],
        ], [
            'puntos.required' => 'Indica cuántos puntos otorga la regla.',
            'puntos.integer' => 'Los puntos deben ser un número entero.',
            'limite_diario.min' => 'El límite diario debe ser de al menos una aplicación.',
            'descripcion.required' => 'La descripción es obligatoria.',
        ]);
        $active = $request->boolean('activa');
        $updateData = [
            'puntos' => (int) $validated['puntos'],
            'limite_diario' => isset($validated['limite_diario']) ? (int) $validated['limite_diario'] : null,
            'descripcion' => trim($validated['descripcion']),
            'activa' => DB::raw($active ? 'TRUE' : 'FALSE'),
            'updated_by' => $request->user()->id,
            'updated_at' => now(),
        ];
        $afterValues = [
            'puntos' => $updateData['puntos'],
            'limite_diario' => $updateData['limite_diario'],
            'descripcion' => $updateData['descripcion'],
            'activa' => $active,
        ];

        try {
            DB::transaction(function () use ($updateData, $afterValues, $id, $request) {
                $before = DB::table('reglas_puntos')->where('id', $id)->lockForUpdate()->firstOrFail();
                DB::table('reglas_puntos')->where('id', $id)->update($updateData);
                DB::table('point_rule_history')->insert([
                    'rule_id' => $id,
                    'before_values' => json_encode($before, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
                    'after_values' => json_encode($afterValues, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
                    'administrator_id' => $request->user()->id,
                    'created_at' => now(),
                ]);
                AuditLogger::record($request, 'point_rule.updated', 'regla_puntos', $id, ['codigo' => $before->codigo]);
            });
        } catch (\Throwable $error) {
            Log::error('No fue posible actualizar una regla de puntos.', ['exception' => get_class($error), 'rule_id' => $id]);
            return back()->withInput()->with('error', 'No fue posible guardar la regla. Ningún cambio fue aplicado; inténtalo nuevamente.');
        }

        return redirect()->route('impacto.reglas')->with('success', 'La regla de puntos fue actualizada correctamente.');
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
        $endDateRules = ['nullable', 'date_format:Y-m-d'];
        if ($request->filled('desde')) {
            $endDateRules[] = 'after_or_equal:desde';
        }
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:150'],
            'tipo' => ['nullable', 'in:GANADO,CANJE,DEVOLUCIÓN,AJUSTE'],
            'referencia' => ['nullable', 'string', 'max:100'],
            'desde' => ['nullable', 'date_format:Y-m-d'],
            'hasta' => $endDateRules,
        ], [
            'tipo.in' => 'Selecciona un tipo de movimiento válido.',
            'desde.date_format' => 'La fecha inicial no es válida.',
            'hasta.date_format' => 'La fecha final no es válida.',
            'hasta.after_or_equal' => 'La fecha final debe ser igual o posterior a la fecha inicial.',
        ]);
        $query = DB::table('movimientos_puntos')->join('usuarios', 'usuarios.id', '=', 'movimientos_puntos.usuario_id')->leftJoin('usuarios as admins', 'admins.id', '=', 'movimientos_puntos.administrador_id')->select('movimientos_puntos.*', 'usuarios.nombre as usuario', 'admins.nombre as responsable');
        if (! empty($filters['q'])) $query->where(fn ($q) => $q->where('usuarios.nombre', 'ilike', '%'.$filters['q'].'%')->orWhere('usuarios.email', 'ilike', '%'.$filters['q'].'%'));
        if (! empty($filters['tipo'])) $query->where('movimientos_puntos.tipo', $filters['tipo']);
        if (! empty($filters['referencia'])) $query->where(fn ($q) => $q->where('referencia_tipo', 'ilike', '%'.$filters['referencia'].'%')->orWhere('referencia_id', 'ilike', '%'.$filters['referencia'].'%'));
        if (! empty($filters['desde'])) $query->whereDate('movimientos_puntos.created_at', '>=', $filters['desde']);
        if (! empty($filters['hasta'])) $query->whereDate('movimientos_puntos.created_at', '<=', $filters['hasta']);
        return $query->orderByDesc('movimientos_puntos.created_at');
    }

    private function maximumWords(int $maximum): \Closure
    {
        return static function (string $attribute, mixed $value, \Closure $fail) use ($maximum): void {
            if (! is_string($value)) {
                return;
            }
            preg_match_all('/[\p{L}\p{N}]+(?:[\x{2019}\x{27}-][\p{L}\p{N}]+)*/u', trim($value), $matches);
            if (count($matches[0]) > $maximum) {
                $label = $attribute === 'nombre' ? 'El nombre' : 'La descripción';
                $fail("{$label} admite un máximo de {$maximum} palabras.");
            }
        };
    }
}
