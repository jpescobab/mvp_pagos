<?php

namespace App\Enums\PagoProveedores;

/**
 * Instancias secuenciales de revisión de un pago dentro de un Egreso.
 * El caso pasa por Finanzas y luego por Zonal antes del registro CGU.
 */
enum InstanciaRevision: string
{
    case Finanzas = 'finanzas';
    case Zonal = 'zonal';

    public function label(): string
    {
        return match ($this) {
            self::Finanzas => 'Jefe de Finanzas',
            self::Zonal => 'Administrador Zonal',
        };
    }

    /**
     * Estado del workflow del caso que corresponde a esta instancia.
     */
    public function estado(): string
    {
        return match ($this) {
            self::Finanzas => 'en_revision_finanzas',
            self::Zonal => 'en_revision_zonal',
        };
    }

    /**
     * Permiso requerido para operar esta instancia.
     */
    public function permiso(): string
    {
        return match ($this) {
            self::Finanzas => 'pago_proveedores.revisar_finanzas',
            self::Zonal => 'pago_proveedores.revisar_zonal',
        };
    }

    /**
     * Transición que aprueba el pago en esta instancia (lo pasa a la siguiente).
     */
    public function transicionAprobar(): string
    {
        return match ($this) {
            self::Finanzas => 'aprobar_finanzas',
            self::Zonal => 'aprobar_zonal',
        };
    }

    /**
     * Transición que devuelve el pago a la etapa anterior.
     */
    public function transicionDevolver(): string
    {
        return match ($this) {
            self::Finanzas => 'observar_finanzas',
            self::Zonal => 'devolver_a_finanzas',
        };
    }

    /**
     * Transición que rechaza el pago en esta instancia.
     */
    public function transicionRechazar(): string
    {
        return match ($this) {
            self::Finanzas => 'rechazar_finanzas',
            self::Zonal => 'rechazar_zonal',
        };
    }

    /**
     * Resuelve la instancia activa a partir del código de estado del caso.
     */
    public static function desdeEstado(string $codigoEstado): ?self
    {
        return match ($codigoEstado) {
            'en_revision_finanzas' => self::Finanzas,
            'en_revision_zonal' => self::Zonal,
            default => null,
        };
    }

    /**
     * Códigos de transición que solo SHALL ejecutarse desde Revisión de Pagos
     * (vía RevisionEgresoService), nunca desde el endpoint genérico de
     * transiciones de un caso.
     *
     * @return list<string>
     */
    public static function codigosTransicionGobernados(): array
    {
        $codigos = [];

        foreach (self::cases() as $instancia) {
            $codigos[] = $instancia->transicionAprobar();
            $codigos[] = $instancia->transicionDevolver();
            $codigos[] = $instancia->transicionRechazar();
        }

        return $codigos;
    }
}
