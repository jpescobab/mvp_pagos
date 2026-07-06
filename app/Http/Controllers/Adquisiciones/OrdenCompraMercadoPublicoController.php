<?php

namespace App\Http\Controllers\Adquisiciones;

use App\Http\Controllers\Controller;
use App\Http\Requests\Adquisiciones\BuscarOrdenCompraMercadoPublicoRequest;
use App\Http\Requests\Adquisiciones\GuardarOrdenCompraMercadoPublicoRequest;
use App\Http\Resources\Adquisiciones\OrdenCompraMercadoPublicoResource;
use App\Models\OrdenCompraMercadoPublico;
use App\Models\ProcesoAdquisicion;
use App\Models\Proveedor;
use App\Services\Adquisiciones\OrdenCompraMercadoPublicoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class OrdenCompraMercadoPublicoController extends Controller
{
    private const COMPONENTE_BUSCAR = 'adquisiciones/ordenes-compra-mercado-publico/buscar';

    public function __construct(private readonly OrdenCompraMercadoPublicoService $servicio) {}

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', OrdenCompraMercadoPublico::class);

        $codigo = $request->string('codigo')->trim()->toString();

        if ($codigo === '') {
            return Inertia::render(self::COMPONENTE_BUSCAR, ['codigo' => null]);
        }

        return $this->renderizarBusqueda($codigo);
    }

    public function buscar(BuscarOrdenCompraMercadoPublicoRequest $request): Response
    {
        Gate::authorize('viewAny', OrdenCompraMercadoPublico::class);

        return $this->renderizarBusqueda($request->string('codigo')->trim()->toString());
    }

    public function verificar(OrdenCompraMercadoPublico $orden): Response
    {
        Gate::authorize('view', $orden);

        $orden->load(['items', 'proveedor', 'procesoAdquisicion']);
        $resultado = $this->servicio->compararConApi($orden);

        return Inertia::render(self::COMPONENTE_BUSCAR, [
            'codigo' => $orden->codigo,
            'ordenLocal' => new OrdenCompraMercadoPublicoResource($orden),
            'comparacion' => [
                'encontrada' => $resultado['encontrada'],
                'diferencias' => $resultado['diferencias'],
            ],
        ]);
    }

    public function actualizar(OrdenCompraMercadoPublico $orden): RedirectResponse
    {
        Gate::authorize('view', $orden);

        $resultado = $this->servicio->compararConApi($orden);

        if (! $resultado['encontrada']) {
            return back()->withErrors(['orden' => 'La OC ya no está disponible en Mercado Público.']);
        }

        $this->servicio->aplicarActualizacion($orden, $resultado['payload_normalizado'], $resultado['snapshot']);

        Inertia::flash('toast', ['type' => 'success', 'message' => "OC \"{$orden->codigo}\" actualizada desde Mercado Público."]);

        return to_route('adquisiciones.ordenes_compra_mp.show', $orden);
    }

    public function guardar(GuardarOrdenCompraMercadoPublicoRequest $request): RedirectResponse
    {
        Gate::authorize('create', OrdenCompraMercadoPublico::class);

        $resultado = $this->servicio->consultarApi($request->string('codigo')->toString());

        if (! $resultado['encontrada']) {
            return back()->withErrors(['codigo' => 'La OC no fue encontrada en Mercado Público.']);
        }

        $proveedorId = $request->integer('proveedor_id');
        $proveedor = $proveedorId !== 0
            ? Proveedor::find($proveedorId)
            : $this->servicio->verificarProveedor($resultado['payload_normalizado']);

        if ($proveedor === null) {
            return back()->withErrors(['proveedor_id' => 'Debes indicar el proveedor emisor de la OC antes de guardarla.']);
        }

        $procesoAdquisicionId = $request->integer('proceso_adquisicion_id');

        $orden = $this->servicio->guardarDesdeApi(
            $resultado['payload_normalizado'],
            $proveedor,
            $resultado['snapshot'],
            $procesoAdquisicionId !== 0 ? $procesoAdquisicionId : null,
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => "OC \"{$orden->codigo}\" guardada."]);

        return to_route('adquisiciones.ordenes_compra_mp.show', $orden);
    }

    public function show(OrdenCompraMercadoPublico $orden): Response
    {
        Gate::authorize('view', $orden);

        $orden->load(['items', 'proveedor', 'procesoAdquisicion']);

        return Inertia::render('adquisiciones/ordenes-compra-mercado-publico/show', [
            'orden' => new OrdenCompraMercadoPublicoResource($orden),
            'procesosAdquisicion' => ProcesoAdquisicion::all(['id', 'codigo']),
        ]);
    }

    private function renderizarBusqueda(string $codigo): Response
    {
        if ($codigo === '') {
            return Inertia::render(self::COMPONENTE_BUSCAR, ['codigo' => null]);
        }

        $ordenLocal = $this->servicio->buscarLocal($codigo);

        if ($ordenLocal !== null) {
            return Inertia::render(self::COMPONENTE_BUSCAR, [
                'codigo' => $codigo,
                'ordenLocal' => new OrdenCompraMercadoPublicoResource($ordenLocal),
            ]);
        }

        $resultado = $this->servicio->consultarApi($codigo);

        if (! $resultado['encontrada']) {
            return Inertia::render(self::COMPONENTE_BUSCAR, [
                'codigo' => $codigo,
                'noEncontrada' => true,
            ]);
        }

        $proveedor = $this->servicio->verificarProveedor($resultado['payload_normalizado']);

        return Inertia::render(self::COMPONENTE_BUSCAR, [
            'codigo' => $codigo,
            'vistaPrevia' => [
                'payload_normalizado' => $resultado['payload_normalizado'],
                'proveedor_existente' => $proveedor === null ? null : [
                    'id' => $proveedor->id,
                    'nombre' => $proveedor->nombre,
                    'rutproveedor' => $proveedor->rutproveedor,
                ],
            ],
        ]);
    }
}
