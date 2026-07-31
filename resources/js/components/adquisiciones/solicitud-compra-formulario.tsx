import { router } from '@inertiajs/react';
import { CheckIcon, ChevronRight } from 'lucide-react';
import { Fragment, useState } from 'react';
import { BotonesSiNo } from '@/components/adquisiciones/botones-si-no';
import type { RespuestaSiNo } from '@/components/adquisiciones/botones-si-no';
import { CampoMonedaMonto } from '@/components/adquisiciones/campo-moneda-monto';
import type { Moneda } from '@/components/adquisiciones/campo-moneda-monto';
import { CampoSoloLectura } from '@/components/adquisiciones/campo-solo-lectura';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Progress } from '@/components/ui/progress';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Textarea } from '@/components/ui/textarea';
import type {
    CcostoSeleccionable,
    FuncionarioSeleccionable,
    ProveedorSeleccionable,
} from '@/types/adquisiciones';

const SIN_PROVEEDOR = 'sin-proveedor';

type PasoKey = 'identificacion' | 'requerimientos' | 'moneda';

const PASOS: { key: PasoKey; label: string }[] = [
    { key: 'identificacion', label: 'Identificación' },
    { key: 'requerimientos', label: 'Requerimientos' },
    { key: 'moneda', label: 'Moneda y Montos' },
];

export type SolicitudCompraValoresIniciales = {
    codigo?: string;
    fecha_inicio: string | null;
    nombre: string | null;
    id_requerimiento: string | null;
    ccosto_id: number | null;
    funcionario_requirente_id: number | null;
    proveedor_id: number | null;
    caracteristicas: string | null;
    motivo_contratacion: string | null;
    en_plan_compras: boolean | null;
    id_pac: string | null;
    codigo_bip: string | null;
    convenio_marco: boolean;
    moneda_compra: string | null;
    monto_estimado_solicitado: string | null;
    fecha_paridad: string | null;
};

function respuesta(valor: boolean | null): RespuestaSiNo {
    if (valor === null) {
        return '';
    }

    return valor ? 'si' : 'no';
}

