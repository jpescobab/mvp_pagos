<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\SecurityAuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditLogger
{
    /**
     * Record a generic auditable action, reusable by any domain.
     *
     * Accepts an explicit $user because callers like Gate::after evaluate
     * authorization for an arbitrary user (Gate::forUser()) without
     * necessarily authenticating them in the current request/session.
     *
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     * @param  array<string, mixed>  $metadata
     */
    public function log(string $action, ?Model $auditable = null, array $before = [], array $after = [], array $metadata = [], ?User $user = null): AuditLog
    {
        return AuditLog::create([
            'user_id' => ($user ?? Auth::user())?->id,
            'action' => $action,
            'auditable_type' => $auditable?->getMorphClass(),
            'auditable_id' => $auditable?->getKey(),
            'before' => $before,
            'after' => $after,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Record a security-relevant event (e.g. an authorization denial).
     *
     * @param  array<string, mixed>  $metadata
     */
    public function logSecurityEvent(string $event, ?string $description = null, array $metadata = [], ?User $user = null): SecurityAuditLog
    {
        return SecurityAuditLog::create([
            'user_id' => ($user ?? Auth::user())?->id,
            'event' => $event,
            'description' => $description,
            'ip_address' => Request::ip(),
            'user_agent' => Request::header('User-Agent'),
            'metadata' => $metadata,
        ]);
    }
}
