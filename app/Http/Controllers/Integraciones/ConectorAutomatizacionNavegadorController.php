<?php

namespace App\Http\Controllers\Integraciones;

use App\Http\Controllers\Controller;
use App\Http\Requests\Integraciones\CrearConectorAutomatizacionNavegadorRequest;
use App\Http\Resources\Integraciones\ConectorAutomatizacionNavegadorResource;
use App\Models\ConectorAutomatizacionNavegador;
use App\Models\SistemaExterno;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ConectorAutomatizacionNavegadorController extends Controller
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function index(): Response
    {
        $conectores = ConectorAutomatizacionNavegador::with(['sistemaExterno', 'autorizadoPor', 'perfilesAutenticacionNavegador'])
            ->orderBy('codigo')
            ->get();

        return Inertia::render('integraciones/conectores/index', [
            'conectores' => ConectorAutomatizacionNavegadorResource::collection($conectores),
            'sistemasExternos' => SistemaExterno::orderBy('codigo')->get(['id', 'codigo', 'nombre']),
        ]);
    }

    public function store(CrearConectorAutomatizacionNavegadorRequest $request): RedirectResponse
    {
        Gate::authorize('create', ConectorAutomatizacionNavegador::class);

        DB::transaction(function () use ($request): void {
            $conector = ConectorAutomatizacionNavegador::create($request->validated());

            $this->auditLogger->log(
                action: 'integraciones.crear_conector',
                auditable: $conector,
                after: $conector->only(['sistema_externo_id', 'codigo', 'nombre']),
            );
        });

        return back();
    }

    public function autorizar(ConectorAutomatizacionNavegador $conector): RedirectResponse
    {
        Gate::authorize('gestionar', $conector);

        DB::transaction(function () use ($conector): void {
            $conector->update([
                'activo' => true,
                'autorizado_por' => request()->user()->id,
                'autorizado_en' => now(),
            ]);

            $this->auditLogger->log(
                action: 'integraciones.autorizar_conector',
                auditable: $conector,
                after: $conector->only(['activo', 'autorizado_por', 'autorizado_en']),
            );
        });

        return back();
    }
}
