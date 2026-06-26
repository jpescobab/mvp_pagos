<?php

namespace App\Services\Sgf;

use App\Models\Documento;
use App\Models\ImportacionSgf;
use App\Models\SnapshotSgf;
use App\Models\TipoDocumento;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ImportadorSgf
{
    public function __construct(private readonly NormalizadorSgf $normalizadorSgf) {}

    public function iniciarImportacion(string $fuente, ?User $user = null): ImportacionSgf
    {
        return ImportacionSgf::create([
            'fuente' => $fuente,
            'iniciado_por' => $user?->id,
            'iniciado_en' => now(),
            'estado' => 'en_progreso',
        ]);
    }

    public function finalizarImportacion(ImportacionSgf $importacion): ImportacionSgf
    {
        $importacion->update([
            'finalizado_en' => now(),
            'estado' => 'completado',
        ]);

        return $importacion->refresh();
    }

    /**
     * @param  array<string, mixed>  $filaSgf
     */
    public function importarFila(ImportacionSgf $importacion, array $filaSgf): SnapshotSgf
    {
        return DB::transaction(function () use ($importacion, $filaSgf) {
            $snapshot = SnapshotSgf::create([
                'importacion_sgf_id' => $importacion->id,
                'sgf_id' => (string) $filaSgf['sgf_id'],
                'payload_crudo' => $filaSgf,
                'payload_normalizado' => $this->normalizadorSgf->normalizar($filaSgf),
                'hash' => hash('sha256', json_encode($filaSgf, JSON_THROW_ON_ERROR)),
                'capturado_en' => now(),
            ]);

            foreach ($filaSgf['documentos'] ?? [] as $documentoSgf) {
                $this->vincularDocumento($snapshot, $documentoSgf);
            }

            $importacion->increment('total_filas');

            return $snapshot;
        });
    }

    /**
     * @param  array<string, mixed>  $documentoSgf
     */
    private function vincularDocumento(SnapshotSgf $snapshot, array $documentoSgf): void
    {
        $tipo = TipoDocumento::firstOrCreate(
            ['codigo' => $documentoSgf['tipo_documento_codigo']],
            ['nombre' => $documentoSgf['tipo_documento_codigo']],
        );

        $documento = Documento::create(['tipo_documento_id' => $tipo->id]);

        $documento->versiones()->create([
            'numero_version' => 1,
            'ruta_archivo' => $documentoSgf['ruta_archivo'],
            'nombre_archivo' => $documentoSgf['nombre_archivo'],
        ]);

        $snapshot->documentos()->create(['documento_id' => $documento->id]);
    }
}
