<?php

namespace App\Http\Controllers\Adquisiciones;

use App\Exceptions\ProcesoAdquisicionException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Adquisiciones\ActualizarProcesoAdquisicionRequest;
use App\Http\Requests\Adquisiciones\CrearProcesoAdquisicionRequest;
use App\Http\Resources\Adquisiciones\ProcesoAdquisicionResource;
use App\Models\Ccosto;
use App\Models\ConjuntoRequisitosDocumentales;
use App\Models\ModalidadAdquisicion;
use App\Models\ProcesoAdquisicion;
use App\Models\Proveedor;
use App\Models\TipoDocumento;
use App\Services\Adquisiciones\ProcesoAdquisicionService;
use App\Services\Documentos\ResolutorChecklistDocumentalProceso;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ProcesoAdquisicionController extends Controller
{
    public function __construct(private readonly ResolutorChecklistDocumentalProceso $resolutorChecklist) {}

    public function index(): Response
    {
        Gate::authorize('viewAny', ProcesoAdquisicion::class);

        $procesos = ProcesoAdquisicion::with(['modalidad', 'ccosto', 'proveedor', 'proceso.estadoActual'])
            ->paginate(20);

        return Inertia::render('adquisiciones/procesos/index', [
            'procesos' => ProcesoAdquisicionResource::collection($procesos),
        ]);
    }

    public function show(ProcesoAdquisicion $proceso, Request $request): Response
    {
        Gate::authorize('view', $proceso);

        $proceso->load([
            'modalidad',
            'ccosto',
            'proveedor',
            'proceso.estadoActual',
            'proceso.definicionWorkflow.transiciones',
            'proceso.historialTransiciones.transicion',
            'proceso.historialTransiciones.estadoOrigen',
            'proceso.historialTransiciones.estadoDestino',
            'proceso.historialTransiciones.user',
            'casosPagoProveedor',
            'ordenesCompraMercadoPublico',
            'licitacionesMercadoPublico',
        ]);

        $conjuntoRequisitos = ConjuntoRequisitosDocumentales::where('codigo', 'adquisiciones')->first();

        if ($conjuntoRequisitos !== null) {
            $this->resolutorChecklist->resolve($proceso->proceso, $conjuntoRequisitos, $request->user());
        }

        $proceso->proceso->load([
            'checklist.items',
            'vinculosDocumento.documento.tipoDocumento',
            'vinculosDocumento.documento.versiones',
            'vinculosDocumento.documento.validaciones.validadoPor',
        ]);

        return Inertia::render('adquisiciones/procesos/show', [
            'proceso' => new ProcesoAdquisicionResource($proceso),
            'tiposDocumento' => TipoDocumento::where('activo', true)->get(['id', 'nombre']),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', ProcesoAdquisicion::class);

        return Inertia::render('adquisiciones/procesos/crear', [
            'modalidades' => $this->modalidadesActivas(),
            'ccostos' => $this->ccostosDisponibles(),
            'proveedores' => $this->proveedoresActivos(),
        ]);
    }

    public function store(CrearProcesoAdquisicionRequest $request, ProcesoAdquisicionService $servicio): RedirectResponse
    {
        Gate::authorize('create', ProcesoAdquisicion::class);

        try {
            $proceso = $servicio->crear($request->validated());
        } catch (ProcesoAdquisicionException $e) {
            return back()->withErrors(['modalidad_id' => $e->getMessage()]);
        }

        return to_route('adquisiciones.procesos.show', $proceso);
    }

    public function edit(ProcesoAdquisicion $proceso): Response
    {
        Gate::authorize('update', $proceso);

        return Inertia::render('adquisiciones/procesos/editar', [
            'proceso' => [
                'id' => $proceso->id,
                'codigo' => $proceso->codigo,
                'modalidad_id' => $proceso->modalidad_id,
                'ccosto_id' => $proceso->ccosto_id,
                'proveedor_id' => $proceso->proveedor_id,
                'monto' => $proceso->monto,
                'objeto' => $proceso->objeto,
            ],
            'modalidades' => $this->modalidadesActivas(),
            'ccostos' => $this->ccostosDisponibles(),
            'proveedores' => $this->proveedoresActivos(),
        ]);
    }

    public function update(ActualizarProcesoAdquisicionRequest $request, ProcesoAdquisicion $proceso, ProcesoAdquisicionService $servicio): RedirectResponse
    {
        Gate::authorize('update', $proceso);

        try {
            $servicio->actualizar($proceso, $request->validated());
        } catch (ProcesoAdquisicionException $e) {
            return back()->withErrors(['modalidad_id' => $e->getMessage()]);
        }

        return to_route('adquisiciones.procesos.show', $proceso);
    }

    /**
     * @return Collection<int, array{id: int, codigo: string, nombre: string}>
     */
    private function modalidadesActivas(): Collection
    {
        return ModalidadAdquisicion::where('activo', true)->get()
            ->map(fn (ModalidadAdquisicion $modalidad) => [
                'id' => $modalidad->id,
                'codigo' => $modalidad->codigo,
                'nombre' => $modalidad->nombre,
            ]);
    }

    /**
     * @return Collection<int, array{id: int, codigo: string, nombre: string}>
     */
    private function ccostosDisponibles(): Collection
    {
        return Ccosto::all()->map(fn (Ccosto $ccosto) => [
            'id' => $ccosto->id,
            'codigo' => $ccosto->codigo,
            'nombre' => $ccosto->nombre,
        ]);
    }

    /**
     * @return Collection<int, array{id: int, nombre: string, rutproveedor: string}>
     */
    private function proveedoresActivos(): Collection
    {
        return Proveedor::activos()->get()->map(fn (Proveedor $proveedor) => [
            'id' => $proveedor->id,
            'nombre' => $proveedor->nombre,
            'rutproveedor' => $proveedor->rutproveedor,
        ]);
    }
}
