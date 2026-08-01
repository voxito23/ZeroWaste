<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AuditLogger
{
    public static function record(Request $request, string $action, string $subjectType, string|int|null $subjectId, array $metadata = []): void
    {
        $safe = collect($metadata)->except(['password', 'token', 'jwt', 'cookie', 'secret'])->all();
        DB::table('audit_logs')->insert([
            'administrator_id' => $request->user()?->id,
            'action' => $action,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId === null ? null : (string) $subjectId,
            'metadata' => $safe ? json_encode($safe, JSON_UNESCAPED_UNICODE) : null,
            'ip_hash' => $request->ip() ? hash('sha256', $request->ip()) : null,
            'created_at' => now(),
        ]);
    }
}
