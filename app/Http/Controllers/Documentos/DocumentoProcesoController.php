<?php

namespace App\Http\Controllers\Documentos;

use App\Http\Controllers\Controller;
use App\Http\Requests\Documentos\ReclasificarDocumentoRequest;
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

    /**
     * Sirve el archivo real de un documento para visualizarlo embebido (disposition
     * inline, a diferencia de la descarga forzada de descargar()) — ver también
     * RevisionVerDocumentoController::show(), que sigue el mismo patrón para el
     * panel de Revisión de Pagos.
     */
    public function ver(Proceso $proceso, Documento $documento): BinaryFileResponse
    {
        return response()->file($this->gestorDocumento->descargarRutaArchivo($documento));
    }

    public function destroy(Proceso $proceso, VinculoDocumento $vinculo): RedirectResponse
    {
        Gate::authorize('gestionarDocumentos', $proceso);

        $this->gestorDocumento->desvincular($vinculo);

        return back();
    }

    public function reclasificar(Proceso $proceso, Documento $documento, ReclasificarDocumentoRequest $request): RedirectResponse
    {
        Gate::authorize('gestionarDocumentos', $proceso);

        $vinculado = $proceso->vinculosDocumento()
            ->where('documento_id', $documento->id)
            ->where('activo', true)
            ->exists();

        abort_unless($vinculado, 404);

        $tipoDocumento = TipoDocumento::findOrFail($request->integer('tipo_documento_id'));

        $this->gestorDocumento->reclasificar($documento, $tipoDocumento);

        return back();
    }

    public function reactivar(Proceso $proceso, Documento $documento, ReclasificarDocumentoRequest $request): RedirectResponse
    {
        Gate::authorize('gestionarDocumentos', $proceso);

        $tieneVinculoInactivo = $proceso->vinculosDocumento()
            ->where('documento_id', $documento->id)
            ->where('activo', false)
            ->exists();

        abort_unless($tieneVinculoInactivo, 404);

        $tipoDocumento = TipoDocumento::findOrFail($request->integer('tipo_documento_id'));

        $this->gestorDocumento->reactivarYReclasificar($proceso, $documento, $tipoDocumento);

        return back();
    }
}
