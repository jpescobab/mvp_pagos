<?php

namespace App\Http\Controllers\Sgf;

use App\Exceptions\ConectorAutomatizacionNoAutorizadoException;
use App\Http\Controllers\Controller;
use App\Jobs\ImportarCasosPendientesSgfJob;
use App\Models\CasoPagoProveedor;
use App\Models\SistemaExterno;
use App\Models\TrabajoIntegracion;
use App\Services\Integraciones\IntegracionExternaService;
use App\Services\Sgf\ConectorSgfPlaywrightService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class ImportarCasosPendientesSgfController extends Controller
{
    public function __construct(
        private readonly IntegracionExternaService $integracionExterna,
        private readonly ConectorSgfPlaywrightService $conectorSgf,
    ) {}

    public function store(): RedirectResponse
    {
        Gate::authorize('importarCasosSgf', CasoPagoProveedor::class);

        try {
            $this->conectorSgf->verificarConectorAutorizado();
        } catch (ConectorAutomatizacionNoAutorizadoException $e) {
            Inertia::flash('toast', ['type' => 'error', 'message' => $e->getMessage()]);

            return back();
        }

        $sistema = SistemaExterno::where('codigo', 'SGF')->firstOrFail();

        $trabajoEnCurso = TrabajoIntegracion::where('sistema_externo_id', $sistema->id)
            ->where('tipo', 'importar_pendientes')
            ->where('estado', 'en_progreso')
            ->latest('id')
            ->first();

        // Chequeo perezoso: si el trabajo encontrado ya superó su umbral de
        // inactividad, se marca como huérfano acá mismo (sin esperar el
        // próximo barrido programado) y deja de bloquear un nuevo intento.
        if ($trabajoEnCurso !== null) {
            $trabajoEnCurso = $this->integracionExterna->expirarSiEsHuerfano($trabajoEnCurso);
        }

        if ($trabajoEnCurso !== null && $trabajoEnCurso->estado === 'en_progreso') {
            Inertia::flash('toast', ['type' => 'info', 'message' => 'Ya hay una importación de casos pendientes de SGF en curso.']);

            return to_route('sgf.importaciones.show', $trabajoEnCurso);
        }

        $trabajo = $this->integracionExterna->iniciarTrabajo($sistema, 'importar_pendientes', 'playwright');

        ImportarCasosPendientesSgfJob::dispatch($trabajo);

        return to_route('sgf.importaciones.show', $trabajo);
    }
}
