<?php

namespace App\Http\Controllers\InformesRazonados;

use App\Exceptions\TransicionWorkflowException;
use App\Http\Controllers\Controller;
use App\Http\Requests\InformesRazonados\EjecutarTransicionInformeRazonadoRequest;
use App\Models\EjecucionInformeRazonado;
use App\Services\InformesRazonados\InformeRazonadoService;
use Illuminate\Http\RedirectResponse;

class TransicionEjecucionInformeRazonadoController extends Controller
{
    public function __construct(private readonly InformeRazonadoService $servicio) {}

    public function store(EjecucionInformeRazonado $ejecucion, EjecutarTransicionInformeRazonadoRequest $request): RedirectResponse
    {
        $codigo = $request->string('codigo')->toString();
        $comentario = $request->string('comentario')->toString() ?: null;

        try {
            match ($codigo) {
                'enviar_a_revision' => $this->servicio->enviarARevision($ejecucion),
                'aprobar' => $this->servicio->aprobar($ejecucion, $comentario),
                'rechazar' => $this->servicio->rechazar($ejecucion, $comentario ?? ''),
                'publicar' => $this->servicio->publicar($ejecucion),
                default => throw TransicionWorkflowException::transicionNoPermitida($codigo),
            };
        } catch (TransicionWorkflowException $e) {
            return back()->withErrors(['transicion' => $e->getMessage()]);
        }

        return back();
    }
}
