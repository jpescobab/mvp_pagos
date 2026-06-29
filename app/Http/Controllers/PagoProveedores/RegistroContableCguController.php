<?php

namespace App\Http\Controllers\PagoProveedores;

use App\Http\Controllers\Controller;
use App\Http\Requests\PagoProveedores\RegistrarRegistroContableCguRequest;
use App\Models\CasoPagoProveedor;
use App\Models\RegistroContableCgu;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class RegistroContableCguController extends Controller
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function store(CasoPagoProveedor $caso, RegistrarRegistroContableCguRequest $request): RedirectResponse
    {
        Gate::authorize('registrarCgu', $caso);

        DB::transaction(function () use ($caso, $request): void {
            $registro = RegistroContableCgu::create([
                'caso_pago_proveedor_id' => $caso->id,
                'numero_registro' => $request->string('numero_registro')->toString(),
                'fecha_registro' => $request->date('fecha_registro'),
                'monto' => $request->float('monto'),
                'observaciones' => $request->string('observaciones')->toString() ?: null,
                'registrado_por' => $request->user()->id,
            ]);

            $this->auditLogger->log(
                action: 'caso_pago_proveedor.registrar_contable_cgu',
                auditable: $caso,
                after: $registro->only(['numero_registro', 'fecha_registro', 'monto']),
            );
        });

        return back();
    }
}
