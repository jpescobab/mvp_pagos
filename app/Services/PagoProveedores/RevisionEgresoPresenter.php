<?php

namespace App\Services\PagoProveedores;

use App\Enums\PagoProveedores\InstanciaRevision;
use App\Models\CasoPagoProveedor;
use App\Models\Documento;
use App\Models\EgresoCgu;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;

/**
 * Construye el payload de la pantalla de Revisión de Pagos a partir del estado
 * derivado del Egreso y de sus casos. No cambia estado; solo presenta.
 */
class RevisionEgresoPresenter
{
    /**
     * Estados del workflow del caso que corresponden a una instancia de revisión.
     *
     * @var list<string>
     */
    private const ESTADOS_EN_REVISION = ['en_revision_finanzas', 'en_revision_zonal'];

    public function __construct(
        private readonly RevisionEgresoService $revision,
        private readonly ValidacionDocumentoInstanciaService $validaciones,
    ) {}

    /**
     * Todos los egresos en revisión visibles para el usuario, con su detalle
     * completo (pagos + documentos) para alimentar el workbench de una sola
     * pantalla.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function listadoEnRevision(User $user): Collection
    {
        return EgresoCgu::query()
            ->whereHas(
                'items.caso.proceso.estadoActual',
                fn ($query) => $query->whereIn('codigo', self::ESTADOS_EN_REVISION),
            )
            ->with('cfinanciero')
            ->orderByDesc('id')
            ->get()
            ->filter(fn (EgresoCgu $egreso) => Gate::forUser($user)->allows('revisar', $egreso))
            ->map(fn (EgresoCgu $egreso) => $this->detalle($egreso, $user))
            ->values();
    }

    /**
     * Detalle completo del egreso para la pantalla de revisión.
     *
     * @return array<string, mixed>
     */
    public function detalle(EgresoCgu $egreso, User $user): array
    {
        $casos = $this->revision->casos($egreso);
        $instancia = $this->revision->instanciaActiva($egreso);
        $puedeOperar = $this->puedeOperar($egreso, $instancia, $user);

        $pagos = $casos->map(fn (CasoPagoProveedor $caso) => $this->pago($caso, $egreso, $user))->all();

        return [
            'id' => $egreso->id,
            'numero_egreso' => $egreso->numero_egreso,
            'periodo' => $egreso->periodo,
            'observaciones' => $egreso->observaciones,
            'monto_total' => (float) ($egreso->monto_total ?? $casos->sum(fn (CasoPagoProveedor $c) => (float) $c->monto)),
            'cantidad_pagos' => $casos->count(),
            'proveedores' => $casos->map(fn (CasoPagoProveedor $c) => data_get($c, 'proveedor.nombre') ?? $c->rut_proveedor)
                ->filter()->unique()->values()->all(),
            'estado' => $this->revision->estadoDerivado($egreso),
            'instancia_activa' => $instancia?->value,
            'instancia_label' => $instancia?->label(),
            'puede_operar' => $puedeOperar,
            'listo_para_avanzar' => $instancia !== null
                && $casos->isNotEmpty()
                && $casos->every(fn (CasoPagoProveedor $c) => $this->revision->pagoListoParaAprobar($c)),
            'pagos' => $pagos,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function pago(CasoPagoProveedor $caso, EgresoCgu $egreso, User $user): array
    {
        $instancia = $this->revision->instanciaDelCaso($caso);
        $totales = $this->revision->totales($caso);
        $documentos = $this->validaciones->documentosDelCaso($caso);

        return [
            'id' => $caso->id,
            'sgf_id' => $caso->sgf_id,
            'proveedor' => data_get($caso, 'proveedor.nombre') ?? $caso->rut_proveedor,
            'rut' => $caso->rut_proveedor,
            'folio' => $caso->folio_egreso ?? $caso->numero,
            'monto' => (float) $caso->monto,
            'estado' => $caso->proceso?->estadoActual->codigo,
            'estado_label' => $caso->proceso?->estadoActual->nombre,
            'instancia' => $instancia?->value,
            'puede_operar' => $this->puedeOperar($egreso, $instancia, $user),
            'totales' => [
                'factura' => $totales['factura'],
                'recepcion' => $totales['recepcion'],
                'monto' => $totales['monto'],
                'coinciden' => $totales['coinciden'],
                'verificados' => $instancia !== null && $this->revision->totalesVerificados($caso, $instancia),
            ],
            'listo_para_aprobar' => $this->revision->pagoListoParaAprobar($caso),
            'jurisdiccion_determinable' => $instancia !== InstanciaRevision::Finanzas || $caso->cfinancieroId() !== null,
            'documentos' => $documentos->map(fn (Documento $d) => $this->documento($d, $instancia))->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function documento(Documento $documento, ?InstanciaRevision $instancia): array
    {
        $estado = $instancia !== null
            ? $this->validaciones->estadoVigente($documento, $instancia)
            : $documento->estadoVigente();

        $ultima = $instancia !== null
            ? $documento->validaciones->filter(fn ($v) => $v->instancia === $instancia)->sortByDesc('id')->first()
            : $documento->validaciones->sortByDesc('id')->first();

        return [
            'id' => $documento->id,
            'titulo' => $documento->titulo,
            'tipo' => $documento->tipoDocumento?->nombre,
            'tipo_codigo' => $documento->tipoDocumento?->codigo,
            'estado' => $estado,
            'observacion' => $ultima?->observacion,
        ];
    }

    private function puedeOperar(EgresoCgu $egreso, ?InstanciaRevision $instancia, User $user): bool
    {
        if ($instancia === null) {
            return false;
        }

        return match ($instancia) {
            InstanciaRevision::Finanzas => Gate::forUser($user)->allows('revisarFinanzas', $egreso),
            InstanciaRevision::Zonal => Gate::forUser($user)->allows('revisarZonal', $egreso),
        };
    }
}
