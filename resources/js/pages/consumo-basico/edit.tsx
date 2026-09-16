import { Head, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import consumoBasico from '@/routes/consumo-basico';
import casos from '@/routes/pago-proveedores/casos';
import type { ConsumoBasico } from '@/types/consumo-basico';

type PageProps = {
    consumoBasico: ConsumoBasico;
};

export default function ConsumoBasicoEditar() {
    const { consumoBasico: detalle } = usePage<PageProps>().props;

    const [numeroDocumento, setNumeroDocumento] = useState(
        detalle.numero_documento,
    );
    const [numeroMedidor, setNumeroMedidor] = useState(detalle.numero_medidor);
    const [fechaInicioLectura, setFechaInicioLectura] = useState(
        detalle.fecha_inicio_lectura,
    );
    const [fechaFinLectura, setFechaFinLectura] = useState(
        detalle.fecha_fin_lectura,
    );
    const [fechaEmision, setFechaEmision] = useState(detalle.fecha_emision);
    const [fechaVencimiento, setFechaVencimiento] = useState(
        detalle.fecha_vencimiento,
    );
    const [consumo, setConsumo] = useState(detalle.consumo ?? '');
    const [tarifa, setTarifa] = useState(detalle.tarifa ?? '');
    const [lecturaEstimada, setLecturaEstimada] = useState(
        detalle.lectura_estimada,
    );
    const [montoNeto, setMontoNeto] = useState(detalle.monto_neto);
    const [iva, setIva] = useState(detalle.iva);
    const [montoExento, setMontoExento] = useState(detalle.monto_exento);
    const [saldoAnterior, setSaldoAnterior] = useState(detalle.saldo_anterior);
    const [montoTotal, setMontoTotal] = useState(detalle.monto_total);

    const [errors, setErrors] = useState<Record<string, string>>({});
    const [procesando, setProcesando] = useState(false);

    function enviar() {
        setProcesando(true);
        setErrors({});

        router.put(
            consumoBasico.update(detalle.id).url,
            {
                numero_documento: numeroDocumento,
                numero_medidor: numeroMedidor,
                fecha_inicio_lectura: fechaInicioLectura,
                fecha_fin_lectura: fechaFinLectura,
                fecha_emision: fechaEmision,
                fecha_vencimiento: fechaVencimiento,
                consumo: consumo || null,
                tarifa: tarifa || null,
                lectura_estimada: lecturaEstimada,
                monto_neto: montoNeto,
                iva: iva,
                monto_exento: montoExento || 0,
                saldo_anterior: saldoAnterior || 0,
                monto_total: montoTotal,
            },
            {
                onError: (errores) =>
                    setErrors(errores as Record<string, string>),
                onFinish: () => setProcesando(false),
            },
        );
    }

    return (
        <>
            <Head title={`Editar consumo — ${detalle.numero_documento}`} />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <h1 className="text-xl font-semibold tracking-tight">
                    Editar detalle de consumo
                </h1>

                <div className="grid max-w-2xl gap-4 rounded-xl border p-4">
                    <div className="grid grid-cols-2 gap-4">
                        <div className="grid gap-2">
                            <Label htmlFor="numero_documento">
                                N.º de documento
                                <span className="text-destructive">*</span>
                            </Label>
                            <Input
                                id="numero_documento"
                                value={numeroDocumento}
                                onChange={(e) =>
                                    setNumeroDocumento(e.target.value)
                                }
                            />
                            {errors.numero_documento && (
                                <p className="text-sm text-destructive">
                                    {errors.numero_documento}
                                </p>
                            )}
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="numero_medidor">
                                N.º de medidor
                                <span className="text-destructive">*</span>
                            </Label>
                            <Input
                                id="numero_medidor"
                                value={numeroMedidor}
                                onChange={(e) =>
                                    setNumeroMedidor(e.target.value)
                                }
                            />
                            {errors.numero_medidor && (
                                <p className="text-sm text-destructive">
                                    {errors.numero_medidor}
                                </p>
                            )}
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="fecha_inicio_lectura">
                                Inicio de lectura
                                <span className="text-destructive">*</span>
                            </Label>
                            <Input
                                id="fecha_inicio_lectura"
                                type="date"
                                value={fechaInicioLectura}
                                onChange={(e) =>
                                    setFechaInicioLectura(e.target.value)
                                }
                            />
                            {errors.fecha_inicio_lectura && (
                                <p className="text-sm text-destructive">
                                    {errors.fecha_inicio_lectura}
                                </p>
                            )}
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="fecha_fin_lectura">
                                Fin de lectura
                                <span className="text-destructive">*</span>
                            </Label>
                            <Input
                                id="fecha_fin_lectura"
                                type="date"
                                value={fechaFinLectura}
                                onChange={(e) =>
                                    setFechaFinLectura(e.target.value)
                                }
                            />
                            {errors.fecha_fin_lectura && (
                                <p className="text-sm text-destructive">
                                    {errors.fecha_fin_lectura}
                                </p>
                            )}
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="fecha_emision">
                                Fecha de emisión
                                <span className="text-destructive">*</span>
                            </Label>
                            <Input
                                id="fecha_emision"
                                type="date"
                                value={fechaEmision}
                                onChange={(e) =>
                                    setFechaEmision(e.target.value)
                                }
                            />
                            {errors.fecha_emision && (
                                <p className="text-sm text-destructive">
                                    {errors.fecha_emision}
                                </p>
                            )}
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="fecha_vencimiento">
                                Fecha de vencimiento
                                <span className="text-destructive">*</span>
                            </Label>
                            <Input
                                id="fecha_vencimiento"
                                type="date"
                                value={fechaVencimiento}
                                onChange={(e) =>
                                    setFechaVencimiento(e.target.value)
                                }
                            />
                            {errors.fecha_vencimiento && (
                                <p className="text-sm text-destructive">
                                    {errors.fecha_vencimiento}
                                </p>
                            )}
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="consumo">Consumo</Label>
                            <Input
                                id="consumo"
                                type="number"
                                step="0.01"
                                value={consumo}
                                onChange={(e) => setConsumo(e.target.value)}
                            />
                            {errors.consumo && (
                                <p className="text-sm text-destructive">
                                    {errors.consumo}
                                </p>
                            )}
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="tarifa">Tarifa</Label>
                            <Input
                                id="tarifa"
                                value={tarifa}
                                onChange={(e) => setTarifa(e.target.value)}
                            />
                            {errors.tarifa && (
                                <p className="text-sm text-destructive">
                                    {errors.tarifa}
                                </p>
                            )}
                        </div>
                    </div>

                    <div className="flex items-center gap-2">
                        <Switch
                            id="lectura_estimada"
                            checked={lecturaEstimada}
                            onCheckedChange={setLecturaEstimada}
                        />
                        <Label htmlFor="lectura_estimada">
                            Lectura estimada
                        </Label>
                    </div>
                </div>

                <div className="grid max-w-2xl gap-4 rounded-xl border p-4">
                    <div className="grid grid-cols-2 gap-4">
                        <div className="grid gap-2">
                            <Label htmlFor="monto_neto">
                                Monto neto
                                <span className="text-destructive">*</span>
                            </Label>
                            <Input
                                id="monto_neto"
                                type="number"
                                step="0.01"
                                value={montoNeto}
                                onChange={(e) => setMontoNeto(e.target.value)}
                            />
                            {errors.monto_neto && (
                                <p className="text-sm text-destructive">
                                    {errors.monto_neto}
                                </p>
                            )}
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="iva">
                                IVA
                                <span className="text-destructive">*</span>
                            </Label>
                            <Input
                                id="iva"
                                type="number"
                                step="0.01"
                                value={iva}
                                onChange={(e) => setIva(e.target.value)}
                            />
                            {errors.iva && (
                                <p className="text-sm text-destructive">
                                    {errors.iva}
                                </p>
                            )}
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="monto_exento">Monto exento</Label>
                            <Input
                                id="monto_exento"
                                type="number"
                                step="0.01"
                                value={montoExento}
                                onChange={(e) => setMontoExento(e.target.value)}
                            />
                            {errors.monto_exento && (
                                <p className="text-sm text-destructive">
                                    {errors.monto_exento}
                                </p>
                            )}
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="saldo_anterior">
                                Saldo anterior
                            </Label>
                            <Input
                                id="saldo_anterior"
                                type="number"
                                step="0.01"
                                value={saldoAnterior}
                                onChange={(e) =>
                                    setSaldoAnterior(e.target.value)
                                }
                            />
                            {errors.saldo_anterior && (
                                <p className="text-sm text-destructive">
                                    {errors.saldo_anterior}
                                </p>
                            )}
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="monto_total">
                                Monto total
                                <span className="text-destructive">*</span>
                            </Label>
                            <Input
                                id="monto_total"
                                type="number"
                                step="0.01"
                                value={montoTotal}
                                onChange={(e) => setMontoTotal(e.target.value)}
                            />
                            {errors.monto_total && (
                                <p className="text-sm text-destructive">
                                    {errors.monto_total}
                                </p>
                            )}
                        </div>
                    </div>
                </div>

                <div className="flex gap-2">
                    <Button disabled={procesando} onClick={enviar}>
                        Guardar cambios
                    </Button>
                    <Button
                        variant="outline"
                        disabled={procesando}
                        onClick={() =>
                            router.get(consumoBasico.show(detalle.id).url)
                        }
                    >
                        Cancelar
                    </Button>
                </div>
            </div>
        </>
    );
}

ConsumoBasicoEditar.layout = {
    breadcrumbs: [
        { title: 'Casos de pago', href: casos.index() },
        { title: 'Detalle de consumo', href: '#' },
        { title: 'Editar', href: '#' },
    ],
};
