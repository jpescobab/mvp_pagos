<?php

namespace App\Http\Controllers\InformesRazonados;

use App\Http\Controllers\Controller;
use App\Http\Requests\InformesRazonados\GenerarExportacionInformeRazonadoRequest;
use App\Models\EjecucionInformeRazonado;
use App\Models\ExportacionInformeRazonado;
use App\Services\InformesRazonados\ExportadorInformeRazonadoService;
use App\Services\InformesRazonados\InformeRazonadoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportacionInformeRazonadoController extends Controller
{
    public function __construct(
        private readonly InformeRazonadoService $servicio,
        private readonly ExportadorInformeRazonadoService $exportador,
    ) {}

    public function store(EjecucionInformeRazonado $ejecucion, GenerarExportacionInformeRazonadoRequest $request): RedirectResponse
    {
        Gate::authorize('exportar', $ejecucion);

        $formato = (string) $request->validated('formato');
        $ruta = $this->exportador->exportar($ejecucion, $formato);

        $this->servicio->exportar($ejecucion, $formato, $ruta);

        return back();
    }

    public function descargar(ExportacionInformeRazonado $exportacion): StreamedResponse
    {
        Gate::authorize('exportar', $exportacion->ejecucionInformeRazonado);

        abort_unless(Storage::disk('local')->exists($exportacion->ruta_archivo), 404);

        $contentType = match ($exportacion->formato) {
            'pdf' => 'application/pdf',
            default => 'text/html',
        };

        return Storage::disk('local')->download(
            $exportacion->ruta_archivo,
            basename($exportacion->ruta_archivo),
            ['Content-Type' => $contentType],
        );
    }
}
