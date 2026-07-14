<?php

use App\Models\ConjuntoRequisitosDocumentales;
use App\Models\TipoDocumento;
use App\Models\TipoProcesoPago;
use Database\Seeders\RequisitosDocumentalesPagoProveedoresSeeder;
use Database\Seeders\TiposDocumentoSeeder;
use Database\Seeders\TiposProcesoPagoSeeder;
use Database\Seeders\WorkflowPagoProveedoresSeeder;

beforeEach(function () {
    $this->seed(WorkflowPagoProveedoresSeeder::class);
    $this->seed(TiposDocumentoSeeder::class);
    $this->seed(TiposProcesoPagoSeeder::class);
    $this->seed(RequisitosDocumentalesPagoProveedoresSeeder::class);

    $this->conjunto = ConjuntoRequisitosDocumentales::where('codigo', 'pago_proveedores')->firstOrFail();
});

function requisitoActivo(ConjuntoRequisitosDocumentales $conjunto, string $codigoDocumento, ?string $codigoTipoProceso = null): ?string
{
    $tipoDocumento = TipoDocumento::where('codigo', $codigoDocumento)->firstOrFail();
    $tipoProcesoPagoId = $codigoTipoProceso === null
        ? null
        : TipoProcesoPago::where('codigo', $codigoTipoProceso)->firstOrFail()->id;

    return $conjunto->requisitos()
        ->where('tipo_documento_id', $tipoDocumento->id)
        ->where('tipo_proceso_pago_id', $tipoProcesoPagoId)
        ->where('activo', true)
        ->value('tipo_requisito');
}

test('FACTURA y COMPROBANTE quedan universales y obligatorios', function () {
    expect(requisitoActivo($this->conjunto, 'FACTURA'))->toBe('obligatorio');
    expect(requisitoActivo($this->conjunto, 'COMPROBANTE'))->toBe('obligatorio');
});

test('las filas universales previas de ACTA_RECEP, CERT_VIGENCIA, RESOLUCION, ORDEN_COMPRA y CONTRATO quedan desactivadas', function () {
    foreach (['ACTA_RECEP', 'CERT_VIGENCIA', 'RESOLUCION', 'ORDEN_COMPRA', 'CONTRATO'] as $codigo) {
        expect(requisitoActivo($this->conjunto, $codigo))->toBeNull();
    }
});

test('COMPRA exige Orden de Compra y Acta de Recepción obligatorias, Certificado y Resolución opcionales', function () {
    expect(requisitoActivo($this->conjunto, 'ORDEN_COMPRA', 'COMPRA'))->toBe('obligatorio');
    expect(requisitoActivo($this->conjunto, 'ACTA_RECEP', 'COMPRA'))->toBe('obligatorio');
    expect(requisitoActivo($this->conjunto, 'CERT_VIGENCIA', 'COMPRA'))->toBe('opcional');
    expect(requisitoActivo($this->conjunto, 'RESOLUCION', 'COMPRA'))->toBe('opcional');
    expect(requisitoActivo($this->conjunto, 'CONTRATO', 'COMPRA'))->toBeNull();
});

test('CONTRATO exige Contrato, Acta de Recepción y Certificado de Vigencia obligatorios', function () {
    expect(requisitoActivo($this->conjunto, 'CONTRATO', 'CONTRATO'))->toBe('obligatorio');
    expect(requisitoActivo($this->conjunto, 'ACTA_RECEP', 'CONTRATO'))->toBe('obligatorio');
    expect(requisitoActivo($this->conjunto, 'CERT_VIGENCIA', 'CONTRATO'))->toBe('obligatorio');
    expect(requisitoActivo($this->conjunto, 'RESOLUCION', 'CONTRATO'))->toBe('opcional');
    expect(requisitoActivo($this->conjunto, 'ORDEN_COMPRA', 'CONTRATO'))->toBeNull();
});

test('ANTICIPO solo exige Resolución de Pago además de los universales, y FACTURA sigue siendo obligatoria', function () {
    expect(requisitoActivo($this->conjunto, 'RESOLUCION', 'ANTICIPO'))->toBe('obligatorio');
    expect(requisitoActivo($this->conjunto, 'FACTURA'))->toBe('obligatorio');
    expect(requisitoActivo($this->conjunto, 'ACTA_RECEP', 'ANTICIPO'))->toBeNull();
    expect(requisitoActivo($this->conjunto, 'CERT_VIGENCIA', 'ANTICIPO'))->toBeNull();
});

test('OTRO mantiene todos los documentos condicionales como obligatorios (fallback conservador)', function () {
    expect(requisitoActivo($this->conjunto, 'ACTA_RECEP', 'OTRO'))->toBe('obligatorio');
    expect(requisitoActivo($this->conjunto, 'CERT_VIGENCIA', 'OTRO'))->toBe('obligatorio');
    expect(requisitoActivo($this->conjunto, 'RESOLUCION', 'OTRO'))->toBe('obligatorio');
    expect(requisitoActivo($this->conjunto, 'ORDEN_COMPRA', 'OTRO'))->toBe('opcional');
    expect(requisitoActivo($this->conjunto, 'CONTRATO', 'OTRO'))->toBe('opcional');
});
