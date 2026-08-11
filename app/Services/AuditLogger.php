<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Schema;

class AuditLogger
{
    /**
     * Persist a lightweight audit row for sensitive actions.
     * No-ops when the audit_logs table has not been migrated yet.
     *
     * @param  array<string, mixed>  $meta
     */
    public static function log(string $action, ?Model $subject = null, array $meta = []): ?AuditLog
    {
        if (! Schema::hasTable('audit_logs')) {
            return null;
        }

        return AuditLog::create([
            'user_id'        => Auth::id(),
            'action'         => $action,
            'auditable_type' => $subject?->getMorphClass(),
            'auditable_id'   => $subject?->getKey(),
            'ip'             => Request::ip(),
            'user_agent'     => substr((string) Request::userAgent(), 0, 512) ?: null,
            'meta'           => $meta ?: null,
            'created_at'     => now(),
        ]);
    }
}
