<?php

namespace App\Http\Controllers\ConsumoBasico;

use App\Http\Controllers\Controller;
use App\Http\Requests\ConsumoBasico\SubirDocumentoConsumoBasicoRequest;
use App\Models\ConsumoBasico;
use App\Models\Documento;
use App\Models\VinculoDocumento;
use App\Services\ConsumoBasico\ConsumoBasicoService;
use App\Services\Documentos\GestorDocumentoProceso;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ConsumoBasicoDocumentoController extends Controller
{
    public function __construct(
        private readonly ConsumoBasicoService $consumoBasicoService,
        private readonly GestorDocumentoProceso $gestorDocumento,
    ) {}

    public function store(ConsumoBasico $consumoBasico, SubirDocumentoConsumoBasicoRequest $request): RedirectResponse
    {
        Gate::authorize('update', $consumoBasico);

        $this->consumoBasicoService->adjuntarDocumento(
            $consumoBasico,
            $request->file('archivo'),
            $request->user(),
        );

        return back();
    }

    public function destroy(ConsumoBasico $consumoBasico, VinculoDocumento $vinculo): RedirectResponse
    {
        Gate::authorize('update', $consumoBasico);

        // Las rutas no están scoped: sin esto, cualquier usuario con permiso
        // sobre ESTE consumo básico podría pasar el id del vínculo de OTRO
        // consumo básico (o de un Proceso) y desvincularlo igual.
        abort_unless(
            $vinculo->vinculable_type === ConsumoBasico::class && $vinculo->vinculable_id === $consumoBasico->id,
            404,
        );

        $this->gestorDocumento->desvincular($vinculo);

        return back();
    }

    public function descargar(ConsumoBasico $consumoBasico, Documento $documento): BinaryFileResponse
    {
        Gate::authorize('view', $consumoBasico);

        // Mismo motivo que en destroy(): confirmar que el Documento está
        // realmente vinculado a ESTE consumo básico antes de servirlo.
        abort_unless(
            $consumoBasico->vinculosDocumento()->where('documento_id', $documento->id)->exists(),
            404,
        );

        return response()->download($this->gestorDocumento->descargarRutaArchivo($documento));
    }
}
