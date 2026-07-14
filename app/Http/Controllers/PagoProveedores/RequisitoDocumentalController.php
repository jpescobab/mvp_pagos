<?php

namespace App\Http\Controllers\PagoProveedores;

use App\Http\Controllers\Controller;
use App\Http\Requests\PagoProveedores\ActualizarRequisitoDocumentalRequest;
use App\Models\ConjuntoRequisitosDocumentales;
use App\Models\DefinicionWorkflow;
use App\Models\RequisitoDocumental;
use App\Models\TipoDocumento;
use App\Models\TipoProcesoPago;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RequisitoDocumentalController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()?->can('pago_proveedores.administrar_requisitos_documentales'), 403);

        $conjunto = $this->conjuntoPagoProveedores();
        $definicion = DefinicionWorkflow::where('codigo', 'pago_proveedores')->first();

        $requisitos = $definicion === null
            ? collect()
            : RequisitoDocumental::query()
                ->where('conjunto_requisitos_documentales_id', $conjunto->id)
                ->where('definicion_workflow_id', $definicion->id)
                ->where('activo', true)
                ->whereNull('modalidad_id')
                ->whereNull('estado_workflow_id')
                ->get(['id', 'tipo_documento_id', 'tipo_proceso_pago_id', 'tipo_requisito']);

        return Inertia::render('pago-proveedores/requisitos-documentales/index', [
            'tiposDocumento' => TipoDocumento::where('activo', true)->orderBy('nombre')->get(['id', 'codigo', 'nombre']),
            'tiposProcesoPago' => TipoProcesoPago::where('activo', true)->orderBy('nombre')->get(['id', 'codigo', 'nombre']),
            'requisitos' => $requisitos,
        ]);
    }

    public function update(TipoDocumento $tipoDocumento, ActualizarRequisitoDocumentalRequest $request): RedirectResponse
    {
        $conjunto = $this->conjuntoPagoProveedores();
        $definicion = DefinicionWorkflow::where('codigo', 'pago_proveedores')->firstOrFail();

        $tipoProcesoPagoId = $request->validated('tipo_proceso_pago_id');
        $tipoRequisito = $request->validated('tipo_requisito');

        $existente = RequisitoDocumental::query()
            ->where('conjunto_requisitos_documentales_id', $conjunto->id)
            ->where('definicion_workflow_id', $definicion->id)
            ->where('tipo_documento_id', $tipoDocumento->id)
            ->where('tipo_proceso_pago_id', $tipoProcesoPagoId)
            ->whereNull('modalidad_id')
            ->whereNull('estado_workflow_id')
            ->whereNull('monto_desde')
            ->whereNull('monto_hasta')
            ->first();

        if ($tipoRequisito === null) {
            $existente?->delete();
        } elseif ($existente !== null) {
            $existente->update(['tipo_requisito' => $tipoRequisito, 'activo' => true]);
        } else {
            RequisitoDocumental::create([
                'conjunto_requisitos_documentales_id' => $conjunto->id,
                'definicion_workflow_id' => $definicion->id,
                'tipo_documento_id' => $tipoDocumento->id,
                'tipo_proceso_pago_id' => $tipoProcesoPagoId,
                'modalidad_id' => null,
                'estado_workflow_id' => null,
                'monto_desde' => null,
                'monto_hasta' => null,
                'tipo_requisito' => $tipoRequisito,
                'activo' => true,
            ]);
        }

        return back();
    }

    private function conjuntoPagoProveedores(): ConjuntoRequisitosDocumentales
    {
        return ConjuntoRequisitosDocumentales::firstOrCreate(
            ['codigo' => 'pago_proveedores'],
            ['nombre' => 'Requisitos documentales de Pago de Proveedores', 'activo' => true],
        );
    }
}
