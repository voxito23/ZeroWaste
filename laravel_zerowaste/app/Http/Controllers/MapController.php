<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Location;
use App\Support\Media;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MapController extends Controller
{
    public function index(Request $request)
    {
        $query = Location::withTrashed();
        if ($request->filled('q')) $query->where(fn ($q) => $q->where('nombre', 'ilike', '%'.$request->q.'%')->orWhere('direccion', 'ilike', '%'.$request->q.'%'));
        if ($request->estado === 'activo') $query->whereRaw('activo = TRUE')->whereNull('deleted_at');
        if ($request->estado === 'inactivo') $query->where(fn ($q) => $q->whereRaw('activo = FALSE')->orWhereNotNull('deleted_at'));
        if ($request->filled('material')) $query->where('materiales', 'ilike', '%'.$request->material.'%');
        if ($request->qr === 'con') $query->whereExists(fn ($q) => $q->selectRaw('1')->from('point_qr_codes')->whereColumn('point_qr_codes.location_id', 'locations.id')->whereRaw('point_qr_codes.active = TRUE'));
        if ($request->qr === 'sin') $query->whereNotExists(fn ($q) => $q->selectRaw('1')->from('point_qr_codes')->whereColumn('point_qr_codes.location_id', 'locations.id')->whereRaw('point_qr_codes.active = TRUE'));
        $sort = in_array($request->sort, ['nombre', 'created_at'], true) ? $request->sort : 'created_at';
        $locations = $query->orderBy($sort, $sort === 'created_at' ? 'desc' : 'asc')->paginate(12)->withQueryString();
        $mapLocations = Location::query()->whereRaw('activo = TRUE')->get();
        $mapboxToken = trim((string) config('services.mapbox.public_token', ''), " \t\n\r\0\x0B\"'");
        return view('admin.mapa.index', compact('locations', 'mapLocations', 'mapboxToken'));
    }

    public function create()
    {
        $mapboxToken = trim((string) config('services.mapbox.public_token', ''), " \t\n\r\0\x0B\"'");
        return view('admin.mapa.create', compact('mapboxToken'));
    }

    public function store(Request $request)
    {
        $rules = [
            'nombre' => 'required|string|max:150',
            'direccion' => 'required|string',
            'latitud' => 'required|numeric|between:-90,90',
            'longitud' => 'required|numeric|between:-180,180',
            'tipo' => 'required|string|max:100',
            'imagen_archivo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'materiales' => 'nullable|string|max:255',
            'horario' => 'required|string|max:255',
            'responsable' => 'nullable|string|max:150',
            'activo' => 'nullable|boolean',
        ];
        $messages = [
            'nombre.required' => 'El nombre del punto es obligatorio.',
            'direccion.required' => 'La dirección es obligatoria.',
            'latitud.required' => 'La latitud es obligatoria.',
            'longitud.required' => 'La longitud es obligatoria.',
            'tipo.required' => 'El tipo de punto es obligatorio.',
            'imagen_archivo.image' => 'El archivo debe ser una imagen válida.',
            'imagen_archivo.uploaded' => 'Error al subir la imagen. Verifica que el archivo no exceda el tamaño permitido.',
            'imagen_archivo.max' => 'La imagen no debe superar 5 MB.',
        ];
        $request->validate($rules, $messages);

        $data = $request->only(['nombre', 'direccion', 'latitud', 'longitud', 'tipo', 'horario', 'responsable']);
        $data['activo'] = DB::raw($request->boolean('activo') ? 'TRUE' : 'FALSE');
        $data['materiales'] = $request->input('materiales', 'No especificado (Material General)');

        $newImage = null;
        if ($request->hasFile('imagen_archivo')) {
            $newImage = Media::store($request->file('imagen_archivo'), 'puntos');
            $data['imagen'] = $newImage;
        }

        try {
            $location = Location::query()->create($data);
        } catch (\Throwable $error) {
            Media::discard($newImage, 'puntos');
            throw $error;
        }
        AuditLogger::record($request, 'point.created', 'location', $location->id, ['nombre' => $location->nombre]);
        if ($request->input('submit_action') === 'save_and_qr') {
            try {
                $qrService = app(\App\Services\FastApiQrService::class);
                $qrService->generatePoint($location->id);
                return redirect()->route('mapa.qr.show', $location)->with('success', 'Punto creado correctamente. El código QR fue generado correctamente.');
            } catch (\Throwable $error) {
                Log::warning('El punto fue creado, pero falló la generación del QR.', ['location_id' => $location->id, 'exception' => get_class($error), 'status' => (int) $error->getCode()]);
                $qrService ??= app(\App\Services\FastApiQrService::class);
                return redirect()->route('mapa.index')->with('error', 'El punto fue creado. '.$qrService->userMessage($error, 'generar'));
            }
        }
        return redirect()->route('mapa.index')->with('success', 'Punto creado correctamente.');
    }

    public function edit(Location $location)
    {
        return view('admin.mapa.edit', compact('location'));
    }

    public function update(Request $request, Location $location)
    {
        $rules = [
            'nombre' => 'required|string|max:150',
            'direccion' => 'required|string',
            'latitud' => 'required|numeric|between:-90,90',
            'longitud' => 'required|numeric|between:-180,180',
            'tipo' => 'required|string|max:100',
            'imagen_archivo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'materiales' => 'nullable|string|max:255',
            'horario' => 'required|string|max:255',
            'responsable' => 'nullable|string|max:150',
            'activo' => 'nullable|boolean',
        ];
        $messages = [
            'nombre.required' => 'El nombre del punto es obligatorio.',
            'direccion.required' => 'La dirección es obligatoria.',
            'latitud.required' => 'La latitud es obligatoria.',
            'longitud.required' => 'La longitud es obligatoria.',
            'tipo.required' => 'El tipo de punto es obligatorio.',
            'imagen_archivo.image' => 'El archivo debe ser una imagen válida.',
            'imagen_archivo.uploaded' => 'Error al subir la imagen. Verifica que el archivo no exceda el tamaño permitido.',
            'imagen_archivo.max' => 'La imagen no debe superar 5 MB.',
        ];
        $request->validate($rules, $messages);

        $data = $request->only(['nombre', 'direccion', 'latitud', 'longitud', 'tipo', 'horario', 'responsable']);
        $data['activo'] = DB::raw($request->boolean('activo') ? 'TRUE' : 'FALSE');
        $data['materiales'] = $request->input('materiales', 'No especificado (Material General)');

        $newImage = null;
        if ($request->hasFile('imagen_archivo')) {
            $newImage = Media::store($request->file('imagen_archivo'), 'puntos');
            $data['imagen'] = $newImage;
        }

        try {
            $location->update($data);
        } catch (\Throwable $error) {
            Media::discard($newImage, 'puntos');
            throw $error;
        }
        AuditLogger::record($request, 'point.updated', 'location', $location->id, ['fields' => array_keys($data)]);
        return redirect()->route('mapa.index')->with('success', 'Los cambios fueron guardados.');
    }

    public function destroy(Request $request, Location $location)
    {
        $location->delete();
        AuditLogger::record($request, 'point.deleted', 'location', $location->id);
        return redirect()->route('mapa.index')->with('success', 'El punto fue eliminado.');
    }

    public function deactivate(Request $request, Location $location)
    {
        $location->update(['activo' => DB::raw('FALSE')]);
        AuditLogger::record($request, 'point.deactivated', 'location', $location->id);
        return back()->with('success', 'El punto fue desactivado.');
    }

    public function reactivate(Request $request, int $id)
    {
        $location = Location::withTrashed()->findOrFail($id);
        if ($location->trashed()) $location->restore();
        $location->update(['activo' => DB::raw('TRUE')]);
        AuditLogger::record($request, 'point.reactivated', 'location', $location->id);
        return back()->with('success', 'El punto fue reactivado.');
    }
}
