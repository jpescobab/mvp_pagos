<?php

namespace App\Http\Controllers\PagoProveedores;

use App\Http\Controllers\Controller;
use App\Http\Requests\PagoProveedores\RegistrarRegistroPagoBancarioRequest;
use App\Models\CasoPagoProveedor;
use App\Models\RegistroPagoBancario;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class RegistroPagoBancarioController extends Controller
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function store(CasoPagoProveedor $caso, RegistrarRegistroPagoBancarioRequest $request): RedirectResponse
    {
        Gate::authorize('registrarPagoBancario', $caso);

        DB::transaction(function () use ($caso, $request): void {
            $registro = RegistroPagoBancario::create([
                'caso_pago_proveedor_id' => $caso->id,
                'numero_operacion' => $request->string('numero_operacion')->toString(),
                'fecha_pago' => $request->date('fecha_pago'),
                'monto' => $request->float('monto'),
                'banco' => $request->string('banco')->toString() ?: null,
                'registrado_por' => $request->user()->id,
            ]);

            $this->auditLogger->log(
                action: 'caso_pago_proveedor.registrar_pago_bancario',
                auditable: $caso,
                after: $registro->only(['numero_operacion', 'fecha_pago', 'monto']),
            );
        });

        return back();
    }
}
