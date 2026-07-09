<?php

namespace App\Services\Integraciones;

use App\Models\SistemaExterno;
use App\Models\SnapshotDatosExterno;
use App\Models\SolicitudApiExterna;
use App\Models\TrabajoIntegracion;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class IntegracionExternaService
{
    public function iniciarTrabajo(SistemaExterno $sistema, string $tipo, string $mecanismo, ?User $usuario = null): TrabajoIntegracion
    {
        $usuario ??= Auth::user();

        return TrabajoIntegracion::create([
            'sistema_externo_id' => $sistema->id,
            'tipo' => $tipo,
            'mecanismo' => $mecanismo,
            'estado' => 'en_progreso',
            'iniciado_por' => $usuario?->id,
            'iniciado_en' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>|null  $payloadEnviado
     * @param  array<string, mixed>|null  $payloadRecibido
     */
    public function registrarSolicitud(
        SistemaExterno $sistema,
        string $metodoHttp,
        string $endpoint,
        string $estado,
        ?array $payloadEnviado = null,
        ?array $payloadRecibido = null,
        ?int $codigoRespuestaHttp = null,
        ?string $error = null,
        ?int $duracionMs = null,
        ?TrabajoIntegracion $trabajo = null,
    ): SolicitudApiExterna {
        $solicitud = SolicitudApiExterna::create([
            'sistema_externo_id' => $sistema->id,
            'trabajo_integracion_id' => $trabajo?->id,
            'metodo_http' => $metodoHttp,
            'endpoint' => $endpoint,
            'payload_enviado' => $payloadEnviado,
            'payload_recibido' => $payloadRecibido,
            'codigo_respuesta_http' => $codigoRespuestaHttp,
            'estado' => $estado,
            'error' => $error,
            'duracion_ms' => $duracionMs,
            'ejecutado_en' => now(),
        ]);

        $trabajo?->increment('total_elementos');

        return $solicitud;
    }

    /**
     * @param  array<string, mixed>  $payloadCrudo
     * @param  array<string, mixed>|null  $payloadNormalizado
     */
    public function registrarSnapshot(
        SistemaExterno $sistema,
        string $metodoCaptura,
        array $payloadCrudo,
        ?array $payloadNormalizado = null,
        ?string $referenciaExterna = null,
        ?TrabajoIntegracion $trabajo = null,
        ?SolicitudApiExterna $solicitud = null,
        ?Model $vinculable = null,
        ?User $usuario = null,
    ): SnapshotDatosExterno {
        $usuario ??= Auth::user();

        return SnapshotDatosExterno::create([
            'sistema_externo_id' => $sistema->id,
            'trabajo_integracion_id' => $trabajo?->id,
            'solicitud_api_externa_id' => $solicitud?->id,
            'metodo_captura' => $metodoCaptura,
            'referencia_externa' => $referenciaExterna,
            'payload_crudo' => $payloadCrudo,
            'payload_normalizado' => $payloadNormalizado,
            'hash' => hash('sha256', json_encode($payloadCrudo, JSON_THROW_ON_ERROR)),
            'capturado_en' => now(),
            'capturado_por' => $usuario?->id,
            'vinculable_type' => $vinculable?->getMorphClass(),
            'vinculable_id' => $vinculable?->getKey(),
        ]);
    }

    public function finalizarTrabajo(TrabajoIntegracion $trabajo, string $estado, ?string $error = null): TrabajoIntegracion
    {
        $trabajo->update([
            'estado' => $estado,
            'finalizado_en' => now(),
            'error' => $error,
        ]);

        return $trabajo->refresh();
    }

    /**
     * Marca un trabajo como huérfano: el proceso que lo ejecutaba
     * probablemente murió sin poder reportar ni éxito ni error (timeout del
     * worker de la cola, terminal cerrada, equipo suspendido, etc.). Distinto
     * de `error` para no mezclar, en reportes, un rechazo real del sistema
     * externo con un proceso que dejó de correr sin poder decir por qué.
     */
    public function marcarHuerfano(TrabajoIntegracion $trabajo): TrabajoIntegracion
    {
        $minutos = $trabajo->umbralHuerfanoEnMinutos();

        return $this->finalizarTrabajo(
            $trabajo,
            'huerfano',
            "Detectado automáticamente como huérfano: sin actividad tras {$minutos} minutos desde iniciado_en, se asume que el proceso que lo ejecutaba dejó de correr.",
        );
    }

    /**
     * Chequeo perezoso: si el trabajo ya no está en_progreso o sigue dentro
     * de su umbral, lo devuelve sin tocar. Si superó su umbral, lo marca
     * como huérfano y devuelve la versión actualizada — pensado para
     * invocarse justo antes de evaluar una guarda de "ya hay uno en curso",
     * así un reintento inmediato no tiene que esperar el próximo barrido
     * programado.
     */
    public function expirarSiEsHuerfano(TrabajoIntegracion $trabajo): TrabajoIntegracion
    {
        return $trabajo->esHuerfano() ? $this->marcarHuerfano($trabajo) : $trabajo;
    }

    /**
     * Barrido completo: marca como huérfano cada trabajo_integracion en
     * en_progreso que superó el umbral de su tipo. Pensado para correr
     * periódicamente vía Scheduler (ver routes/console.php).
     */
    public function expirarHuerfanos(): int
    {
        return TrabajoIntegracion::where('estado', 'en_progreso')
            ->get()
            ->filter(fn (TrabajoIntegracion $trabajo) => $trabajo->esHuerfano())
            ->each(fn (TrabajoIntegracion $trabajo) => $this->marcarHuerfano($trabajo))
            ->count();
    }
}
