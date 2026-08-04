<?php

namespace App\Support;

final class ForumContent
{
    public static function plainText(?string $content): string
    {
        $decoded = str_replace("\r", '', (string) $content);
        for ($attempt = 0; $attempt < 3; $attempt++) {
            $next = html_entity_decode($decoded, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            if ($next === $decoded) {
                break;
            }
            $decoded = $next;
        }

        $decoded = preg_replace('/<\s*br\s*\/?\s*>/iu', "\n", $decoded) ?? $decoded;
        $decoded = preg_replace('/<\s*li\b[^>]*>\s*(?:<\s*p\b[^>]*>)?/iu', "\n• ", $decoded) ?? $decoded;
        $decoded = preg_replace('/<\s*(?:p|div|h[1-6]|ul|ol|blockquote|section|article)\b[^>]*>/iu', "\n", $decoded) ?? $decoded;
        $decoded = preg_replace('/<\s*\/\s*(?:p|div|h[1-6]|li|ul|ol|blockquote|section|article)\s*>/iu', "\n", $decoded) ?? $decoded;
        $decoded = strip_tags($decoded);
        $decoded = html_entity_decode($decoded, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $decoded = str_replace("\u{00A0}", ' ', $decoded);
        $decoded = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $decoded) ?? $decoded;
        $decoded = preg_replace('/[ \t]+\n/u', "\n", $decoded) ?? $decoded;
        $decoded = preg_replace('/\n[ \t]+/u', "\n", $decoded) ?? $decoded;
        $decoded = preg_replace('/[ \t]{2,}/u', ' ', $decoded) ?? $decoded;
        $decoded = preg_replace('/\n{3,}/u', "\n\n", $decoded) ?? $decoded;

        return trim($decoded);
    }
}
