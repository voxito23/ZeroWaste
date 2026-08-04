<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

final class Media
{
    private const CATEGORIES = [
        'foro', 'perfiles', 'recompensas', 'campanas', 'eventos', 'puntos',
    ];

    private const MIME_EXTENSIONS = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    private const LEGACY_PREFIXES = [
        'images/recompensas/' => 'recompensas',
        'img/perfiles/' => 'perfiles',
        'static/img/posts/' => 'foro',
        'static/img/perfiles/' => 'perfiles',
        'static/img/campanas/' => 'campanas',
        'static/img/puntos/' => 'puntos',
        'img/eventos/' => 'eventos',
        'img/mapa/' => 'puntos',
        'api/foro/posts/imagenes/' => 'foro',
        'api/foro/perfiles/' => 'perfiles',
    ];

    public static function url(mixed $path, ?string $category = null): ?string
    {
        if (! is_scalar($path)) {
            return null;
        }
        $value = trim((string) $path);
        if ($value === '' || preg_match('/[\x00-\x1F\x7F]/', $value)) {
            return null;
        }

        $absoluteFallback = null;
        $scheme = parse_url($value, PHP_URL_SCHEME);
        if ($scheme !== null) {
            $parts = parse_url($value);
            if (! is_array($parts)) {
                return null;
            }
            $host = strtolower(rtrim((string) ($parts['host'] ?? ''), '.'));
            $ownPublicUrl = in_array($host, ['zerowaste-qro.com', 'www.zerowaste-qro.com'], true);
            if (! $ownPublicUrl) {
                return self::safeAbsoluteUrl($value);
            }
            if (! in_array(strtolower($scheme), ['http', 'https'], true) || isset($parts['user']) || isset($parts['pass'])) {
                return null;
            }
            $absoluteFallback = strtolower($scheme) === 'https' ? self::safeAbsoluteUrl($value) : null;
            $value = (string) ($parts['path'] ?? '');
        }
        if (str_starts_with($value, '//') || str_starts_with($value, '\\\\') || preg_match('/^[A-Za-z]:[\\\\\/]/', $value)) {
            return null;
        }

        $decoded = ltrim(str_replace('\\', '/', rawurldecode($value)), '/');
        if (preg_match('#^(data|app|var|opt|home|tmp)/#i', $decoded)) {
            return null;
        }
        $parts = explode('/', $decoded);
        if (in_array('', $parts, true) || in_array('.', $parts, true) || in_array('..', $parts, true)) {
            return null;
        }

        $selected = self::category($category);
        $relative = null;
        if (str_starts_with($decoded, 'media/')) {
            if (count($parts) < 3) {
                return null;
            }
            $selected = self::category($parts[1]);
            $relative = implode('/', array_slice($parts, 2));
        } else {
            foreach (self::LEGACY_PREFIXES as $prefix => $legacyCategory) {
                if (str_starts_with($decoded, $prefix)) {
                    $selected = $legacyCategory;
                    $relative = substr($decoded, strlen($prefix));
                    break;
                }
            }
        }
        if ($relative === null && str_starts_with($decoded, 'static/img/eventos/')) {
            $relative = substr($decoded, strlen('static/img/eventos/'));
            $selected ??= 'eventos';
        } elseif ($relative === null && $selected !== null) {
            $relative = basename($decoded);
        }
        if ($selected === null || $relative === null || $relative === '') {
            return $absoluteFallback;
        }
        $relativeParts = explode('/', rawurldecode($relative));
        if (in_array('', $relativeParts, true) || in_array('.', $relativeParts, true) || in_array('..', $relativeParts, true)) {
            return null;
        }
        $encoded = implode('/', array_map('rawurlencode', $relativeParts));

        return rtrim(self::publicBaseUrl(), '/').'/'.$selected.'/'.$encoded;
    }

