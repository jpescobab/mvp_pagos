<?php

namespace App\Services\InformesRazonados;

use App\Exceptions\CorteReportabilidadException;
use App\Models\AprobacionInformeRazonado;
use App\Models\CorteReportabilidad;
use App\Models\DefinicionInformeRazonado;
use App\Models\DefinicionWorkflow;
use App\Models\EjecucionInformeRazonado;
use App\Models\ExcepcionInformeRazonado;
use App\Models\ExportacionInformeRazonado;
use App\Models\GraficoInformeRazonado;
use App\Models\MetricaInformeRazonado;
use App\Models\NarrativaInformeRazonado;
use App\Models\Proceso;
use App\Models\SeccionInformeRazonado;
use App\Models\SnapshotInformeRazonado;
use App\Models\User;
use App\Services\Workflow\TransicionWorkflowService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InformeRazonadoService
{
    public function __construct(private readonly TransicionWorkflowService $transicionWorkflowService) {}

    public function iniciarEjecucion(DefinicionInformeRazonado $definicion, CorteReportabilidad $corte, ?User $usuario = null): EjecucionInformeRazonado
    {
        if (! $corte->estaPublicado()) {
            throw CorteReportabilidadException::corteNoPublicado();
        }

        $usuario ??= Auth::user();

        return DB::transaction(function () use ($definicion, $corte, $usuario) {
            $ejecucion = EjecucionInformeRazonado::create([
                'definicion_informe_razonado_id' => $definicion->id,
                'corte_reportabilidad_id' => $corte->id,
                'generado_por' => $usuario?->id,
                'generado_en' => now(),
            ]);

            $definicionWorkflow = DefinicionWorkflow::where('codigo', 'informes_razonados')->firstOrFail();
            $estadoInicial = $definicionWorkflow->estados()->where('es_inicial', true)->firstOrFail();

            Proceso::create([
                'definicion_workflow_id' => $definicionWorkflow->id,
                'estado_actual_id' => $estadoInicial->id,
                'sujeto_type' => EjecucionInformeRazonado::class,
                'sujeto_id' => $ejecucion->id,
            ]);

            return $ejecucion->refresh();
        });
    }

    public function agregarSeccion(EjecucionInformeRazonado $ejecucion, string $codigo, string $titulo, int $orden = 0): SeccionInformeRazonado
    {
        return $ejecucion->secciones()->create([
            'codigo' => $codigo,
            'titulo' => $titulo,
            'orden' => $orden,
        ]);
    }

    public function agregarMetrica(
        EjecucionInformeRazonado $ejecucion,
        string $codigo,
        string $etiqueta,
        ?float $valor = null,
        ?string $unidad = null,
        ?SeccionInformeRazonado $seccion = null,
        int $orden = 0,
    ): MetricaInformeRazonado {
        return $ejecucion->metricas()->create([
            'seccion_informe_razonado_id' => $seccion?->id,
            'codigo' => $codigo,
            'etiqueta' => $etiqueta,
            'valor' => $valor,
            'unidad' => $unidad,
            'orden' => $orden,
        ]);
    }

    /**
     * @param  array<string, mixed>  $datos
     */
    public function agregarGrafico(
        EjecucionInformeRazonado $ejecucion,
        string $codigo,
        string $titulo,
        string $tipo,
        array $datos,
        ?SeccionInformeRazonado $seccion = null,
        int $orden = 0,
    ): GraficoInformeRazonado {
        return $ejecucion->graficos()->create([
            'seccion_informe_razonado_id' => $seccion?->id,
            'codigo' => $codigo,
            'titulo' => $titulo,
            'tipo' => $tipo,
            'datos' => $datos,
            'orden' => $orden,
        ]);
    }

    public function agregarExcepcion(
        EjecucionInformeRazonado $ejecucion,
        string $codigo,
        string $descripcion,
        string $severidad = 'info',
        ?Model $vinculable = null,
    ): ExcepcionInformeRazonado {
        return $ejecucion->excepciones()->create([
            'codigo' => $codigo,
            'descripcion' => $descripcion,
            'severidad' => $severidad,
            'vinculable_type' => $vinculable?->getMorphClass(),
            'vinculable_id' => $vinculable?->getKey(),
        ]);
    }

    public function agregarNarrativa(
        EjecucionInformeRazonado $ejecucion,
        string $contenido,
        bool $generadoPorIa = false,
        ?SeccionInformeRazonado $seccion = null,
    ): NarrativaInformeRazonado {
        return $ejecucion->narrativas()->create([
            'seccion_informe_razonado_id' => $seccion?->id,
            'contenido' => $contenido,
            'generado_por_ia' => $generadoPorIa,
        ]);
    }

    public function revisarNarrativa(NarrativaInformeRazonado $narrativa, ?User $usuario = null): NarrativaInformeRazonado
    {
        $usuario ??= Auth::user();

        $narrativa->update([
            'revisado_por' => $usuario?->id,
            'revisado_en' => now(),
        ]);

        return $narrativa->refresh();
    }

    public function enviarARevision(EjecucionInformeRazonado $ejecucion, ?User $usuario = null): Proceso
    {
        return $this->transicionWorkflowService->execute($ejecucion->proceso, 'enviar_a_revision', user: $usuario);
    }

    public function aprobar(EjecucionInformeRazonado $ejecucion, ?string $comentario = null, ?User $usuario = null): Proceso
    {
        $usuario ??= Auth::user();

        return DB::transaction(function () use ($ejecucion, $comentario, $usuario) {
            $proceso = $this->transicionWorkflowService->execute($ejecucion->proceso, 'aprobar', $comentario, user: $usuario);

            AprobacionInformeRazonado::create([
                'ejecucion_informe_razonado_id' => $ejecucion->id,
                'aprobado_por' => $usuario?->id,
                'decision' => 'aprobado',
                'comentario' => $comentario,
                'decidido_en' => now(),
            ]);

            return $proceso;
        });
    }

    public function rechazar(EjecucionInformeRazonado $ejecucion, string $comentario, ?User $usuario = null): Proceso
    {
        $usuario ??= Auth::user();

        return DB::transaction(function () use ($ejecucion, $comentario, $usuario) {
            $proceso = $this->transicionWorkflowService->execute($ejecucion->proceso, 'rechazar', $comentario, user: $usuario);

            AprobacionInformeRazonado::create([
                'ejecucion_informe_razonado_id' => $ejecucion->id,
                'aprobado_por' => $usuario?->id,
                'decision' => 'rechazado',
                'comentario' => $comentario,
                'decidido_en' => now(),
            ]);

            return $proceso;
        });
    }

    public function publicar(EjecucionInformeRazonado $ejecucion, ?User $usuario = null): Proceso
    {
        return DB::transaction(function () use ($ejecucion, $usuario) {
            $proceso = $this->transicionWorkflowService->execute($ejecucion->proceso, 'publicar', user: $usuario);

            $contenido = $this->ensamblarContenido($ejecucion);

            SnapshotInformeRazonado::create([
                'ejecucion_informe_razonado_id' => $ejecucion->id,
                'payload_crudo' => $contenido,
                'hash' => hash('sha256', json_encode($contenido, JSON_THROW_ON_ERROR)),
                'capturado_en' => now(),
            ]);

            return $proceso;
        });
    }

    public function exportar(EjecucionInformeRazonado $ejecucion, string $formato, string $rutaArchivo, ?User $usuario = null): ExportacionInformeRazonado
    {
        $usuario ??= Auth::user();

        return $ejecucion->exportaciones()->create([
            'formato' => $formato,
            'ruta_archivo' => $rutaArchivo,
            'generado_por' => $usuario?->id,
            'generado_en' => now(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function ensamblarContenido(EjecucionInformeRazonado $ejecucion): array
    {
        $ejecucion->load(['secciones', 'metricas', 'graficos', 'excepciones', 'narrativas']);

        return [
            'secciones' => $ejecucion->secciones->toArray(),
            'metricas' => $ejecucion->metricas->toArray(),
            'graficos' => $ejecucion->graficos->toArray(),
            'excepciones' => $ejecucion->excepciones->toArray(),
            'narrativas' => $ejecucion->narrativas->toArray(),
        ];
    }
}
