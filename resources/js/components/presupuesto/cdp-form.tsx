import { Link } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
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
import { cn } from '@/lib/utils';
import { paridad } from '@/routes/presupuesto/cdps';
import type {
    LineaPresupuestoSeleccionable,
    ProcesoAdquisicionSeleccionable,
} from '@/types/presupuesto';

const SIN_ADQUISICION = 'sin-adquisicion';
const SIN_MEDIO_SOLICITUD = 'sin-especificar';

const SIMBOLO_MONEDA: Record<'CLP' | 'UF' | 'USD', string> = {
    CLP: '$',
    UF: 'UF',
    USD: 'US$',
};

export type CdpFormValues = {
    presupuesto_id: string;
    fecha_solicitud: string;
    tipo_gasto: 'GO' | 'INI';
    codigo_iniciativa: string;
    nombre: string;
    nombre_iniciativa: string;
    caracter_gasto: 'transitorio' | 'permanente';
    medio_solicitud: string;
    requerimiento_numero: string;
    moneda_compra: 'CLP' | 'UF' | 'USD';
    total_moneda_compra: string;
    fecha_paridad: string;
    anio_validez: string;
    proceso_adquisicion_id: string;
};

export const CDP_FORM_VALORES_INICIALES: CdpFormValues = {
    presupuesto_id: '',
    fecha_solicitud: '',
    tipo_gasto: 'GO',
    codigo_iniciativa: '',
    nombre: '',
    nombre_iniciativa: '',
    caracter_gasto: 'transitorio',
    medio_solicitud: SIN_MEDIO_SOLICITUD,
    requerimiento_numero: '',
    moneda_compra: 'CLP',
    total_moneda_compra: '',
    fecha_paridad: '',
    anio_validez: String(new Date().getFullYear()),
    proceso_adquisicion_id: SIN_ADQUISICION,
};

type CdpFormProps = {
    valores: CdpFormValues;
    onChange: (valores: CdpFormValues) => void;
    errors: Record<string, string>;
    lineasPresupuesto: LineaPresupuestoSeleccionable[];
    procesosAdquisicion: ProcesoAdquisicionSeleccionable[];
    procesando: boolean;
    onSubmit: () => void;
    textoBoton: string;
    folio?: string;
    hrefCancelar: string;
};

function SeccionCard({
    numero,
    titulo,
    children,
}: {
    numero: number;
    titulo: string;
    children: React.ReactNode;
}) {
    return (
        <Card className="gap-0 py-0">
            <CardHeader className="flex-row items-center gap-3 rounded-t-xl border-b bg-muted/30 py-4">
                <span className="flex size-6 shrink-0 items-center justify-center rounded-md bg-primary text-xs font-bold text-primary-foreground">
                    {numero}
                </span>
                <CardTitle className="text-sm">{titulo}</CardTitle>
            </CardHeader>
            <CardContent className="grid grid-cols-2 gap-4 py-6">
                {children}
            </CardContent>
        </Card>
    );
}

function CampoSoloLectura({
    id,
    valor,
    vacio,
}: {
    id?: string;
    valor: React.ReactNode;
    vacio?: boolean;
}) {
    return (
        <div
            id={id}
            className={cn(
                'flex h-9 items-center rounded-md border bg-muted/30 px-3 text-sm',
                vacio && 'text-muted-foreground italic',
            )}
        >
            {valor}
        </div>
    );
}

function GrupoRadio<T extends string>({
    name,
    opciones,
    valor,
    onChange,
}: {
    name: string;
    opciones: { value: T; label: string }[];
    valor: T;
    onChange: (valor: T) => void;
}) {
    return (
        <div className="flex flex-wrap items-center gap-4 pt-1">
            {opciones.map((opcion) => (
                <label
                    key={opcion.value}
                    className="flex cursor-pointer items-center gap-2 text-sm font-medium"
                >
                    <input
                        type="radio"
                        name={name}
                        value={opcion.value}
                        checked={valor === opcion.value}
                        onChange={() => onChange(opcion.value)}
                        className="size-4 cursor-pointer accent-primary"
                    />
                    {opcion.label}
                </label>
            ))}
        </div>
    );
}

