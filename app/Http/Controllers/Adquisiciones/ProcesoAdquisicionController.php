<?php

namespace App\Http\Controllers\Adquisiciones;

use App\Exceptions\ProcesoAdquisicionException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Adquisiciones\ActualizarProcesoAdquisicionRequest;
use App\Http\Requests\Adquisiciones\CrearProcesoAdquisicionRequest;
use App\Http\Resources\Adquisiciones\ProcesoAdquisicionResource;
use App\Models\Ccosto;
use App\Models\ConjuntoRequisitosDocumentales;
use App\Models\Funcionario;
use App\Models\ProcesoAdquisicion;
use App\Models\Proveedor;
use App\Models\TipoDocumento;
use App\Services\Adquisiciones\ExportadorSolicitudCompraPdfService;
use App\Services\Adquisiciones\ProcesoAdquisicionService;
use App\Services\Documentos\ResolutorChecklistDocumentalProceso;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
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

    public function pdf(ProcesoAdquisicion $proceso, ExportadorSolicitudCompraPdfService $exportador): HttpResponse
    {
        Gate::authorize('view', $proceso);

        return response($exportador->generar($proceso), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$proceso->codigo.'.pdf"',
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', ProcesoAdquisicion::class);

        return Inertia::render('adquisiciones/procesos/crear', [
            'ccostos' => $this->ccostosDisponibles(),
            'funcionarios' => $this->funcionariosActivos(),
            'proveedores' => $this->proveedoresActivos(),
        ]);
    }

    public function store(CrearProcesoAdquisicionRequest $request, ProcesoAdquisicionService $servicio): RedirectResponse
    {
        Gate::authorize('create', ProcesoAdquisicion::class);

        try {
            $proceso = $servicio->crear($request->validated());
        } catch (ProcesoAdquisicionException $e) {
            return back()->withErrors([$e->campo() => $e->getMessage()]);
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
                'fecha_inicio' => $proceso->fecha_inicio,
                'nombre' => $proceso->nombre,
                'id_requerimiento' => $proceso->id_requerimiento,
                'ccosto_id' => $proceso->ccosto_id,
                'funcionario_requirente_id' => $proceso->funcionario_requirente_id,
                'proveedor_id' => $proceso->proveedor_id,
                'caracteristicas' => $proceso->caracteristicas,
                'motivo_contratacion' => $proceso->motivo_contratacion,
                'en_plan_compras' => $proceso->en_plan_compras,
                'id_pac' => $proceso->id_pac,
                'codigo_bip' => $proceso->codigo_bip,
                'convenio_marco' => $proceso->modalidad?->codigo === 'CONVENIO_MARCO',
                'moneda_compra' => $proceso->moneda_compra,
                'monto_estimado_solicitado' => $proceso->monto_estimado_solicitado,
                'fecha_paridad' => $proceso->fecha_paridad,
                'paridad' => $proceso->paridad,
                'monto_estimado' => $proceso->monto_estimado,
            ],
            'ccostos' => $this->ccostosDisponibles(),
            'funcionarios' => $this->funcionariosActivos(),
            'proveedores' => $this->proveedoresActivos(),
        ]);
    }

    public function update(ActualizarProcesoAdquisicionRequest $request, ProcesoAdquisicion $proceso, ProcesoAdquisicionService $servicio): RedirectResponse
    {
        Gate::authorize('update', $proceso);

        try {
            $servicio->actualizar($proceso, $request->validated());
        } catch (ProcesoAdquisicionException $e) {
            return back()->withErrors([$e->campo() => $e->getMessage()]);
        }

        return to_route('adquisiciones.procesos.show', $proceso);
    }

    /**
     * @return Collection<int, array{id: int, nombre: string, cargo: ?string, ccosto_id: int<0, max>|null}>
     */
    private function funcionariosActivos(): Collection
    {
        return Funcionario::where('activo', true)->get()
            ->map(fn (Funcionario $funcionario) => [
                'id' => $funcionario->id,
                'nombre' => $funcionario->nombre,
                'cargo' => $funcionario->cargo,
                'ccosto_id' => $funcionario->ccosto_id,
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
