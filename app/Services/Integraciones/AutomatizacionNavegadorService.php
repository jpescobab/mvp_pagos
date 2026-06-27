<?php

namespace App\Services\Integraciones;

use App\Exceptions\ConectorAutomatizacionNoAutorizadoException;
use App\Models\ArtefactoAutomatizacionNavegador;
use App\Models\ConectorAutomatizacionNavegador;
use App\Models\EjecucionAutomatizacionNavegador;
use App\Models\PasoAutomatizacionNavegador;
use App\Models\PerfilAutenticacionNavegador;
use App\Models\TrabajoIntegracion;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class AutomatizacionNavegadorService
{
    public function iniciarEjecucion(
        ConectorAutomatizacionNavegador $conector,
        ?PerfilAutenticacionNavegador $perfil = null,
        ?TrabajoIntegracion $trabajo = null,
        ?User $usuario = null,
    ): EjecucionAutomatizacionNavegador {
        if (! $conector->estaAutorizado()) {
            throw ConectorAutomatizacionNoAutorizadoException::paraConector($conector);
        }

        $usuario ??= Auth::user();

        return EjecucionAutomatizacionNavegador::create([
            'conector_automatizacion_navegador_id' => $conector->id,
            'perfil_autenticacion_navegador_id' => $perfil?->id,
            'trabajo_integracion_id' => $trabajo?->id,
            'iniciado_por' => $usuario?->id,
            'estado' => 'en_progreso',
            'iniciado_en' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>|null  $detalle
     */
    public function registrarPaso(
        EjecucionAutomatizacionNavegador $ejecucion,
        int $orden,
        string $accion,
        string $estado,
        ?array $detalle = null,
        ?string $error = null,
    ): PasoAutomatizacionNavegador {
        return $ejecucion->pasos()->create([
            'orden' => $orden,
            'accion' => $accion,
            'detalle' => $detalle,
            'estado' => $estado,
            'error' => $error,
            'ejecutado_en' => now(),
        ]);
    }

    public function registrarArtefacto(
        EjecucionAutomatizacionNavegador $ejecucion,
        string $tipo,
        string $rutaAlmacenamiento,
        string $hash,
        ?PasoAutomatizacionNavegador $paso = null,
    ): ArtefactoAutomatizacionNavegador {
        return $ejecucion->artefactos()->create([
            'paso_automatizacion_navegador_id' => $paso?->id,
            'tipo' => $tipo,
            'ruta_almacenamiento' => $rutaAlmacenamiento,
            'hash' => $hash,
            'capturado_en' => now(),
        ]);
    }

    public function finalizarEjecucion(EjecucionAutomatizacionNavegador $ejecucion, string $estado, ?string $resumenResultado = null, ?string $error = null): EjecucionAutomatizacionNavegador
    {
        $ejecucion->update([
            'estado' => $estado,
            'finalizado_en' => now(),
            'resumen_resultado' => $resumenResultado,
            'error' => $error,
        ]);

        return $ejecucion->refresh();
    }
}
