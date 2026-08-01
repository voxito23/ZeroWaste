<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Services\FastApiQrService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Http\Request;
use App\Services\AuditLogger;

class PointQrController extends Controller
{
    public function __construct(private readonly FastApiQrService $qr) {}

    public function show(Location $location): View|RedirectResponse
    {
        try {
            $qr = $this->qr->point($location->id);
            $history = $this->qr->history($location->id);
            return view('admin.mapa.qr', compact('location', 'qr', 'history'));
        } catch (\Throwable $error) {
            return redirect()->route('mapa.index')->with('error', 'No fue posible consultar el código QR.');
        }
    }

    public function generate(Request $request, Location $location): RedirectResponse
    {
        try {
            $this->qr->generatePoint($location->id);
            AuditLogger::record($request, 'point_qr.generated', 'location', $location->id);
            return redirect()->route('mapa.qr.show', $location)->with('success', 'El código QR fue generado correctamente.');
        } catch (\Throwable $error) {
            return back()->with('error', 'No fue posible generar el código QR.');
        }
    }

    public function regenerate(Request $request, Location $location): RedirectResponse
    {
        try {
            $this->qr->regeneratePoint($location->id);
            AuditLogger::record($request, 'point_qr.regenerated', 'location', $location->id);
            return redirect()->route('mapa.qr.show', $location)->with('success', 'El código QR fue regenerado correctamente.');
        } catch (\Throwable $error) {
            return back()->with('error', 'No fue posible regenerar el código QR.');
        }
    }

    public function revoke(Request $request, Location $location): RedirectResponse
    {
        try {
            $this->qr->revokePoint($location->id);
            AuditLogger::record($request, 'point_qr.revoked', 'location', $location->id);
            return redirect()->route('mapa.index')->with('success', 'El código QR fue revocado correctamente.');
        } catch (\Throwable $error) {
            return back()->with('error', 'No fue posible revocar el código QR.');
        }
    }

    public function download(Location $location, string $format)
    {
        abort_unless(in_array($format, ['png', 'svg'], true), 404);
        try {
            $qr = $this->qr->point($location->id);
            $rendered = $this->qr->render($qr['content'], $format);
            return response($rendered['body'], 200, [
                'Content-Type' => $rendered['content_type'],
                'Content-Disposition' => "attachment; filename=zerowaste-punto-{$location->id}.{$format}",
                'X-Content-Type-Options' => 'nosniff',
            ]);
        } catch (\Throwable $error) {
            return back()->with('error', 'No fue posible descargar el código QR.');
        }
    }
}
