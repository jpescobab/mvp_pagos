<?php

namespace App\Services\PagoProveedores;

use App\Enums\PagoProveedores\InstanciaRevision;
use App\Models\CasoPagoProveedor;
use App\Models\EgresoCgu;
use App\Models\RevisionPagoInstancia;
use App\Models\User;
use App\Services\Workflow\TransicionWorkflowService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Orquesta la revisión en dos instancias de los pagos de un Egreso. El Egreso
 * es el contenedor de trabajo; su estado de revisión y su instancia activa se
 * DERIVAN de los estados de sus casos. Todo cambio de estado de un caso se
 * ejecuta exclusivamente a través de TransicionWorkflowService.
 */
class RevisionEgresoService
{
    public function __construct(
        private readonly TransicionWorkflowService $transicionWorkflow,
        private readonly ValidacionDocumentoInstanciaService $validacionDocumentos,
    ) {}

    /**
     * Casos del egreso con su proceso y estado actual cargados.
     *
     * @return Collection<int, CasoPagoProveedor>
     */
    public function casos(EgresoCgu $egreso): Collection
    {
        return $egreso->items()
            ->with(['caso.proceso.estadoActual', 'caso.revisionesInstancia'])
            ->get()
            ->map(fn ($item) => $item->caso)
            ->filter()
            ->values();
    }

    /**
     * Instancia activa del egreso: la instancia común a los casos que están en
     * revisión. Null si los casos están en instancias distintas (en tránsito).
     */
    public function instanciaActiva(EgresoCgu $egreso): ?InstanciaRevision
    {
        $instancias = $this->casos($egreso)
            ->map(fn (CasoPagoProveedor $caso) => $this->instanciaDelCaso($caso))
            ->filter()
            ->unique();

        return $instancias->count() === 1 ? $instancias->first() : null;
    }

    public function instanciaDelCaso(CasoPagoProveedor $caso): ?InstanciaRevision
    {
        return InstanciaRevision::desdeEstado($caso->proceso?->estadoActual->codigo ?? '');
    }

    /**
     * Estado de revisión derivado del egreso (para mostrar, no se persiste).
     */
    public function estadoDerivado(EgresoCgu $egreso): string
    {
        $codigos = $this->casos($egreso)
            ->map(fn (CasoPagoProveedor $caso) => $caso->proceso?->estadoActual->codigo)
            ->filter()
            ->values();

        if ($codigos->isEmpty()) {
            return 'sin_pagos';
        }

        if ($codigos->contains('rechazada') || $codigos->contains('anulada')) {
            return 'rechazado';
        }

        $enRevision = ['recibida_finanzas', 'en_revision_finanzas', 'en_revision_zonal', 'observada', 'subsanada', 'importada_desde_sgf'];

        if ($codigos->every(fn (string $codigo) => ! in_array($codigo, $enRevision, true))) {
            return 'aprobado';
        }

        if ($codigos->every(fn (string $codigo) => $codigo === 'en_revision_finanzas')) {
            return 'en_revision_finanzas';
        }

        if ($codigos->every(fn (string $codigo) => $codigo === 'en_revision_zonal')) {
            return 'en_revision_zonal';
        }

        return 'en_transito';
    }

    /**
     * Totales del pago: factura vs recepción/OC vs monto a pagar.
     *
     * @return array{factura: float, recepcion: float, monto: float, coinciden: bool}
     */
    public function totales(CasoPagoProveedor $caso): array
    {
        $monto = (float) $caso->monto;
        $factura = (float) ($caso->facturas()->latest('id')->value('monto') ?? $monto);
        $recepcion = (float) ($caso->registrosContablesCgu()->latest('id')->value('monto') ?? $monto);

        return [
            'factura' => $factura,
            'recepcion' => $recepcion,
            'monto' => $monto,
            'coinciden' => $factura === $monto && $recepcion === $monto,
        ];
    }

    public function totalesVerificados(CasoPagoProveedor $caso, InstanciaRevision $instancia): bool
    {
        return (bool) $caso->revisionesInstancia
            ->first(fn (RevisionPagoInstancia $r) => $r->instancia === $instancia)
            ?->totales_verificados;
    }

