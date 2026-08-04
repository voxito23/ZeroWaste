<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class FastApiQrService
{
    /** @return list<string> */
    private function keys(): array
    {
        $configured = implode(',', array_filter([
            (string) config('services.fastapi.system_api_key'),
            (string) getenv('SYSTEM_API_KEY'),
        ]));
        $keys = array_values(array_filter(array_map(
            static fn (string $key): string => trim($key),
            explode(',', $configured)
        )));
        if ($keys === []) {
            throw new RuntimeException('SYSTEM_API_KEY no está configurada para la comunicación interna.');
        }

        return array_values(array_unique($keys));
    }

    private function client(string $key): PendingRequest
    {
        $runtimeUrl = trim((string) getenv('FASTAPI_INTERNAL_URL'));
        $baseUrl = $runtimeUrl !== '' ? $runtimeUrl : (string) config('services.fastapi.url');

        return Http::baseUrl(rtrim($baseUrl, '/'))
            ->acceptJson()
            ->withHeaders(['X-API-Key' => $key])
            ->timeout(15)
            ->retry(2, 200, throw: false);
    }

    public function point(int $pointId): array
    {
        return $this->json($this->request('get', "/qr/puntos/{$pointId}"));
    }

    public function generatePoint(int $pointId): array
    {
        return $this->json($this->request('post', "/qr/puntos/{$pointId}/generar"));
    }

    public function regeneratePoint(int $pointId): array
    {
        return $this->json($this->request('post', "/qr/puntos/{$pointId}/regenerar"));
    }

    public function revokePoint(int $pointId): array
    {
        return $this->json($this->request('post', "/qr/puntos/{$pointId}/revocar"));
    }

    public function history(int $pointId): array
    {
        return $this->json($this->request('get', "/qr/puntos/{$pointId}/historial"));
    }

    public function render(string $content, string $format): array
    {
        $response = $this->request('post', '/qr/render?format='.$format, ['contenido' => $content]);
        if (! $response->successful()) {
            throw new RuntimeException($response->json('detail') ?: 'No fue posible renderizar el código QR.');
        }

        return ['body' => $response->body(), 'content_type' => $response->header('Content-Type')];
    }

    public function userMessage(Throwable $error, string $operation = 'procesar'): string
    {
        $message = $error->getMessage();
        $status = (int) $error->getCode();

        if (str_contains($message, 'SYSTEM_API_KEY') || in_array($status, [401, 403], true)) {
            return 'La conexión segura entre Laravel y FastAPI no está configurada correctamente. Revisa SYSTEM_API_KEY en ambos servicios.';
        }
        if ($status === 404 || str_contains($message, 'Punto no encontrado')) {
            return 'FastAPI no encontró este punto. Verifica que Laravel y FastAPI estén conectados a la misma base de datos.';
        }
        if ($error instanceof \Illuminate\Http\Client\ConnectionException) {
            return 'FastAPI no está disponible en este momento. Confirma FASTAPI_INTERNAL_URL y el estado del contenedor fast_api.';
        }
        if ($status >= 500) {
            return 'FastAPI recibió la solicitud, pero su servicio de QR no está listo. Verifica QR_TOKEN_ENCRYPTION_KEY y que la migración segura de QR esté aplicada.';
        }

        return "No fue posible {$operation} el código QR. Revisa la integración interna e inténtalo nuevamente.";
    }

    private function json($response): array
    {
        if (! $response->successful()) {
            $detail = $response->json('detail');
            if (is_array($detail)) {
                $detail = $detail['detail'] ?? null;
            }
            throw new RuntimeException($detail ?: 'No fue posible completar la operación de QR.', $response->status());
        }

        return $response->json();
    }

    private function request(string $method, string $path, array $payload = [])
    {
        $response = null;
        foreach ($this->keys() as $key) {
            $client = $this->client($key);
            $response = $method === 'get'
                ? $client->get($path)
                : $client->post($path, $payload);
            if (! in_array($response->status(), [401, 403], true)) {
                return $response;
            }
        }

        return $response;
    }
}
