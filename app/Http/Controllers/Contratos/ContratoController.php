<?php

namespace App\Http\Controllers\Contratos;

use App\Exceptions\ContratoException;
use App\Exceptions\ContratoSinProveedorException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Contratos\StoreContratoRequest;
use App\Http\Requests\Contratos\UpdateContratoRequest;
use App\Http\Resources\Contratos\ContratoResource;
use App\Models\CasoPagoProveedor;
use App\Models\ConjuntoRequisitosDocumentales;
use App\Models\Contrato;
use App\Models\LicitacionMercadoPublico;
use App\Models\ProcesoAdquisicion;
use App\Models\TipoDocumento;
use App\Services\Contratos\ContratoService;
use App\Services\Documentos\ResolutorChecklistDocumentalProceso;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ContratoController extends Controller
{
    public function __construct(private readonly ResolutorChecklistDocumentalProceso $resolutorChecklist) {}

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Contrato::class);

        $q = $request->string('q')->toString();

        $contratos = Contrato::query()
            ->with(['proveedor', 'proceso.estadoActual'])
            ->when($q !== '', fn ($query) => $query->where(
                // id_institucional es bigint: Postgres no castea implícito a
                // texto para LIKE (a diferencia de SQLite/MySQL). CAST(...AS TEXT)
                // es portable entre Postgres (producción) y SQLite (tests).
                fn ($sub) => $sub
                    ->whereRaw('CAST(id_institucional AS TEXT) LIKE ?', ["%{$q}%"])
                    ->orWhere('codigo', 'like', "%{$q}%")
                    ->orWhere('id_proceso_mp', 'like', "%{$q}%")
                    ->orWhere('referencia', 'like', "%{$q}%"),
            ))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('contratos/index', [
            'contratos' => ContratoResource::collection($contratos),
            'q' => $q !== '' ? $q : null,
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', Contrato::class);

        return Inertia::render('contratos/create');
    }

    public function store(StoreContratoRequest $request, ContratoService $servicio): RedirectResponse
    {
        Gate::authorize('create', Contrato::class);

        $datos = $request->validated();
        $datosProveedor = Arr::pull($datos, 'proveedor');

        try {
            $contrato = $servicio->crear($datos, $datosProveedor);
        } catch (ContratoException $e) {
            return back()->withErrors([$e->campo() => $e->getMessage()]);
        } catch (ContratoSinProveedorException $e) {
            return back()->withErrors(['proveedor' => $e->getMessage()]);
        }

        return to_route('contratos.show', $contrato);
    }

    public function show(Contrato $contrato, Request $request): Response
    {
        Gate::authorize('view', $contrato);

        $contrato->load([
            'proveedor',
            'procesoAdquisicion',
            'licitacionMercadoPublico',
            'proceso.estadoActual',
            'proceso.definicionWorkflow.transiciones',
            'proceso.historialTransiciones.transicion',
            'proceso.historialTransiciones.estadoOrigen',
            'proceso.historialTransiciones.estadoDestino',
            'proceso.historialTransiciones.user',
            'itemsConvenioPrecio',
            'cuotas.casoPagoProveedor',
            'ordenesCompraMercadoPublico',
        ]);

        $conjuntoRequisitos = ConjuntoRequisitosDocumentales::where('codigo', 'contratos')->first();

        if ($conjuntoRequisitos !== null) {
            $this->resolutorChecklist->resolve($contrato->proceso, $conjuntoRequisitos, $request->user());
        }

        $contrato->proceso->load([
            'checklist.items',
            'vinculosDocumento.documento.tipoDocumento',
            'vinculosDocumento.documento.versiones',
            'vinculosDocumento.documento.validaciones.validadoPor',
        ]);

        return Inertia::render('contratos/show', [
            'contrato' => new ContratoResource($contrato),
            'tiposDocumento' => TipoDocumento::where('activo', true)->get(['id', 'nombre']),
            'procesosAdquisicion' => ProcesoAdquisicion::all(['id', 'codigo']),
            'licitacionesMercadoPublico' => LicitacionMercadoPublico::all(['id', 'codigo']),
            'casosPagoProveedor' => CasoPagoProveedor::query()
                ->with('proveedor')
                ->latest()
                ->limit(100)
                ->get(['id', 'sgf_id', 'proveedor_id', 'monto'])
                ->map(fn ($caso) => [
                    'id' => $caso->id,
                    'sgf_id' => $caso->sgf_id,
                    'proveedor' => $caso->proveedor?->nombre,
                    'monto' => $caso->monto,
                ]),
        ]);
    }

    public function edit(Contrato $contrato): Response
    {
        Gate::authorize('update', $contrato);

        return Inertia::render('contratos/edit', [
            'contrato' => new ContratoResource($contrato->load('proveedor')),
        ]);
    }

    public function update(UpdateContratoRequest $request, Contrato $contrato, ContratoService $servicio): RedirectResponse
    {
        Gate::authorize('update', $contrato);

        try {
            $servicio->actualizar($contrato, $request->validated());
        } catch (ContratoException $e) {
            return back()->withErrors([$e->campo() => $e->getMessage()]);
        }

        return to_route('contratos.show', $contrato);
    }
}
