<?php

namespace App\Services\Presupuesto;

use App\Models\Catalogo;
use App\Models\Cfinanciero;
use App\Models\Documento;
use App\Models\Presupuesto\ImportacionPresupuesto;
use App\Models\Presupuesto\PlanTarea;
use App\Models\Presupuesto\Presupuesto;
use App\Models\SistemaExterno;
use App\Models\SnapshotDatosExternoDocumento;
use App\Models\TipoDocumento;
use App\Models\User;
use App\Services\Integraciones\IntegracionExternaService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ImportadorPresupuestoCguService
{
    public function __construct(
        private readonly LectorExcelPresupuestoCgu $lector,
        private readonly IntegracionExternaService $integracionExterna,
    ) {}

    public function importar(
        string $rutaArchivo,
        int $anio,
        ?User $usuario = null,
        ?UploadedFile $archivoOriginal = null,
    ): ImportacionPresupuesto {
        $usuario ??= Auth::user();
        $sistema = SistemaExterno::where('codigo', 'CGU')->firstOrFail();

        return DB::transaction(function () use ($rutaArchivo, $anio, $usuario, $sistema, $archivoOriginal) {
            $trabajo = $this->integracionExterna->iniciarTrabajo($sistema, 'importar_presupuesto', 'manual', $usuario);

            try {
                $filas = $this->lector->leer($rutaArchivo);
            } catch (RuntimeException $e) {
                $this->integracionExterna->finalizarTrabajo($trabajo, 'error', $e->getMessage());

                throw $e;
            }

            $importacion = ImportacionPresupuesto::create([
                'nro_version' => $filas[0]['nro_version'] ?? '',
                'anio' => $anio,
                'estado' => 'running',
                'iniciado_en' => now(),
                'creado_por_user_id' => $usuario?->id,
            ]);

            $snapshot = $this->integracionExterna->registrarSnapshot(
                $sistema,
                'excel',
                ['archivo' => basename($rutaArchivo), 'filas' => $filas],
                trabajo: $trabajo,
                vinculable: $importacion,
                usuario: $usuario,
            );

            $importacion->update(['snapshot_datos_externo_id' => $snapshot->id]);

            if ($archivoOriginal !== null) {
                $this->registrarDocumentoOriginal($archivoOriginal, $snapshot->id, $usuario);
            }

            $totales = [
                'total_recibidos' => count($filas),
                'total_creados' => 0,
                'total_actualizados' => 0,
                'total_omitidos' => 0,
                'total_fallidos' => 0,
            ];
            $advertencias = [];

            foreach ($filas as $fila) {
                $cfinanciero = Cfinanciero::where('codigo', $fila['cfinanciero_codigo'])->first();
                $catalogo = Catalogo::where('codigo', $fila['catalogo_codigo'])->first();

                if ($cfinanciero === null || $catalogo === null) {
                    $totales['total_omitidos']++;
                    $advertencias[] = "Fila omitida: cuenta \"{$fila['catalogo_codigo']}\" o unidad ejecutora \"{$fila['cfinanciero_codigo']}\" no reconocida.";

                    continue;
                }

                $planTarea = PlanTarea::firstOrCreate(
                    ['codigo' => $fila['plan_tarea_codigo']],
                    ['nombre' => $fila['plan_tarea_codigo']],
                );

                $identidad = [
                    'cfinanciero_id' => $cfinanciero->id,
                    'catalogo_id' => $catalogo->id,
                    'plan_tarea_id' => $planTarea->id,
                    'anio' => $anio,
                ];

                $yaExistia = Presupuesto::where($identidad)->exists();

                Presupuesto::updateOrCreate($identidad, [
                    'monto_asignado' => $fila['monto_asignado'],
                    'importacion_presupuesto_id' => $importacion->id,
                ]);

                $yaExistia ? $totales['total_actualizados']++ : $totales['total_creados']++;
            }

            $this->integracionExterna->finalizarTrabajo($trabajo, 'completado');

            $importacion->marcarComoFinalizada('completado', [
                ...$totales,
                'advertencias' => $advertencias === [] ? null : $advertencias,
            ]);

            return $importacion->refresh();
        });
    }

    private function registrarDocumentoOriginal(UploadedFile $archivo, int $snapshotDatosExternoId, ?User $usuario): void
    {
        $tipoDocumento = TipoDocumento::where('codigo', 'PRESUPUESTO_CGU')->firstOrFail();

        $documento = Documento::create([
            'tipo_documento_id' => $tipoDocumento->id,
            'titulo' => $archivo->getClientOriginalName(),
            'subido_por' => $usuario?->id,
        ]);

        $rutaAlmacenada = $archivo->store('documentos', 'local');

        $documento->versiones()->create([
            'numero_version' => 1,
            'ruta_archivo' => $rutaAlmacenada,
            'nombre_archivo' => $archivo->getClientOriginalName(),
            'tipo_mime' => $archivo->getClientMimeType(),
            'tamano_bytes' => $archivo->getSize(),
            'hash' => hash_file('sha256', $archivo->getRealPath()),
            'subido_por' => $usuario?->id,
        ]);

        SnapshotDatosExternoDocumento::create([
            'snapshot_datos_externo_id' => $snapshotDatosExternoId,
            'documento_id' => $documento->id,
        ]);
    }
}