export function SolicitudCompraFormulario({
    modo,
    ccostos,
    funcionarios,
    proveedores,
    accionUrl,
    metodoHttp,
    volverUrl,
    valoresIniciales,
}: {
    modo: 'crear' | 'editar';
    ccostos: CcostoSeleccionable[];
    funcionarios: FuncionarioSeleccionable[];
    proveedores: ProveedorSeleccionable[];
    accionUrl: string;
    metodoHttp: 'post' | 'put';
    volverUrl: string;
    valoresIniciales?: SolicitudCompraValoresIniciales;
}) {
    const [pasoActivo, setPasoActivo] = useState<PasoKey>('identificacion');
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [procesando, setProcesando] = useState(false);

    const [fechaInicio, setFechaInicio] = useState(
        valoresIniciales?.fecha_inicio ?? '',
    );
    const [nombre, setNombre] = useState(valoresIniciales?.nombre ?? '');
    const [idRequerimiento, setIdRequerimiento] = useState(
        valoresIniciales?.id_requerimiento ?? '',
    );
    const [ccostoId, setCcostoId] = useState(
        valoresIniciales?.ccosto_id != null
            ? String(valoresIniciales.ccosto_id)
            : '',
    );
    const [funcionarioRequirenteId, setFuncionarioRequirenteId] = useState(
        valoresIniciales?.funcionario_requirente_id != null
            ? String(valoresIniciales.funcionario_requirente_id)
            : '',
    );
    const [proveedorId, setProveedorId] = useState(
        valoresIniciales?.proveedor_id != null
            ? String(valoresIniciales.proveedor_id)
            : SIN_PROVEEDOR,
    );
    const [caracteristicas, setCaracteristicas] = useState(
        valoresIniciales?.caracteristicas ?? '',
    );
    const [motivoContratacion, setMotivoContratacion] = useState(
        valoresIniciales?.motivo_contratacion ?? '',
    );
    const [enPlanCompras, setEnPlanCompras] = useState<RespuestaSiNo>(
        respuesta(valoresIniciales?.en_plan_compras ?? null),
    );
    const [idPac, setIdPac] = useState(valoresIniciales?.id_pac ?? '');
    const [codigoBip, setCodigoBip] = useState(
        valoresIniciales?.codigo_bip ?? '',
    );
    const [convenioMarco, setConvenioMarco] = useState<RespuestaSiNo>(
        respuesta(valoresIniciales?.convenio_marco ?? null),
    );
    const [monedaCompra, setMonedaCompra] = useState<Moneda>(
        (valoresIniciales?.moneda_compra as Moneda | null) ?? 'CLP',
    );
    const [montoEstimadoSolicitado, setMontoEstimadoSolicitado] = useState(
        valoresIniciales?.monto_estimado_solicitado ?? '',
    );
    const [fechaParidad, setFechaParidad] = useState(
        valoresIniciales?.fecha_paridad ?? '',
    );

    const funcionariosDeLaUnidad = ccostoId
        ? funcionarios.filter((f) => f.ccosto_id === Number(ccostoId))
        : [];

    const ccostoSeleccionado = ccostos.find((c) => String(c.id) === ccostoId);

    const pasosCompletos: Record<PasoKey, boolean> = {
        identificacion:
            fechaInicio.trim() !== '' &&
            nombre.trim() !== '' &&
            ccostoId !== '' &&
            funcionarioRequirenteId !== '',
        requerimientos:
            caracteristicas.trim() !== '' &&
            motivoContratacion.trim() !== '' &&
            enPlanCompras !== '' &&
            convenioMarco !== '',
        moneda:
            montoEstimadoSolicitado.trim() !== '' &&
            (monedaCompra === 'CLP' || fechaParidad.trim() !== ''),
    };

    const totalCompletos = Object.values(pasosCompletos).filter(Boolean).length;
    const completitud = Math.round((totalCompletos / PASOS.length) * 100);
    const todoCompleto = totalCompletos === PASOS.length;

    const PASO_POR_CAMPO: Record<string, PasoKey> = {
        fecha_inicio: 'identificacion',
        nombre: 'identificacion',
        ccosto_id: 'identificacion',
        funcionario_requirente_id: 'identificacion',
        proveedor_id: 'identificacion',
        caracteristicas: 'requerimientos',
        motivo_contratacion: 'requerimientos',
        en_plan_compras: 'requerimientos',
        id_pac: 'requerimientos',
        id_requerimiento: 'requerimientos',
        codigo_bip: 'requerimientos',
        convenio_marco: 'requerimientos',
        moneda_compra: 'moneda',
        monto_estimado_solicitado: 'moneda',
        fecha_paridad: 'moneda',
    };

    function enviar() {
        setProcesando(true);
        setErrors({});

        router[metodoHttp](
            accionUrl,
            {
                fecha_inicio: fechaInicio,
                nombre,
                id_requerimiento: idRequerimiento || null,
                ccosto_id: ccostoId ? Number(ccostoId) : null,
                funcionario_requirente_id: funcionarioRequirenteId
                    ? Number(funcionarioRequirenteId)
                    : null,
                proveedor_id:
                    proveedorId === SIN_PROVEEDOR ? null : Number(proveedorId),
                caracteristicas,
                motivo_contratacion: motivoContratacion,
                en_plan_compras: enPlanCompras === 'si',
                id_pac: idPac || null,
                codigo_bip: codigoBip || null,
                convenio_marco: convenioMarco === 'si',
                moneda_compra: monedaCompra,
                monto_estimado_solicitado: montoEstimadoSolicitado,
                fecha_paridad: monedaCompra === 'CLP' ? null : fechaParidad,
            },
            {
                onError: (errores) => {
                    const erroresTipados = errores as Record<string, string>;
                    setErrors(erroresTipados);

                    const primerCampo = Object.keys(erroresTipados)[0];
                    const pasoConError = primerCampo
                        ? PASO_POR_CAMPO[primerCampo]
                        : undefined;

                    if (pasoConError) {
                        setPasoActivo(pasoConError);
                    }
                },
                onFinish: () => setProcesando(false),
            },
        );
    }

    return (
        <div className="flex h-full flex-1 flex-col gap-4 p-4">
            <div className="flex items-center justify-between">
                <h1 className="text-xl font-semibold tracking-tight">
                    {modo === 'crear'
                        ? 'Solicitud Proceso de Compras y/o Contratación'
                        : 'Editar solicitud de compra'}
                    <span className="ml-2 text-sm font-normal text-muted-foreground">
                        {modo === 'crear'
                            ? '(Menor a 1.000 UTM)'
                            : valoresIniciales?.codigo}
                    </span>
                </h1>
            </div>

            <div className="grid gap-4 lg:grid-cols-[1fr_320px]">
                <div className="rounded-xl border p-4">
                    <Tabs
                        value={pasoActivo}
                        onValueChange={(valor) =>
                            setPasoActivo(valor as PasoKey)
                        }
                    >
                        <TabsList className="mb-4 h-auto w-full justify-start gap-0 bg-transparent p-0">
                            {PASOS.map((paso, indice) => (
                                <Fragment key={paso.key}>
                                    <TabsTrigger
                                        value={paso.key}
                                        className="flex-1 gap-1.5 rounded-none border-b-2 border-transparent bg-transparent py-2 shadow-none data-[state=active]:border-primary data-[state=active]:bg-transparent data-[state=active]:shadow-none dark:data-[state=active]:border-primary dark:data-[state=active]:bg-transparent"
                                    >
                                        <span
                                            className={
                                                pasosCompletos[paso.key]
                                                    ? 'flex size-5 shrink-0 items-center justify-center rounded-full bg-success text-white'
                                                    : 'flex size-5 shrink-0 items-center justify-center rounded-full border border-input text-[11px] text-muted-foreground'
                                            }
                                        >
                                            {pasosCompletos[paso.key] ? (
                                                <CheckIcon className="size-3" />
                                            ) : (
                                                indice + 1
                                            )}
                                        </span>
                                        {paso.label}
                                    </TabsTrigger>
                                    {indice < PASOS.length - 1 && (
                                        <ChevronRight className="size-4 shrink-0 text-muted-foreground" />
                                    )}
                                </Fragment>
                            ))}
                        </TabsList>

                        <TabsContent
                            value="identificacion"
                            className="grid gap-4"
                        >
                            <div className="grid grid-cols-2 gap-4">
                                <div className="grid gap-2">
                                    <Label htmlFor="numeroSolicitud">
                                        N° de solicitud
                                    </Label>
                                    <CampoSoloLectura
                                        id="numeroSolicitud"
                                        valor={
                                            valoresIniciales?.codigo ??
                                            'Se genera al guardar'
                                        }
                                        vacio={!valoresIniciales?.codigo}
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="fecha_inicio">
                                        Fecha inicio compra
                                        <span className="text-destructive">
                                            *
                                        </span>
                                    </Label>
                                    <Input
                                        id="fecha_inicio"
                                        type="date"
                                        value={fechaInicio}
                                        onChange={(e) =>
                                            setFechaInicio(e.target.value)
                                        }
                                    />
                                    {errors.fecha_inicio && (
                                        <p className="text-sm text-destructive">
                                            {errors.fecha_inicio}
                                        </p>
                                    )}
                                </div>
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="nombre">
                                    Nombre de compra
                                    <span className="text-destructive">*</span>
                                </Label>
                                <Input
                                    id="nombre"
                                    placeholder="Ej: Adquisición de insumos de oficina"
                                    value={nombre}
                                    onChange={(e) => setNombre(e.target.value)}
                                />
                                {errors.nombre && (
                                    <p className="text-sm text-destructive">
                                        {errors.nombre}
                                    </p>
                                )}
                            </div>

                            <div className="grid grid-cols-2 gap-4">
                                <div className="grid gap-2">
                                    <Label htmlFor="ccosto_id">
                                        Unidad requirente
                                        <span className="text-destructive">
                                            *
                                        </span>
                                    </Label>
                                    <Select
                                        value={ccostoId}
                                        onValueChange={(valor) => {
                                            setCcostoId(valor);
                                            setFuncionarioRequirenteId('');
                                        }}
                                    >
                                        <SelectTrigger
                                            id="ccosto_id"
                                            className="w-full"
                                        >
                                            <SelectValue placeholder="Selecciona la unidad requirente" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {ccostos.map((ccosto) => (
                                                <SelectItem
                                                    key={ccosto.id}
                                                    value={String(ccosto.id)}
                                                >
                                                    {ccosto.nombre}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    {errors.ccosto_id && (
                                        <p className="text-sm text-destructive">
                                            {errors.ccosto_id}
                                        </p>
                                    )}
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="funcionario_requirente_id">
                                        Nombre requirente
                                        <span className="text-destructive">
                                            *
                                        </span>
                                    </Label>
                                    <Select
                                        value={funcionarioRequirenteId}
                                        onValueChange={
                                            setFuncionarioRequirenteId
                                        }
                                        disabled={!ccostoId}
                                    >
                                        <SelectTrigger
                                            id="funcionario_requirente_id"
                                            className="w-full"
                                        >
                                            <SelectValue
                                                placeholder={
                                                    ccostoId
                                                        ? 'Selecciona el funcionario requirente'
                                                        : 'Primero selecciona la unidad requirente'
                                                }
                                            />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {funcionariosDeLaUnidad.map(
                                                (funcionario) => (
                                                    <SelectItem
                                                        key={funcionario.id}
                                                        value={String(
                                                            funcionario.id,
                                                        )}
                                                    >
                                                        {funcionario.nombre}
                                                    </SelectItem>
                                                ),
                                            )}
                                        </SelectContent>
                                    </Select>
                                    {errors.funcionario_requirente_id && (
                                        <p className="text-sm text-destructive">
                                            {errors.funcionario_requirente_id}
                                        </p>
                                    )}
                                </div>
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="proveedor_id">Proveedor</Label>
                                <Select
                                    value={proveedorId}
                                    onValueChange={setProveedorId}
                                >
                                    <SelectTrigger
                                        id="proveedor_id"
                                        className="w-full"
                                    >
                                        <SelectValue placeholder="Sin proveedor" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value={SIN_PROVEEDOR}>
                                            Sin proveedor
                                        </SelectItem>
                                        {proveedores.map((proveedor) => (
                                            <SelectItem
                                                key={proveedor.id}
                                                value={String(proveedor.id)}
                                            >
                                                {proveedor.nombre}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                {errors.proveedor_id && (
                                    <p className="text-sm text-destructive">
                                        {errors.proveedor_id}
                                    </p>
                                )}
                            </div>
                        </TabsContent>

                        <TabsContent
                            value="requerimientos"
                            className="grid gap-4"
                        >
                            <div className="grid gap-2">
                                <Label htmlFor="caracteristicas">
                                    Características del Bien y/o Servicio
                                    <span className="text-destructive">*</span>
                                </Label>
                                <Textarea
                                    id="caracteristicas"
                                    placeholder="Describe el bien o servicio solicitado…"
                                    value={caracteristicas}
                                    onChange={(e) =>
                                        setCaracteristicas(e.target.value)
                                    }
                                />
                                {errors.caracteristicas && (
                                    <p className="text-sm text-destructive">
                                        {errors.caracteristicas}
                                    </p>
                                )}
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="motivo_contratacion">
                                    Motivo de Contratación
                                    <span className="text-destructive">*</span>
                                </Label>
                                <Textarea
                                    id="motivo_contratacion"
                                    placeholder="Justifica el motivo de la contratación…"
                                    value={motivoContratacion}
                                    onChange={(e) =>
                                        setMotivoContratacion(e.target.value)
                                    }
                                />
                                {errors.motivo_contratacion && (
                                    <p className="text-sm text-destructive">
                                        {errors.motivo_contratacion}
                                    </p>
                                )}
                            </div>

                            <div className="grid grid-cols-2 gap-4">
                                <div className="grid gap-2">
                                    <Label htmlFor="id_requerimiento">
                                        ID requerimiento
                                    </Label>
                                    <Input
                                        id="id_requerimiento"
                                        placeholder="ID del requerimiento"
                                        value={idRequerimiento}
                                        onChange={(e) =>
                                            setIdRequerimiento(e.target.value)
                                        }
                                    />
                                    {errors.id_requerimiento && (
                                        <p className="text-sm text-destructive">
                                            {errors.id_requerimiento}
                                        </p>
                                    )}
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="codigo_bip">
                                        Código BIP (SUBT. 31)
                                    </Label>
                                    <Input
                                        id="codigo_bip"
                                        placeholder="Código BIP, si aplica"
                                        value={codigoBip}
                                        onChange={(e) =>
                                            setCodigoBip(e.target.value)
                                        }
                                    />
                                    {errors.codigo_bip && (
                                        <p className="text-sm text-destructive">
                                            {errors.codigo_bip}
                                        </p>
                                    )}
                                </div>
                            </div>

                            <div className="grid grid-cols-2 gap-4">
                                <div className="grid gap-2">
                                    <Label htmlFor="en_plan_compras">
                                        ¿En Plan de Compras?
                                        <span className="text-destructive">
                                            *
                                        </span>
                                    </Label>
                                    <BotonesSiNo
                                        id="en_plan_compras"
                                        valor={enPlanCompras}
                                        onChange={setEnPlanCompras}
                                    />
                                    {errors.en_plan_compras && (
                                        <p className="text-sm text-destructive">
                                            {errors.en_plan_compras}
                                        </p>
                                    )}
                                </div>

                                {enPlanCompras === 'si' && (
                                    <div className="grid gap-2">
                                        <Label htmlFor="id_pac">
                                            ID del PAC
                                        </Label>
                                        <Input
                                            id="id_pac"
                                            placeholder="ID del Plan Anual de Compras"
                                            value={idPac}
                                            onChange={(e) =>
                                                setIdPac(e.target.value)
                                            }
                                        />
                                        {errors.id_pac && (
                                            <p className="text-sm text-destructive">
                                                {errors.id_pac}
                                            </p>
                                        )}
                                    </div>
                                )}
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="convenio_marco">
                                    ¿El bien o servicio se encuentra en Convenio
                                    Marco?
                                    <span className="text-destructive">*</span>
                                </Label>
                                <BotonesSiNo
                                    id="convenio_marco"
                                    valor={convenioMarco}
                                    onChange={setConvenioMarco}
                                />
                                <p className="text-xs text-muted-foreground">
                                    Si respondes &quot;No&quot;, deberás
                                    adjuntar en el expediente el informe que
                                    justifica no comprar por Convenio Marco.
                                </p>
                                {errors.convenio_marco && (
                                    <p className="text-sm text-destructive">
                                        {errors.convenio_marco}
                                    </p>
                                )}
                            </div>
                        </TabsContent>

                        <TabsContent value="moneda" className="grid gap-4">
                            <CampoMonedaMonto
                                moneda={monedaCompra}
                                montoSolicitado={montoEstimadoSolicitado}
                                fechaParidad={fechaParidad}
                                onChangeMoneda={setMonedaCompra}
                                onChangeMontoSolicitado={
                                    setMontoEstimadoSolicitado
                                }
                                onChangeFechaParidad={setFechaParidad}
                                errors={errors}
                            />
                        </TabsContent>
                    </Tabs>

                    <div className="mt-6 flex items-center justify-between border-t pt-4">
                        <Button
                            variant="outline"
                            disabled={procesando}
                            onClick={() => router.get(volverUrl)}
                        >
                            Cancelar
                        </Button>

                        <div className="flex gap-2">
                            {pasoActivo !== PASOS[0].key && (
                                <Button
                                    variant="outline"
                                    disabled={procesando}
                                    onClick={() => {
                                        const idx = PASOS.findIndex(
                                            (p) => p.key === pasoActivo,
                                        );
                                        setPasoActivo(PASOS[idx - 1].key);
                                    }}
                                >
                                    ‹ Anterior
                                </Button>
                            )}
                            {pasoActivo !== PASOS[PASOS.length - 1].key && (
                                <Button
                                    variant="outline"
                                    disabled={procesando}
                                    onClick={() => {
                                        const idx = PASOS.findIndex(
                                            (p) => p.key === pasoActivo,
                                        );
                                        setPasoActivo(PASOS[idx + 1].key);
                                    }}
                                >
                                    Siguiente ›
                                </Button>
                            )}
                            <Button
                                disabled={procesando || !todoCompleto}
                                onClick={enviar}
                            >
                                {modo === 'crear'
                                    ? 'Crear solicitud'
                                    : 'Guardar cambios'}
                            </Button>
                        </div>
                    </div>
                </div>

                <div className="flex flex-col gap-4 rounded-xl border p-4">
                    <div>
                        <p className="text-xs text-muted-foreground">Resumen</p>
                        <p className="text-xs text-muted-foreground">
                            Vista previa de la solicitud
                        </p>
                    </div>

                    <div>
                        <div className="truncate font-medium">
                            {nombre.trim() || 'Sin nombre'}
                        </div>
                        <div className="truncate text-xs text-muted-foreground">
                            {ccostoSeleccionado?.nombre ?? 'Sin unidad'}
                        </div>
                    </div>

                    <dl className="grid gap-2 text-sm">
                        <div className="flex items-center justify-between">
                            <dt className="text-muted-foreground">
                                N° de solicitud
                            </dt>
                            <dd className="font-mono text-xs">
                                {valoresIniciales?.codigo ?? '—'}
                            </dd>
                        </div>
                        <div className="flex items-center justify-between">
                            <dt className="text-muted-foreground">
                                Monto solicitado
                            </dt>
                            <dd>
                                {montoEstimadoSolicitado
                                    ? `${monedaCompra} ${montoEstimadoSolicitado}`
                                    : '—'}
                            </dd>
                        </div>
                    </dl>

                    <div className="border-t pt-4">
                        <div className="mb-2 flex items-center justify-between text-sm">
                            <span className="text-muted-foreground">
                                Completitud de la solicitud
                            </span>
                            <span className="font-medium">{completitud}%</span>
                        </div>
                        <Progress value={completitud} />

                        <ul className="mt-3 flex flex-col gap-2 text-sm">
                            {PASOS.map((paso) => (
                                <li
                                    key={paso.key}
                                    className="flex items-center gap-2"
                                >
                                    <span
                                        className={
                                            pasosCompletos[paso.key]
                                                ? 'flex size-4 items-center justify-center rounded-full bg-success text-white'
                                                : 'size-4 rounded-full border border-input'
                                        }
                                    >
                                        {pasosCompletos[paso.key] && (
                                            <CheckIcon className="size-3" />
                                        )}
                                    </span>
                                    <span
                                        className={
                                            pasosCompletos[paso.key]
                                                ? 'text-foreground'
                                                : 'text-muted-foreground'
                                        }
                                    >
                                        {paso.label}
                                    </span>
                                </li>
                            ))}
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    );
}
