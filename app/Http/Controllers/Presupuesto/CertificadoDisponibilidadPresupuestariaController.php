<?php

namespace App\Http\Controllers\Presupuesto;

use App\Exceptions\CertificadoDisponibilidadPresupuestariaException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Presupuesto\ActualizarCertificadoDisponibilidadRequest;
use App\Http\Requests\Presupuesto\CrearCertificadoDisponibilidadRequest;
use App\Http\Resources\Presupuesto\CertificadoDisponibilidadPresupuestariaResource;
use App\Models\Presupuesto\CertificadoDisponibilidadPresupuestaria;
use App\Models\Presupuesto\Presupuesto;
use App\Models\ProcesoAdquisicion;
use App\Services\Presupuesto\CrearBorradorCertificadoDisponibilidadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class CertificadoDisponibilidadPresupuestariaController extends Controller
{
    public function __construct(
        private readonly CrearBorradorCertificadoDisponibilidadService $servicio,
    ) {}

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', CertificadoDisponibilidadPresupuestaria::class);

        $q = $request->string('q')->toString() ?: null;

        $cdps = CertificadoDisponibilidadPresupuestaria::with(['cfinanciero', 'presupuesto.catalogo', 'proceso.estadoActual'])
            ->when($q, fn ($query) => $query->where('folio', 'like', "%{$q}%"))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('presupuesto/cdp/index', [
            'cdps' => CertificadoDisponibilidadPresupuestariaResource::collection($cdps),
            'q' => $q,
        ]);
    }

    public function show(CertificadoDisponibilidadPresupuestaria $cdp): Response
    {
        Gate::authorize('view', $cdp);

        $cdp->load([
            'cfinanciero',
            'presupuesto.catalogo',
            'procesoAdquisicion',
            'cdpOriginal',
            'firmadoPor',
            'proceso.estadoActual',
            'proceso.definicionWorkflow.transiciones',
            'proceso.vinculosDocumento.documento.tipoDocumento',
            'proceso.vinculosDocumento.documento.versiones',
        ]);

        return Inertia::render('presupuesto/cdp/show', [
            'cdp' => new CertificadoDisponibilidadPresupuestariaResource($cdp),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', CertificadoDisponibilidadPresupuestaria::class);

        return Inertia::render('presupuesto/cdp/create', [
            'lineasPresupuesto' => $this->lineasPresupuestoDisponibles(),
            'procesosAdquisicion' => $this->procesosAdquisicionDisponibles(),
        ]);
    }

    public function store(CrearCertificadoDisponibilidadRequest $request): RedirectResponse
    {
        Gate::authorize('create', CertificadoDisponibilidadPresupuestaria::class);

        $cdp = $this->servicio->crear($request->validated());

        return to_route('presupuesto.cdps.show', $cdp);
    }

    public function edit(CertificadoDisponibilidadPresupuestaria $cdp): Response
    {
        Gate::authorize('update', $cdp);

        return Inertia::render('presupuesto/cdp/edit', [
            'cdp' => new CertificadoDisponibilidadPresupuestariaResource($cdp),
            'lineasPresupuesto' => $this->lineasPresupuestoDisponibles(),
            'procesosAdquisicion' => $this->procesosAdquisicionDisponibles(),
        ]);
    }

    public function update(ActualizarCertificadoDisponibilidadRequest $request, CertificadoDisponibilidadPresupuestaria $cdp): RedirectResponse
    {
        Gate::authorize('update', $cdp);

        try {
            $this->servicio->actualizar($cdp, $request->validated());
        } catch (CertificadoDisponibilidadPresupuestariaException $e) {
            return back()->withErrors(['estado' => $e->getMessage()]);
        }

        return to_route('presupuesto.cdps.show', $cdp);
    }

    /**
     * @return Collection<int, array{id: int, codigo: string, etiqueta: non-falsy-string, denominacion: string, unidad_ejecutora: string, n_ue: string, subtitulo: string}>
     */
    private function lineasPresupuestoDisponibles(): Collection
    {
        return Presupuesto::with(['catalogo.item', 'cfinanciero'])->get()
            ->map(fn (Presupuesto $presupuesto) => [
                'id' => $presupuesto->id,
                'codigo' => $presupuesto->catalogo->codigo,
                'etiqueta' => "{$presupuesto->catalogo->codigo} — {$presupuesto->catalogo->nombre} ({$presupuesto->cfinanciero->nombre}, {$presupuesto->anio})",
                'denominacion' => $presupuesto->catalogo->nombre,
                'unidad_ejecutora' => $presupuesto->cfinanciero->nombre,
                'n_ue' => $presupuesto->cfinanciero->codigo,
                'subtitulo' => substr($presupuesto->catalogo->item->codigo, 0, 2),
            ]);
    }

    /**
     * @return Collection<int, array{id: int, codigo: string}>
     */
    private function procesosAdquisicionDisponibles(): Collection
    {
        return ProcesoAdquisicion::all(['id', 'codigo'])
            ->map(fn (ProcesoAdquisicion $proceso) => [
                'id' => $proceso->id,
                'codigo' => $proceso->codigo,
            ]);
    }
}
