<?php

namespace App\Http\Controllers\Seguridad;

use App\Http\Controllers\Controller;
use App\Http\Resources\Seguridad\AuditLogResource;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class AuditoriaController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('viewAny', AuditLog::class);

        $registros = AuditLog::with('user')
            ->orderByDesc('id')
            ->paginate(20);

        return Inertia::render('auditoria/index', [
            'registros' => AuditLogResource::collection($registros),
        ]);
    }
}
