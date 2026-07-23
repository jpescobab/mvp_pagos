<?php

namespace App\Http\Controllers\Adquisiciones;

use App\Http\Controllers\Controller;
use App\Http\Requests\Adquisiciones\BuscarLicitacionMercadoPublicoRequest;
use App\Http\Requests\Adquisiciones\GuardarLicitacionMercadoPublicoRequest;
use App\Http\Resources\Adquisiciones\LicitacionMercadoPublicoResource;
use App\Models\LicitacionMercadoPublico;
use App\Models\ProcesoAdquisicion;
use App\Services\Adquisiciones\DescargaPdfLicitacionMercadoPublicoService;
use App\Services\Adquisiciones\LicitacionMercadoPublicoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LicitacionMercadoPublicoController extends Controller
{
    private const COMPONENTE_BUSCAR = 'adquisiciones/licitaciones-mercado-publico/buscar';

    private const COMPONENTE_INDEX = 'adquisiciones/licitaciones-mercado-publico/index';

    public function __construct(private readonly LicitacionMercadoPublicoService $servicio) {}

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', LicitacionMercadoPublico::class);

        $codigo = $request->string('codigo')->trim()->toString();

        if ($codigo !== '') {
            return $this->renderizarBusqueda($codigo);
        }

        if ($request->boolean('nuevo')) {
            return Inertia::render(self::COMPONENTE_BUSCAR, ['codigo' => null]);
        }

        return $this->renderizarListado($request);
    }

    public function buscar(BuscarLicitacionMercadoPublicoRequest $request): Response
    {
        Gate::authorize('viewAny', LicitacionMercadoPublico::class);

        return $this->renderizarBusqueda($request->string('codigo')->trim()->toString());
    }

    /**
     * Entrega el PDF en el cuerpo, no como redirect: a diferencia de la Orden
     * de Compra, la ficha de Licitación no publica ninguna URL de PDF a la que
     * redirigir (ver DescargaPdfLicitacionMercadoPublicoService).
     */
    public function pdf(
        BuscarLicitacionMercadoPublicoRequest $request,
        DescargaPdfLicitacionMercadoPublicoService $descargaPdf,
    ): StreamedResponse|RedirectResponse {
        Gate::authorize('viewAny', LicitacionMercadoPublico::class);

        $pdf = $descargaPdf->obtener($request->string('codigo')->trim()->toString());

        if ($pdf === null) {
            return back()->withErrors(['pdf' => 'No fue posible obtener el PDF de esta licitación desde Mercado Público.']);
        }

        return response()->streamDownload(
            fn () => print ($pdf['contenido']),
            $pdf['nombre_archivo'],
            ['Content-Type' => 'application/pdf'],
        );
    }

    public function verificar(LicitacionMercadoPublico $licitacion): Response
    {
        Gate::authorize('view', $licitacion);

        $licitacion->load(['items', 'procesoAdquisicion', 'snapshot']);
        $resultado = $this->servicio->compararConApi($licitacion);

        return Inertia::render(self::COMPONENTE_BUSCAR, [
            'codigo' => $licitacion->codigo,
            'licitacionLocal' => new LicitacionMercadoPublicoResource($licitacion),
            'comparacion' => [
                'encontrada' => $resultado['encontrada'],
                'diferencias' => $resultado['diferencias'],
            ],
        ]);
    }

    public function actualizar(LicitacionMercadoPublico $licitacion): RedirectResponse
    {
        Gate::authorize('view', $licitacion);

        $resultado = $this->servicio->compararConApi($licitacion);

        if (! $resultado['encontrada']) {
            return back()->withErrors(['licitacion' => 'La licitación ya no está disponible en Mercado Público.']);
        }

        $this->servicio->aplicarActualizacion($licitacion, $resultado['payload_normalizado'], $resultado['snapshot']);

        Inertia::flash('toast', ['type' => 'success', 'message' => "Licitación \"{$licitacion->codigo}\" actualizada desde Mercado Público."]);

        return to_route('adquisiciones.licitaciones_mp.show', $licitacion);
    }

    public function guardar(GuardarLicitacionMercadoPublicoRequest $request): RedirectResponse
    {
        Gate::authorize('create', LicitacionMercadoPublico::class);

        $resultado = $this->servicio->consultarApi($request->string('codigo')->toString());

        if (! $resultado['encontrada']) {
            return back()->withErrors(['codigo' => 'La licitación no fue encontrada en Mercado Público.']);
        }

        $procesoAdquisicionId = $request->integer('proceso_adquisicion_id');

        $guardado = $this->servicio->guardarDesdeApi(
            $resultado['payload_normalizado'],
            $resultado['snapshot'],
            $procesoAdquisicionId !== 0 ? $procesoAdquisicionId : null,
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => "Licitación \"{$guardado['licitacion']->codigo}\" guardada."]);

        return to_route('adquisiciones.licitaciones_mp.index');
    }

    public function show(LicitacionMercadoPublico $licitacion): Response
    {
        Gate::authorize('view', $licitacion);

        $licitacion->load(['items', 'procesoAdquisicion', 'snapshot']);

        return Inertia::render('adquisiciones/licitaciones-mercado-publico/show', [
            'licitacion' => new LicitacionMercadoPublicoResource($licitacion),
            'procesosAdquisicion' => ProcesoAdquisicion::all(['id', 'codigo']),
        ]);
    }

    private function renderizarListado(Request $request): Response
    {
        $q = $request->string('q')->trim()->toString();

        $licitaciones = LicitacionMercadoPublico::query()
            ->with(['procesoAdquisicion'])
            ->when($q !== '', fn ($query) => $query->where('codigo', 'like', "%{$q}%"))
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render(self::COMPONENTE_INDEX, [
            'licitaciones' => LicitacionMercadoPublicoResource::collection($licitaciones),
            'q' => $q !== '' ? $q : null,
        ]);
    }

    private function renderizarBusqueda(string $codigo): Response
    {
        if ($codigo === '') {
            return Inertia::render(self::COMPONENTE_BUSCAR, ['codigo' => null]);
        }

        $licitacionLocal = $this->servicio->buscarLocal($codigo);

        if ($licitacionLocal !== null) {
            return Inertia::render(self::COMPONENTE_BUSCAR, [
                'codigo' => $codigo,
                'licitacionLocal' => new LicitacionMercadoPublicoResource($licitacionLocal),
            ]);
        }

        $resultado = $this->servicio->consultarApi($codigo);

        if (! $resultado['encontrada']) {
            return Inertia::render(self::COMPONENTE_BUSCAR, [
                'codigo' => $codigo,
                'noEncontrada' => true,
            ]);
        }

        return Inertia::render(self::COMPONENTE_BUSCAR, [
            'codigo' => $codigo,
            'vistaPrevia' => [
                'payload_normalizado' => $resultado['payload_normalizado'],
                'payload_crudo' => $resultado['snapshot']?->payload_crudo,
            ],
        ]);
    }
}
