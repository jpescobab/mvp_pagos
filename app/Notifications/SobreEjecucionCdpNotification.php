<?php

namespace App\Notifications;

use App\Models\CasoPagoProveedor;
use App\Models\Presupuesto\CertificadoDisponibilidadPresupuestaria;
use Illuminate\Notifications\Notification;

class SobreEjecucionCdpNotification extends Notification
{
    public function __construct(
        private readonly CertificadoDisponibilidadPresupuestaria $cdp,
        private readonly CasoPagoProveedor $caso,
        private readonly float $montoEjecutado,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'cdp_id' => $this->cdp->id,
            'caso_pago_proveedor_id' => $this->caso->id,
            'monto_cdp' => (float) $this->cdp->monto,
            'monto_ejecutado' => $this->montoEjecutado,
            'descripcion' => "Sobre-ejecución del CDP {$this->cdp->folio}: ejecutado ".number_format($this->montoEjecutado, 0, ',', '.').' sobre '.number_format((float) $this->cdp->monto, 0, ',', '.'),
            'url' => route('presupuesto.cdps.show', $this->cdp),
        ];
    }
}