    public static function store(UploadedFile $file, string $category, int $maximumBytes = 5 * 1024 * 1024): string
    {
        $normalized = self::category($category);
        if ($normalized === null) {
            throw new InvalidArgumentException('Categoría de medios no permitida.');
        }
        if ($maximumBytes < 1 || $maximumBytes > 15 * 1024 * 1024) {
            throw new InvalidArgumentException('El límite de imagen solicitado no es válido.');
        }
        if (! $file->isValid() || $file->getSize() === false || $file->getSize() > $maximumBytes) {
            throw new RuntimeException('La imagen no es válida o supera el límite permitido.');
        }
        $mime = $file->getMimeType();
        $extension = is_string($mime) ? (self::MIME_EXTENSIONS[$mime] ?? null) : null;
        if ($extension === null) {
            throw new RuntimeException('Usa una imagen JPEG, PNG o WebP.');
        }

        $directory = self::directory($normalized);
        File::ensureDirectoryExists($directory, 02770, true);
        if (! is_writable($directory)) {
            throw new RuntimeException('El directorio compartido de perfiles no permite escritura.');
        }
        $filename = Str::uuid()->toString().'.'.$extension;
        $file->move($directory, $filename);
        $path = $directory.DIRECTORY_SEPARATOR.$filename;
        if (! chmod($path, 0660)) {
            File::delete($path);
            throw new RuntimeException('No fue posible aplicar permisos seguros al archivo.');
        }

        return $filename;
    }

    public static function directory(string $category): string
    {
        $normalized = self::category($category);
        if ($normalized === null) {
            throw new InvalidArgumentException('Categoría de medios no permitida.');
        }

        return rtrim((string) config('media.root', '/data/media'), DIRECTORY_SEPARATOR)
            .DIRECTORY_SEPARATOR.$normalized;
    }

    public static function discard(?string $filename, string $category): void
    {
        if ($filename === null || preg_match('/^[0-9a-f-]{36}\.(jpg|png|webp)$/', $filename) !== 1) {
            return;
        }
        $path = self::directory($category).DIRECTORY_SEPARATOR.$filename;
        if (File::isFile($path)) {
            File::delete($path);
        }
    }

    private static function category(?string $category): ?string
    {
        if ($category === null) {
            return null;
        }
        $normalized = strtolower(trim(str_replace('campañas', 'campanas', $category)));

        return in_array($normalized, self::CATEGORIES, true) ? $normalized : null;
    }

    private static function publicBaseUrl(): string
    {
        $configured = trim((string) config('media.public_base_url'));

        return self::safeAbsoluteUrl($configured)
            ?? 'https://www.zerowaste-qro.com/media';
    }

    private static function safeAbsoluteUrl(string $url): ?string
    {
        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            return null;
        }
        $parts = parse_url($url);
        if (($parts['scheme'] ?? '') !== 'https' || isset($parts['user']) || isset($parts['pass'])) {
            return null;
        }
        $host = strtolower((string) ($parts['host'] ?? ''));
        if (! self::isPublicHost($host)) {
            return null;
        }
        $port = isset($parts['port']) ? ':'.(int) $parts['port'] : '';
        $path = implode('/', array_map('rawurlencode', explode('/', rawurldecode($parts['path'] ?? ''))));
        $query = isset($parts['query']) ? '?'.preg_replace('/[^A-Za-z0-9%=&:@._~!$\'()*+,;\/?-]/', '', $parts['query']) : '';
        $fragment = isset($parts['fragment']) ? '#'.rawurlencode(rawurldecode($parts['fragment'])) : '';

        return 'https://'.$host.$port.$path.$query.$fragment;
    }

    private static function isPublicHost(string $host): bool
    {
        if ($host === '' || $host === 'localhost' || str_ends_with($host, '.localhost') || str_ends_with($host, '.local')) {
            return false;
        }
        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            return filter_var(
                $host,
                FILTER_VALIDATE_IP,
                FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
            ) !== false;
        }

        return str_contains($host, '.')
            && preg_match('/^[a-z0-9.-]+$/', $host) === 1;
    }
}
