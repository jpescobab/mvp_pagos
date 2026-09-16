import { Head, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import consumoBasico from '@/routes/consumo-basico';
import casos from '@/routes/pago-proveedores/casos';
import type { ClienteMedidorSeleccionable } from '@/types/consumo-basico';
import type { Ccosto } from '@/types/maestros';
import type { CasoPagoProveedor } from '@/types/pago-proveedores';

const CREAR_NUEVO = 'crear-nuevo';

type PageProps = {
    caso: CasoPagoProveedor;
    ccostos: Pick<Ccosto, 'id' | 'codigo' | 'nombre'>[];
    clientesMedidoresProveedor: ClienteMedidorSeleccionable[];
};

export default function ConsumoBasicoCrear() {
    const { caso, ccostos, clientesMedidoresProveedor } =
        usePage<PageProps>().props;

    const [clienteMedidorId, setClienteMedidorId] = useState(
        clientesMedidoresProveedor.length > 0 ? '' : CREAR_NUEVO,
    );
    const [numeroCliente, setNumeroCliente] = useState('');
    const [ccostoId, setCcostoId] = useState('');
    const [tipoSuministro, setTipoSuministro] = useState('');
    const [direccionSuministro, setDireccionSuministro] = useState('');

    const [numeroDocumento, setNumeroDocumento] = useState('');
    const [numeroMedidor, setNumeroMedidor] = useState('');
    const [fechaInicioLectura, setFechaInicioLectura] = useState('');
    const [fechaFinLectura, setFechaFinLectura] = useState('');
    const [fechaEmision, setFechaEmision] = useState('');
    const [fechaVencimiento, setFechaVencimiento] = useState('');
    const [consumo, setConsumo] = useState('');
    const [tarifa, setTarifa] = useState('');
    const [lecturaEstimada, setLecturaEstimada] = useState(false);
    const [montoNeto, setMontoNeto] = useState('');
    const [iva, setIva] = useState('');
    const [montoExento, setMontoExento] = useState('0');
    const [saldoAnterior, setSaldoAnterior] = useState('0');
    const [montoTotal, setMontoTotal] = useState('');

    const [errors, setErrors] = useState<Record<string, string>>({});
    const [procesando, setProcesando] = useState(false);

    const creandoClienteMedidor = clienteMedidorId === CREAR_NUEVO;

    function enviar() {
        setProcesando(true);
        setErrors({});

        router.post(
            consumoBasico.store(caso.id).url,
            {
                cliente_medidor_id: creandoClienteMedidor
                    ? null
                    : Number(clienteMedidorId),
                cliente_medidor: creandoClienteMedidor
                    ? {
                          numero_cliente: numeroCliente,
                          ccosto_id: ccostoId ? Number(ccostoId) : null,
                          tipo_suministro: tipoSuministro,
                          direccion_suministro: direccionSuministro || null,
                      }
                    : null,
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
            <Head title="Completar detalle de consumo" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div>
                    <h1 className="text-xl font-semibold tracking-tight">
                        Completar detalle de consumo
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        Caso {caso.sgf_id} · {caso.proveedor.nombre ?? '—'}
                    </p>
                </div>

                <div className="grid max-w-2xl gap-4 rounded-xl border p-4">
                    <h2 className="text-sm font-semibold tracking-tight">
                        Punto de suministro
                    </h2>

                    <div className="grid gap-2">
                        <Label htmlFor="cliente_medidor_id">
                            Cliente medidor
                            <span className="text-destructive">*</span>
                        </Label>
                        <Select
                            value={clienteMedidorId}
                            onValueChange={setClienteMedidorId}
                        >
                            <SelectTrigger
                                id="cliente_medidor_id"
                                className="w-full"
                            >
                                <SelectValue placeholder="Selecciona un cliente medidor" />
                            </SelectTrigger>
                            <SelectContent>
                                {clientesMedidoresProveedor.map((cliente) => (
                                    <SelectItem
                                        key={cliente.id}
                                        value={String(cliente.id)}
                                    >
                                        {cliente.numero_cliente} ·{' '}
                                        {cliente.tipo_suministro}
                                    </SelectItem>
                                ))}
                                <SelectItem value={CREAR_NUEVO}>
                                    + Crear nuevo cliente medidor
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        {errors.cliente_medidor_id && (
                            <p className="text-sm text-destructive">
                                {errors.cliente_medidor_id}
                            </p>
                        )}
                    </div>

                    {creandoClienteMedidor && (
                        <>
                            <p className="text-sm text-muted-foreground">
                                Se creará un cliente medidor nuevo con estos
                                datos al guardar.
                            </p>

                            <div className="grid gap-2">
                                <Label htmlFor="numero_cliente">
                                    N.º de cliente
                                    <span className="text-destructive">*</span>
                                </Label>
                                <Input
                                    id="numero_cliente"
                                    value={numeroCliente}
                                    onChange={(e) =>
                                        setNumeroCliente(e.target.value)
                                    }
                                />
                                {errors['cliente_medidor.numero_cliente'] && (
                                    <p className="text-sm text-destructive">
                                        {
                                            errors[
                                                'cliente_medidor.numero_cliente'
                                            ]
                                        }
                                    </p>
                                )}
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="ccosto_id">
                                    Centro de costo
                                    <span className="text-destructive">*</span>
                                </Label>
                                <Select
                                    value={ccostoId}
                                    onValueChange={setCcostoId}
                                >
                                    <SelectTrigger
                                        id="ccosto_id"
                                        className="w-full"
                                    >
                                        <SelectValue placeholder="Selecciona un centro de costo" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {ccostos.map((ccosto) => (
                                            <SelectItem
                                                key={ccosto.id}
                                                value={String(ccosto.id)}
                                            >
                                                {ccosto.codigo} ·{' '}
                                                {ccosto.nombre}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                {errors['cliente_medidor.ccosto_id'] && (
                                    <p className="text-sm text-destructive">
                                        {errors['cliente_medidor.ccosto_id']}
                                    </p>
                                )}
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="tipo_suministro">
                                    Tipo de suministro
                                    <span className="text-destructive">*</span>
                                </Label>
                                <Input
                                    id="tipo_suministro"
                                    placeholder="Electricidad, agua, gas..."
                                    value={tipoSuministro}
                                    onChange={(e) =>
                                        setTipoSuministro(e.target.value)
                                    }
                                />
                                {errors['cliente_medidor.tipo_suministro'] && (
                                    <p className="text-sm text-destructive">
                                        {
                                            errors[
                                                'cliente_medidor.tipo_suministro'
                                            ]
                                        }
                                    </p>
                                )}
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="direccion_suministro">
                                    Dirección de suministro
                                </Label>
                                <Input
                                    id="direccion_suministro"
                                    value={direccionSuministro}
                                    onChange={(e) =>
                                        setDireccionSuministro(e.target.value)
                                    }
                                />
                            </div>
                        </>
                    )}
                </div>

                <div className="grid max-w-2xl gap-4 rounded-xl border p-4">
                    <h2 className="text-sm font-semibold tracking-tight">
                        Detalle de la boleta/factura
                    </h2>

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
                                placeholder="Código impreso en la boleta"
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
                    <h2 className="text-sm font-semibold tracking-tight">
                        Montos
                    </h2>

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
                        Registrar detalle de consumo
                    </Button>
                    <Button
                        variant="outline"
                        disabled={procesando}
                        onClick={() => router.get(casos.show(caso.id).url)}
                    >
                        Cancelar
                    </Button>
                </div>
            </div>
        </>
    );
}

ConsumoBasicoCrear.layout = {
    breadcrumbs: [
        { title: 'Casos de pago', href: casos.index() },
        { title: 'Detalle', href: '#' },
        { title: 'Completar consumo', href: '#' },
    ],
};
