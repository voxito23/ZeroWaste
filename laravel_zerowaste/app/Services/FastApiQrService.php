<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class FastApiQrService
{
    private function client(): PendingRequest
    {
        $key = (string) config('services.fastapi.system_api_key');
        if ($key === '') {
            throw new RuntimeException('SYSTEM_API_KEY no está configurada para la comunicación interna.');
        }

        return Http::baseUrl(rtrim((string) config('services.fastapi.url'), '/'))
            ->acceptJson()
            ->withHeaders(['X-API-Key' => $key])
            ->timeout(15)
            ->retry(2, 200, throw: false);
    }

    public function point(int $pointId): array
    {
        return $this->json($this->client()->get("/qr/puntos/{$pointId}"));
    }

    public function generatePoint(int $pointId): array
    {
        return $this->json($this->client()->post("/qr/puntos/{$pointId}/generar"));
    }

    public function regeneratePoint(int $pointId): array
    {
        return $this->json($this->client()->post("/qr/puntos/{$pointId}/regenerar"));
    }

    public function revokePoint(int $pointId): array
    {
        return $this->json($this->client()->post("/qr/puntos/{$pointId}/revocar"));
    }

    public function history(int $pointId): array
    {
        return $this->json($this->client()->get("/qr/puntos/{$pointId}/historial"));
    }

    public function render(string $content, string $format): array
    {
        $response = $this->client()->post('/qr/render?format='.$format, ['contenido' => $content]);
        if (! $response->successful()) {
            throw new RuntimeException($response->json('detail') ?: 'No fue posible renderizar el código QR.');
        }

        return ['body' => $response->body(), 'content_type' => $response->header('Content-Type')];
    }

    private function json($response): array
    {
        if (! $response->successful()) {
            $detail = $response->json('detail');
            if (is_array($detail)) {
                $detail = $detail['detail'] ?? null;
            }
            throw new RuntimeException($detail ?: 'No fue posible completar la operación de QR.');
        }

        return $response->json();
    }
}
