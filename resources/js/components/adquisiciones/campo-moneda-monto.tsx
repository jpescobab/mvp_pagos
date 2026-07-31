import { useEffect, useState } from 'react';
import { CampoSoloLectura } from '@/components/adquisiciones/campo-solo-lectura';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { formatMonto } from '@/lib/format';
import procesos from '@/routes/adquisiciones/procesos';

export type Moneda = 'CLP' | 'UF' | 'USD';

const SIMBOLO_MONEDA: Record<Moneda, string> = {
    CLP: '$',
    UF: 'UF',
    USD: 'US$',
};

export function CampoMonedaMonto({
    moneda,
    montoSolicitado,
    fechaParidad,
    onChangeMoneda,
    onChangeMontoSolicitado,
    onChangeFechaParidad,
    errors,
}: {
    moneda: Moneda;
    montoSolicitado: string;
    fechaParidad: string;
    onChangeMoneda: (moneda: Moneda) => void;
    onChangeMontoSolicitado: (valor: string) => void;
    onChangeFechaParidad: (valor: string) => void;
    errors: Record<string, string>;
}) {
    // Paridad y monto estimado son de solo lectura: la paridad se resuelve
    // contra el indicador económico real vigente para la fecha elegida
    // (misma fuente que usa el servidor al guardar); el monto estimado es
    // siempre monto_estimado_solicitado × paridad. El servidor vuelve a
    // calcular ambos al guardar — esto es solo una previsualización.
    const [paridadPreview, setParidadPreview] = useState<{
        valor: number;
        error: string | null;
    }>({ valor: 1, error: null });

    useEffect(() => {
        if (moneda === 'CLP' || !fechaParidad) {
            return;
        }

        let cancelado = false;

        fetch(
            procesos.paridad.url({
                query: { moneda, fecha: fechaParidad },
            }),
        )
            .then(async (respuesta) => {
                if (cancelado) {
                    return;
                }

                if (!respuesta.ok) {
                    setParidadPreview({
                        valor: 1,
                        error: 'Sin valor registrado para esa fecha.',
                    });

                    return;
                }

                const datos = await respuesta.json();
                setParidadPreview({ valor: datos.valor, error: null });
            })
            .catch(() => {
                if (!cancelado) {
                    setParidadPreview({
                        valor: 1,
                        error: 'No se pudo consultar la paridad.',
                    });
                }
            });

        return () => {
            cancelado = true;
        };
    }, [moneda, fechaParidad]);

    const sinParidadAplicable = moneda === 'CLP' || !fechaParidad;
    const paridadEfectiva = sinParidadAplicable ? 1 : paridadPreview.valor;
    const paridadError = sinParidadAplicable ? null : paridadPreview.error;

    const montoSolicitadoNumero = parseFloat(montoSolicitado) || 0;
    const montoEstimadoPreview = Math.round(
        montoSolicitadoNumero * paridadEfectiva,
    );

    function cambiarMoneda(valor: Moneda) {
        onChangeMoneda(valor);

        if (valor !== 'CLP' && !fechaParidad) {
            onChangeFechaParidad(new Date().toISOString().slice(0, 10));
        }
    }

    return (
        <div className="grid gap-4">
            <div className="grid grid-cols-2 gap-4">
                <div className="grid gap-2">
                    <Label htmlFor="moneda_compra">
                        Moneda<span className="text-destructive">*</span>
                    </Label>
                    <Select
                        value={moneda}
                        onValueChange={(v) => cambiarMoneda(v as Moneda)}
                    >
                        <SelectTrigger id="moneda_compra" className="w-full">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="CLP">CLP</SelectItem>
                            <SelectItem value="UF">UF</SelectItem>
                            <SelectItem value="USD">USD</SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="monto_estimado_solicitado">
                        Monto estimado de la compra y/o contratación
                        <span className="text-destructive">*</span>
                    </Label>
                    <div className="relative">
                        <span className="pointer-events-none absolute top-1/2 left-3 -translate-y-1/2 text-sm font-semibold text-muted-foreground">
                            {SIMBOLO_MONEDA[moneda]}
                        </span>
                        <Input
                            id="monto_estimado_solicitado"
                            type="number"
                            min="0"
                            step="0.0001"
                            className="pl-9"
                            value={montoSolicitado}
                            onChange={(e) =>
                                onChangeMontoSolicitado(e.target.value)
                            }
                        />
                    </div>
                    {errors.monto_estimado_solicitado && (
                        <p className="text-sm text-destructive">
                            {errors.monto_estimado_solicitado}
                        </p>
                    )}
                </div>
            </div>

            {moneda !== 'CLP' && (
                <div className="grid grid-cols-2 gap-4 rounded-md border border-dashed border-primary bg-primary/5 p-4">
                    <div className="grid gap-2">
                        <Label htmlFor="fecha_paridad">Fecha de paridad</Label>
                        <Input
                            id="fecha_paridad"
                            type="date"
                            value={fechaParidad}
                            onChange={(e) =>
                                onChangeFechaParidad(e.target.value)
                            }
                        />
                        {errors.fecha_paridad && (
                            <p className="text-sm text-destructive">
                                {errors.fecha_paridad}
                            </p>
                        )}
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="paridad">Paridad</Label>
                        <CampoSoloLectura
                            id="paridad"
                            valor={paridadError ?? paridadEfectiva}
                        />
                    </div>
                </div>
            )}

            <div className="grid gap-2">
                <Label htmlFor="montoEstimadoTotal">Monto estimado (CLP)</Label>
                <CampoSoloLectura
                    id="montoEstimadoTotal"
                    valor={
                        <span className="font-medium">
                            {formatMonto(montoEstimadoPreview)}
                        </span>
                    }
                />
            </div>
        </div>
    );
}
