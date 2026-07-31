<?php

namespace App\Services\Presupuesto;

use App\Models\Documento;
use App\Models\Presupuesto\CertificadoDisponibilidadPresupuestaria;
use App\Models\Presupuesto\MovimientoPresupuestario;
use App\Models\Presupuesto\Presupuesto;
use App\Models\Proceso;
use App\Models\TipoDocumento;
use App\Models\VersionDocumento;
use App\Models\VinculoDocumento;
use App\Services\Workflow\TransicionWorkflowService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class FirmarCertificadoDisponibilidadService
{
    public function __construct(
        private readonly CalculadorSaldoPresupuestoService $calculadorSaldo,
        private readonly TransicionWorkflowService $transicionWorkflowService,
    ) {}

    public function firmar(CertificadoDisponibilidadPresupuestaria $cdp, ?string $comentario = null): CertificadoDisponibilidadPresupuestaria
    {
        return DB::transaction(function () use ($cdp, $comentario) {
            $presupuesto = Presupuesto::where('id', $cdp->presupuesto_id)->lockForUpdate()->firstOrFail();

            $disponible = $this->calculadorSaldo->disponible($presupuesto);
            $huboSobregiro = (float) $cdp->monto > $disponible;

            $this->transicionWorkflowService->execute($cdp->proceso, 'firmar', $comentario);

            $cdp->update([
                'firmado_por' => Auth::id(),
                'firmado_en' => now(),
                'saldo_disponible_al_emitir' => $disponible,
                'hubo_sobregiro_al_emitir' => $huboSobregiro,
            ]);

            MovimientoPresupuestario::create([
                'presupuesto_id' => $presupuesto->id,
                'tipo' => 'compromiso',
                'monto' => $cdp->monto,
                'origen_type' => CertificadoDisponibilidadPresupuestaria::class,
                'origen_id' => $cdp->id,
                'user_id' => Auth::id(),
            ]);

            $this->generarYVincularDocumento($cdp->refresh());

            return $cdp->refresh();
        });
    }

    private function generarYVincularDocumento(CertificadoDisponibilidadPresupuestaria $cdp): void
    {
        $cdp->load('cfinanciero', 'presupuesto.catalogo', 'firmadoPor', 'proceso');

        $pdf = Pdf::loadView('presupuesto.cdp', ['cdp' => $cdp]);
        $binario = $pdf->output();

        $nombreArchivo = str_replace(' ', '-', $cdp->folio).'.pdf';
        $ruta = "documentos/cdp/{$cdp->id}/{$nombreArchivo}";
        Storage::disk('local')->put($ruta, $binario);

        $tipoDocumentoId = TipoDocumento::where('codigo', 'CDP')->value('id');

        $documento = Documento::create([
            'tipo_documento_id' => $tipoDocumentoId,
            'titulo' => $cdp->folio,
            'subido_por' => Auth::id(),
        ]);

        VersionDocumento::create([
            'documento_id' => $documento->id,
            'numero_version' => 1,
            'ruta_archivo' => $ruta,
            'nombre_archivo' => $nombreArchivo,
            'tipo_mime' => 'application/pdf',
            'tamano_bytes' => strlen($binario),
            'subido_por' => Auth::id(),
        ]);

        VinculoDocumento::create([
            'documento_id' => $documento->id,
            'vinculable_type' => Proceso::class,
            'vinculable_id' => $cdp->proceso->id,
            'activo' => true,
        ]);

        if ($cdp->proceso_adquisicion_id !== null) {
            $procesoAdquisicion = $cdp->procesoAdquisicion()->with('proceso')->first();

            if ($procesoAdquisicion?->proceso !== null) {
                VinculoDocumento::create([
                    'documento_id' => $documento->id,
                    'vinculable_type' => Proceso::class,
                    'vinculable_id' => $procesoAdquisicion->proceso->id,
                    'activo' => true,
                ]);
            }
        }
    }
}
