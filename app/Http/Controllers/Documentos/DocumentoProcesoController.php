<?php

namespace App\Http\Controllers\Documentos;

use App\Http\Controllers\Controller;
use App\Http\Requests\Documentos\SubirDocumentoProcesoRequest;
use App\Http\Requests\Documentos\SubirNuevaVersionDocumentoRequest;
use App\Models\Documento;
use App\Models\Proceso;
use App\Models\TipoDocumento;
use App\Models\VinculoDocumento;
use App\Services\Documentos\GestorDocumentoProceso;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DocumentoProcesoController extends Controller
{
    public function __construct(private readonly GestorDocumentoProceso $gestorDocumento) {}

    public function store(Proceso $proceso, SubirDocumentoProcesoRequest $request): RedirectResponse
    {
        Gate::authorize('gestionarDocumentos', $proceso);

        $tipoDocumento = TipoDocumento::findOrFail($request->integer('tipo_documento_id'));

        $this->gestorDocumento->subirYVincular(
            $proceso,
            $request->file('archivo'),
            $tipoDocumento,
            $request->user(),
        );

        return back();
    }

    public function nuevaVersion(Proceso $proceso, Documento $documento, SubirNuevaVersionDocumentoRequest $request): RedirectResponse
    {
        Gate::authorize('gestionarDocumentos', $proceso);

        $this->gestorDocumento->subirNuevaVersion(
            $documento,
            $request->file('archivo'),
            $request->user(),
        );

        return back();
    }

    public function descargar(Proceso $proceso, Documento $documento): BinaryFileResponse
    {
        return response()->download($this->gestorDocumento->descargarRutaArchivo($documento));
    }

    public function destroy(Proceso $proceso, VinculoDocumento $vinculo): RedirectResponse
    {
        Gate::authorize('gestionarDocumentos', $proceso);

        $this->gestorDocumento->desvincular($vinculo);

        return back();
    }
}