export function CdpForm({
    valores,
    onChange,
    errors,
    lineasPresupuesto,
    procesosAdquisicion,
    procesando,
    onSubmit,
    textoBoton,
    folio,
    hrefCancelar,
}: CdpFormProps) {
    function set<K extends keyof CdpFormValues>(
        campo: K,
        valor: CdpFormValues[K],
    ) {
        onChange({ ...valores, [campo]: valor });
    }

    const lineaSeleccionada = lineasPresupuesto.find(
        (linea) => String(linea.id) === valores.presupuesto_id,
    );

    // Paridad y monto son de solo lectura: la paridad se resuelve contra el
    // indicador económico real vigente para la fecha elegida (misma fuente
    // que usa el servidor al guardar); el monto es siempre
    // total_moneda_compra × paridad. El servidor vuelve a calcular ambos al
    // guardar — esto es solo una previsualización.
    const [paridadPreview, setParidadPreview] = useState<{
        valor: number;
        error: string | null;
    }>({ valor: 1, error: null });

    useEffect(() => {
        if (valores.moneda_compra === 'CLP' || !valores.fecha_paridad) {
            return;
        }

        let cancelado = false;

        fetch(
            paridad.url({
                query: {
                    moneda: valores.moneda_compra,
                    fecha: valores.fecha_paridad,
                },
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
    }, [valores.moneda_compra, valores.fecha_paridad]);

    const sinParidadAplicable =
        valores.moneda_compra === 'CLP' || !valores.fecha_paridad;
    const paridadEfectiva = sinParidadAplicable ? 1 : paridadPreview.valor;
    const paridadError = sinParidadAplicable ? null : paridadPreview.error;

    const totalMonedaCompra = parseFloat(valores.total_moneda_compra) || 0;
    const montoPreview = Math.round(totalMonedaCompra * paridadEfectiva);

    function cambiarMoneda(valor: 'CLP' | 'UF' | 'USD') {
        const cambios: Partial<CdpFormValues> = { moneda_compra: valor };

        if (valor !== 'CLP' && !valores.fecha_paridad) {
            cambios.fecha_paridad = new Date().toISOString().slice(0, 10);
        }

        onChange({ ...valores, ...cambios });
    }

    const camposIncompletos =
        !valores.presupuesto_id ||
        !valores.nombre ||
        !valores.total_moneda_compra;

    return (
        <div className="grid max-w-3xl gap-6">
            {/* 1. Identificación del Certificado */}
            <SeccionCard numero={1} titulo="Identificación del Certificado">
                <div className="col-span-2 grid grid-cols-3 gap-4">
                    <div className="grid gap-2">
                        <Label htmlFor="numeroCdp">N° CDP</Label>
                        <CampoSoloLectura
                            id="numeroCdp"
                            valor={folio ?? '—'}
                            vacio={!folio}
                        />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="fecha_solicitud">Fecha</Label>
                        <Input
                            id="fecha_solicitud"
                            type="date"
                            value={valores.fecha_solicitud}
                            onChange={(e) =>
                                set('fecha_solicitud', e.target.value)
                            }
                        />
                        {errors.fecha_solicitud && (
                            <p className="text-sm text-destructive">
                                {errors.fecha_solicitud}
                            </p>
                        )}
                    </div>
                    <div className="grid gap-2">
                        <Label>
                            Tipo de gasto
                            <span className="text-destructive">*</span>
                        </Label>
                        <GrupoRadio
                            name="tipo_gasto"
                            valor={valores.tipo_gasto}
                            onChange={(v) => set('tipo_gasto', v)}
                            opciones={[
                                { value: 'GO', label: 'Gasto Operacional' },
                                { value: 'INI', label: 'Gasto de Inversión' },
                            ]}
                        />
                        {errors.tipo_gasto && (
                            <p className="text-sm text-destructive">
                                {errors.tipo_gasto}
                            </p>
                        )}
                    </div>
                </div>

                <div className="col-span-2 grid gap-2">
                    <Label htmlFor="nombre">
                        Detalle del requerimiento
                        <span className="text-destructive">*</span>
                    </Label>
                    <textarea
                        id="nombre"
                        className="min-h-20 rounded-md border bg-background p-2 text-sm"
                        placeholder="Ej: Cambio de pasaje aéreo Academia Judicial Sr. …"
                        value={valores.nombre}
                        onChange={(e) => set('nombre', e.target.value)}
                    />
                    {errors.nombre && (
                        <p className="text-sm text-destructive">
                            {errors.nombre}
                        </p>
                    )}
                </div>

                {valores.tipo_gasto === 'INI' && (
                    <div className="col-span-2 grid grid-cols-2 gap-4 rounded-md border border-dashed border-primary bg-primary/5 p-4">
                        <div className="grid gap-2">
                            <Label htmlFor="nombre_iniciativa">
                                Nombre Iniciativa
                                <span className="text-destructive">*</span>
                            </Label>
                            <Input
                                id="nombre_iniciativa"
                                placeholder="Nombre de la iniciativa"
                                value={valores.nombre_iniciativa}
                                onChange={(e) =>
                                    set('nombre_iniciativa', e.target.value)
                                }
                            />
                            {errors.nombre_iniciativa && (
                                <p className="text-sm text-destructive">
                                    {errors.nombre_iniciativa}
                                </p>
                            )}
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="codigo_iniciativa">
                                Código Iniciativa
                                <span className="text-destructive">*</span>
                            </Label>
                            <Input
                                id="codigo_iniciativa"
                                placeholder="Código"
                                value={valores.codigo_iniciativa}
                                onChange={(e) =>
                                    set('codigo_iniciativa', e.target.value)
                                }
                            />
                            {errors.codigo_iniciativa && (
                                <p className="text-sm text-destructive">
                                    {errors.codigo_iniciativa}
                                </p>
                            )}
                        </div>
                    </div>
                )}
            </SeccionCard>

            {/* 2. Cuenta Presupuestaria */}
            <SeccionCard numero={2} titulo="Cuenta Presupuestaria">
                <div className="grid gap-2">
                    <Label htmlFor="presupuesto_id">
                        Cuenta Presupuestaria
                        <span className="text-destructive">*</span>
                    </Label>
                    <Select
                        value={valores.presupuesto_id}
                        onValueChange={(v) => set('presupuesto_id', v)}
                    >
                        <SelectTrigger id="presupuesto_id" className="w-full">
                            <SelectValue placeholder="Selecciona una cuenta">
                                {lineaSeleccionada?.codigo}
                            </SelectValue>
                        </SelectTrigger>
                        <SelectContent>
                            {lineasPresupuesto.map((linea) => (
                                <SelectItem
                                    key={linea.id}
                                    value={String(linea.id)}
                                >
                                    {linea.etiqueta}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    {errors.presupuesto_id && (
                        <p className="text-sm text-destructive">
                            {errors.presupuesto_id}
                        </p>
                    )}
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="denominacionGasto">
                        Denominación del gasto (catálogo)
                    </Label>
                    <CampoSoloLectura
                        id="denominacionGasto"
                        valor={
                            lineaSeleccionada?.denominacion ??
                            'Se completa al elegir la cuenta'
                        }
                        vacio={!lineaSeleccionada}
                    />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="unidadEjecutora">Unidad Ejecutora</Label>
                    <CampoSoloLectura
                        id="unidadEjecutora"
                        valor={lineaSeleccionada?.unidad_ejecutora ?? '—'}
                        vacio={!lineaSeleccionada}
                    />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="numUE">N° UE</Label>
                    <CampoSoloLectura
                        id="numUE"
                        valor={lineaSeleccionada?.n_ue ?? '—'}
                        vacio={!lineaSeleccionada}
                    />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="anio_validez">
                        Validez<span className="text-destructive">*</span>
                    </Label>
                    <Input
                        id="anio_validez"
                        type="number"
                        value={valores.anio_validez}
                        onChange={(e) => set('anio_validez', e.target.value)}
                    />
                    {errors.anio_validez && (
                        <p className="text-sm text-destructive">
                            {errors.anio_validez}
                        </p>
                    )}
                </div>

                <div className="grid gap-2">
                    <Label>
                        Carácter del gasto
                        <span className="text-destructive">*</span>
                    </Label>
                    <GrupoRadio
                        name="caracter_gasto"
                        valor={valores.caracter_gasto}
                        onChange={(v) => set('caracter_gasto', v)}
                        opciones={[
                            { value: 'transitorio', label: 'Transitorio' },
                            { value: 'permanente', label: 'Permanente' },
                        ]}
                    />
                    {errors.caracter_gasto && (
                        <p className="text-sm text-destructive">
                            {errors.caracter_gasto}
                        </p>
                    )}
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="medio_solicitud">Medio de solicitud</Label>
                    <Select
                        value={valores.medio_solicitud}
                        onValueChange={(v) => set('medio_solicitud', v)}
                    >
                        <SelectTrigger id="medio_solicitud" className="w-full">
                            <SelectValue placeholder="Sin especificar" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value={SIN_MEDIO_SOLICITUD}>
                                Sin especificar
                            </SelectItem>
                            <SelectItem value="Requerimiento">
                                Requerimiento
                            </SelectItem>
                            <SelectItem value="Oficio">Oficio</SelectItem>
                            <SelectItem value="Otro">Otro</SelectItem>
                        </SelectContent>
                    </Select>
                    {errors.medio_solicitud && (
                        <p className="text-sm text-destructive">
                            {errors.medio_solicitud}
                        </p>
                    )}
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="requerimiento_numero">
                        N° de Requerimiento
                    </Label>
                    <Input
                        id="requerimiento_numero"
                        placeholder="Ej: 1365"
                        value={valores.requerimiento_numero}
                        onChange={(e) =>
                            set('requerimiento_numero', e.target.value)
                        }
                    />
                    {errors.requerimiento_numero && (
                        <p className="text-sm text-destructive">
                            {errors.requerimiento_numero}
                        </p>
                    )}
                </div>
            </SeccionCard>

            {/* 3. Moneda, Paridad y Monto */}
            <SeccionCard numero={3} titulo="Moneda, Paridad y Monto">
                <div className="col-span-2 grid grid-cols-2 gap-4">
                    <div className="grid gap-2">
                        <Label htmlFor="moneda_compra">
                            Moneda de compra
                            <span className="text-destructive">*</span>
                        </Label>
                        <Select
                            value={valores.moneda_compra}
                            onValueChange={(v) =>
                                cambiarMoneda(v as 'CLP' | 'UF' | 'USD')
                            }
                        >
                            <SelectTrigger
                                id="moneda_compra"
                                className="w-full"
                            >
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
                        <Label htmlFor="total_moneda_compra">
                            Total moneda de compra
                            <span className="text-destructive">*</span>
                        </Label>
                        <div className="relative">
                            <span className="pointer-events-none absolute top-1/2 left-3 -translate-y-1/2 text-sm font-semibold text-muted-foreground">
                                {SIMBOLO_MONEDA[valores.moneda_compra]}
                            </span>
                            <Input
                                id="total_moneda_compra"
                                type="number"
                                step="0.0001"
                                min="0"
                                placeholder="0"
                                className="pl-9"
                                value={valores.total_moneda_compra}
                                onChange={(e) =>
                                    set('total_moneda_compra', e.target.value)
                                }
                            />
                        </div>
                        {errors.total_moneda_compra && (
                            <p className="text-sm text-destructive">
                                {errors.total_moneda_compra}
                            </p>
                        )}
                    </div>
                </div>

                {valores.moneda_compra !== 'CLP' && (
                    <div className="col-span-2 grid grid-cols-2 gap-4 rounded-md border border-dashed border-primary bg-primary/5 p-4">
                        <div className="grid gap-2">
                            <Label htmlFor="fecha_paridad">
                                Fecha de paridad
                            </Label>
                            <Input
                                id="fecha_paridad"
                                type="date"
                                value={valores.fecha_paridad}
                                onChange={(e) =>
                                    set('fecha_paridad', e.target.value)
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

                <div className="col-span-2 grid gap-2">
                    <Label htmlFor="montoImpuesto">
                        Monto con impuesto incluido
                    </Label>
                    <CampoSoloLectura
                        id="montoImpuesto"
                        valor={
                            <span className="font-medium">
                                {formatMonto(montoPreview)}
                            </span>
                        }
                    />
                </div>
            </SeccionCard>

            {/* Adquisición vinculada — no existe en el mockup, campo real del sistema */}
            <SeccionCard numero={4} titulo="Adquisición vinculada">
                <div className="col-span-2 grid gap-2">
                    <Label htmlFor="proceso_adquisicion_id">
                        Adquisición vinculada
                    </Label>
                    <Select
                        value={valores.proceso_adquisicion_id}
                        onValueChange={(v) => set('proceso_adquisicion_id', v)}
                    >
                        <SelectTrigger
                            id="proceso_adquisicion_id"
                            className="w-full"
                        >
                            <SelectValue placeholder="Sin vínculo" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value={SIN_ADQUISICION}>
                                Sin vínculo
                            </SelectItem>
                            {procesosAdquisicion.map((proceso) => (
                                <SelectItem
                                    key={proceso.id}
                                    value={String(proceso.id)}
                                >
                                    {proceso.codigo}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    {errors.proceso_adquisicion_id && (
                        <p className="text-sm text-destructive">
                            {errors.proceso_adquisicion_id}
                        </p>
                    )}
                </div>
            </SeccionCard>

            {/* Footer */}
            <div className="sticky bottom-0 flex flex-wrap items-center gap-4 rounded-md border bg-background p-4 shadow-sm">
                <span className="text-sm text-muted-foreground">
                    {camposIncompletos
                        ? 'Completa los campos requeridos (*) para continuar.'
                        : 'Todo listo para guardar.'}
                </span>
                <div className="ml-auto flex gap-2">
                    <Button variant="outline" asChild>
                        <Link href={hrefCancelar}>Cancelar</Link>
                    </Button>
                    <Button disabled={procesando} onClick={onSubmit}>
                        {textoBoton}
                    </Button>
                </div>
            </div>
        </div>
    );
}

export { SIN_ADQUISICION, SIN_MEDIO_SOLICITUD };
