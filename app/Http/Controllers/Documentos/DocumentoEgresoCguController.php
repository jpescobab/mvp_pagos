<?php

namespace App\Http\Controllers\Documentos;

use App\Http\Controllers\Controller;
use App\Http\Requests\Documentos\SubirDocumentoProcesoRequest;
use App\Models\Documento;
use App\Models\EgresoCgu;
use App\Models\TipoDocumento;
use App\Models\VinculoDocumento;
use App\Services\Documentos\GestorDocumentoProceso;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DocumentoEgresoCguController extends Controller
{
    public function __construct(private readonly GestorDocumentoProceso $gestorDocumento) {}

    public function store(EgresoCgu $egresoCgu, SubirDocumentoProcesoRequest $request): RedirectResponse
    {
        Gate::authorize('gestionarDocumentos', $egresoCgu);

        $tipoDocumento = TipoDocumento::findOrFail($request->integer('tipo_documento_id'));

        $this->gestorDocumento->subirYVincular(
            $egresoCgu,
            $request->file('archivo'),
            $tipoDocumento,
            $request->user(),
        );

        return back();
    }

    public function descargar(EgresoCgu $egresoCgu, Documento $documento): BinaryFileResponse
    {
        return response()->download($this->gestorDocumento->descargarRutaArchivo($documento));
    }

    public function destroy(EgresoCgu $egresoCgu, VinculoDocumento $vinculo): RedirectResponse
    {
        Gate::authorize('gestionarDocumentos', $egresoCgu);

        $this->gestorDocumento->desvincular($vinculo);

        return back();
    }
}
