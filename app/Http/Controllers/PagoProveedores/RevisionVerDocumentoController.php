<?php

namespace App\Http\Controllers\PagoProveedores;

use App\Http\Controllers\Controller;
use App\Models\CasoPagoProveedor;
use App\Models\Documento;
use App\Models\EgresoCgu;
use App\Services\Documentos\GestorDocumentoProceso;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class RevisionVerDocumentoController extends Controller
{
    public function __construct(private readonly GestorDocumentoProceso $gestorDocumento) {}

    /**
     * Sirve el archivo real de un documento para visualizarlo embebido en el
     * panel de revisión (disposition inline, a diferencia de la descarga
     * forzada de DocumentoProcesoController::descargar).
     */
    public function show(EgresoCgu $egresoCgu, CasoPagoProveedor $caso, Documento $documento): BinaryFileResponse
    {
        Gate::authorize('revisar', $egresoCgu);

        return response()->file($this->gestorDocumento->descargarRutaArchivo($documento));
    }
}