    /**
     * Marca (o desmarca) los totales del pago como verificados en la instancia.
     */
    public function verificarTotales(CasoPagoProveedor $caso, InstanciaRevision $instancia, User $user, bool $verificado = true): RevisionPagoInstancia
    {
        $revision = RevisionPagoInstancia::updateOrCreate(
            ['caso_pago_proveedor_id' => $caso->id, 'instancia' => $instancia],
            [
                'totales_verificados' => $verificado,
                'verificado_por' => $verificado ? $user->id : null,
                'verificado_en' => $verificado ? now() : null,
            ],
        );

        $caso->load('revisionesInstancia');

        return $revision;
    }

    /**
     * ¿El pago está listo para aprobar en su instancia activa? Requiere todos
     * los documentos aprobados en esa instancia y los totales verificados.
     */
    public function pagoListoParaAprobar(CasoPagoProveedor $caso): bool
    {
        $instancia = $this->instanciaDelCaso($caso);

        if ($instancia === null) {
            return false;
        }

        return $this->validacionDocumentos->todosAprobados($caso, $instancia)
            && $this->totalesVerificados($caso, $instancia);
    }

    /**
     * Aprueba un pago en su instancia activa, avanzándolo a la instancia
     * siguiente (o a lista_para_registro_cgu si es la Zonal).
     */
    public function aprobarPago(CasoPagoProveedor $caso, User $user): void
    {
        $instancia = $this->exigirInstancia($caso);

        if (! $this->pagoListoParaAprobar($caso)) {
            throw new RuntimeException('El pago no está listo para aprobar: faltan documentos aprobados o verificar totales.');
        }

        $this->transicionWorkflow->execute(
            $caso->proceso,
            $instancia->transicionAprobar(),
            metadata: ['instancia' => $instancia->value],
            user: $user,
        );
    }

    public function rechazarPago(CasoPagoProveedor $caso, string $comentario, User $user): void
    {
        $instancia = $this->exigirInstancia($caso);

        $this->transicionWorkflow->execute(
            $caso->proceso,
            $instancia->transicionRechazar(),
            comentario: $comentario,
            metadata: ['instancia' => $instancia->value],
            user: $user,
        );
    }

    public function devolverPago(CasoPagoProveedor $caso, string $comentario, User $user): void
    {
        $instancia = $this->exigirInstancia($caso);

        $this->transicionWorkflow->execute(
            $caso->proceso,
            $instancia->transicionDevolver(),
            comentario: $comentario,
            metadata: ['instancia' => $instancia->value],
            user: $user,
        );
    }

    /**
     * Aprueba el egreso completo: exige que todos sus pagos estén en la misma
     * instancia y listos, y dispara la aprobación de cada uno en una transacción.
     */
    public function aprobarEgreso(EgresoCgu $egreso, User $user): void
    {
        $instancia = $this->instanciaActiva($egreso);

        if ($instancia === null) {
            throw new RuntimeException('El egreso no tiene una instancia de revisión activa única.');
        }

        $casos = $this->casos($egreso);

        foreach ($casos as $caso) {
            if (! $this->pagoListoParaAprobar($caso)) {
                throw new RuntimeException("El pago {$caso->sgf_id} no está listo para aprobar.");
            }
        }

        DB::transaction(function () use ($casos, $user): void {
            foreach ($casos as $caso) {
                $this->aprobarPago($caso, $user);
            }
        });
    }

    public function devolverEgreso(EgresoCgu $egreso, string $comentario, User $user): void
    {
        $instancia = $this->instanciaActiva($egreso);

        if ($instancia === null || $instancia === InstanciaRevision::Finanzas) {
            throw new RuntimeException('El egreso no puede devolverse desde su instancia actual.');
        }

        DB::transaction(function () use ($egreso, $comentario, $user): void {
            foreach ($this->casos($egreso) as $caso) {
                $this->devolverPago($caso, $comentario, $user);
            }
        });
    }

    private function exigirInstancia(CasoPagoProveedor $caso): InstanciaRevision
    {
        $instancia = $this->instanciaDelCaso($caso);

        if ($instancia === null) {
            throw new RuntimeException('El pago no está en una instancia de revisión.');
        }

        return $instancia;
    }
}
