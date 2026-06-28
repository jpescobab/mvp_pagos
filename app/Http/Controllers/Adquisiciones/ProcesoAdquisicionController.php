<?php

namespace App\Http\Controllers\Adquisiciones;

use App\Exceptions\ProcesoAdquisicionException;
use App\Http\Controllers\Controller;
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

        $modalidades = ModalidadAdquisicion::where('activo', true)->get()
            ->map(fn (ModalidadAdquisicion $modalidad) => [
                'id' => $modalidad->id,
                'codigo' => $modalidad->codigo,
                'nombre' => $modalidad->nombre,
            ]);

        $ccostos = Ccosto::all()->map(fn (Ccosto $ccosto) => [
            'id' => $ccosto->id,
            'codigo' => $ccosto->codigo,
            'nombre' => $ccosto->nombre,
        ]);

        $proveedores = Proveedor::all()->map(fn (Proveedor $proveedor) => [
            'id' => $proveedor->id,
            'nombre' => $proveedor->nombre,
            'rutproveedor' => $proveedor->rutproveedor,
        ]);

        return Inertia::render('adquisiciones/procesos/crear', [
            'modalidades' => $modalidades,
            'ccostos' => $ccostos,
            'proveedores' => $proveedores,
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
}
