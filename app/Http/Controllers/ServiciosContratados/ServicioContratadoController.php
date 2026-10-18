<?php

namespace App\Http\Controllers\ServiciosContratados;

use App\Exceptions\ServicioContratadoException;
use App\Http\Controllers\Controller;
use App\Http\Requests\ServiciosContratados\StoreServicioContratadoRequest;
use App\Http\Requests\ServiciosContratados\UpdateServicioContratadoRequest;
use App\Http\Resources\ServiciosContratados\ServicioContratadoResource;
use App\Models\Ccosto;
use App\Models\Contrato;
use App\Models\Funcionario;
use App\Models\ServicioContratado;
use App\Services\ServiciosContratados\PeriodosServicioContratadoService;
use App\Services\ServiciosContratados\ServicioContratadoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ServicioContratadoController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', ServicioContratado::class);

        $q = $request->string('q')->toString();
        $estado = $request->string('estado')->toString();

        $servicios = ServicioContratado::query()
            ->with(['contrato.proveedor', 'ccosto', 'administrador'])
            ->when($q !== '', fn ($query) => $query->where(
                fn ($sub) => $sub
                    ->where('nombre', 'like', "%{$q}%")
                    ->orWhereHas('contrato', fn ($c) => $c->where('codigo', 'like', "%{$q}%")
                        ->orWhereHas('proveedor', fn ($p) => $p->where('nombre', 'like', "%{$q}%"))),
            ))
            ->when($estado !== '', fn ($query) => $query->conEstado($estado))
            ->orderBy('fecha_fin')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('servicios-contratados/index', [
            'servicios' => ServicioContratadoResource::collection($servicios),
            'q' => $q !== '' ? $q : null,
            'estado' => $estado !== '' ? $estado : null,
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', ServicioContratado::class);

        return Inertia::render('servicios-contratados/create', $this->opcionesFormulario());
    }

    public function store(StoreServicioContratadoRequest $request, ServicioContratadoService $servicio): RedirectResponse
    {
        Gate::authorize('create', ServicioContratado::class);

        try {
            $creado = $servicio->registrar($request->validated());
        } catch (ServicioContratadoException $e) {
            return back()->withErrors([$e->campo() => $e->getMessage()]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Servicio contratado registrado.']);

        return to_route('servicios-contratados.show', $creado);
    }

    public function show(ServicioContratado $servicioContratado, PeriodosServicioContratadoService $periodos): Response
    {
        Gate::authorize('view', $servicioContratado);

        $servicioContratado->load(['contrato.proveedor', 'ccosto', 'administrador']);

        return Inertia::render('servicios-contratados/show', [
            'servicio' => new ServicioContratadoResource($servicioContratado),
            'periodos' => $periodos->periodos($servicioContratado),
        ]);
    }

    public function edit(ServicioContratado $servicioContratado): Response
    {
        Gate::authorize('update', $servicioContratado);

        $servicioContratado->load(['contrato.proveedor', 'ccosto', 'administrador']);

        return Inertia::render('servicios-contratados/edit', [
            'servicio' => new ServicioContratadoResource($servicioContratado),
            ...$this->opcionesFormulario(),
        ]);
    }

    public function update(UpdateServicioContratadoRequest $request, ServicioContratado $servicioContratado, ServicioContratadoService $servicio): RedirectResponse
    {
        Gate::authorize('update', $servicioContratado);

        try {
            $servicio->actualizar($servicioContratado, $request->validated());
        } catch (ServicioContratadoException $e) {
            return back()->withErrors([$e->campo() => $e->getMessage()]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Servicio contratado actualizado.']);

        return to_route('servicios-contratados.show', $servicioContratado);
    }

    public function terminar(ServicioContratado $servicioContratado, ServicioContratadoService $servicio): RedirectResponse
    {
        Gate::authorize('terminar', $servicioContratado);

        try {
            $servicio->terminar($servicioContratado);
        } catch (ServicioContratadoException $e) {
            return back()->withErrors([$e->campo() => $e->getMessage()]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Servicio marcado como terminado.']);

        return to_route('servicios-contratados.show', $servicioContratado);
    }

    /**
     * @return array<string, mixed>
     */
    private function opcionesFormulario(): array
    {
        return [
            'categorias' => ServicioContratado::CATEGORIAS,
            'contratos' => Contrato::query()
                ->whereHas('proceso.estadoActual', fn ($q) => $q->where('codigo', 'aprobado'))
                ->with('proveedor:id,nombre')
                ->orderBy('codigo')
                ->get()
                ->map(fn (Contrato $c) => [
                    'id' => $c->id,
                    'codigo' => $c->codigo,
                    'referencia' => $c->referencia,
                    'proveedor' => $c->proveedor?->nombre,
                    'fecha_inicio_vigencia' => $c->fecha_inicio_vigencia->format('Y-m-d'),
                    'fecha_fin_vigencia' => $c->fecha_fin_vigencia->format('Y-m-d'),
                ]),
            'ccostos' => Ccosto::all(['id', 'codigo', 'nombre']),
            'funcionarios' => Funcionario::where('activo', true)->orderBy('nombre')->get(['id', 'nombre']),
        ];
    }
}
