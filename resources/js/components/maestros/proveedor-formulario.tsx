import { router } from '@inertiajs/react';
import { CheckIcon, ChevronRight } from 'lucide-react';
import { Fragment, useState } from 'react';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
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
import { Switch } from '@/components/ui/switch';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Textarea } from '@/components/ui/textarea';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { useInitials } from '@/hooks/use-initials';
import type { CatalogosProveedor, Proveedor } from '@/types/maestros';

const SIN_SELECCION = '__sin_seleccion__';
const OTRO_BANCO = '__otro_banco__';

type PasoKey =
    | 'identificacion'
    | 'clasificacion'
    | 'contacto'
    | 'domicilio'
    | 'bancarios';

const PASOS: { key: PasoKey; label: string }[] = [
    { key: 'identificacion', label: 'Identificación' },
    { key: 'clasificacion', label: 'Clasificación' },
    { key: 'contacto', label: 'Contacto' },
    { key: 'domicilio', label: 'Domicilio' },
    { key: 'bancarios', label: 'Datos bancarios' },
];

const PASO_POR_CAMPO: Record<string, PasoKey> = {
    rutproveedor: 'identificacion',
    nombre: 'identificacion',
    giro: 'identificacion',
    tipo_contribuyente: 'identificacion',
    rubros: 'clasificacion',
    contacto: 'contacto',
    contacto_cargo: 'contacto',
    contacto_telefono: 'contacto',
    correo: 'contacto',
    direccion: 'domicilio',
    region: 'domicilio',
    comuna: 'domicilio',
    banco: 'bancarios',
    tipo_cuenta: 'bancarios',
    numero_cuenta: 'bancarios',
    condicion_pago: 'bancarios',
    moneda: 'bancarios',
    correo_pago: 'bancarios',
    documento_respaldo: 'bancarios',
    notas_internas: 'bancarios',
};

type ProveedorFormularioProps = {
    modo: 'crear' | 'editar';
    catalogos: CatalogosProveedor;
    accionUrl: string;
    metodoHttp: 'post' | 'patch';
    volverUrl: string;
    valoresIniciales?: Proveedor;
    tieneDocumentoRespaldo?: boolean;
};

export function ProveedorFormulario({
    modo,
    catalogos,
    accionUrl,
    metodoHttp,
    volverUrl,
    valoresIniciales,
    tieneDocumentoRespaldo = false,
}: ProveedorFormularioProps) {
    const getInitials = useInitials();

    const [pasoActivo, setPasoActivo] = useState<PasoKey>('identificacion');
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [procesando, setProcesando] = useState(false);

    const [rutproveedor, setRutproveedor] = useState(
        valoresIniciales?.rutproveedor ?? '',
    );
    const [nombre, setNombre] = useState(valoresIniciales?.nombre ?? '');
    const [giro, setGiro] = useState(valoresIniciales?.giro ?? '');
    const [tipoContribuyente, setTipoContribuyente] = useState(
        valoresIniciales?.tipo_contribuyente ?? SIN_SELECCION,
    );

    const [rubros, setRubros] = useState<string[]>(
        valoresIniciales?.rubros ?? [],
    );

    const [contacto, setContacto] = useState(valoresIniciales?.contacto ?? '');
    const [contactoCargo, setContactoCargo] = useState(
        valoresIniciales?.contacto_cargo ?? '',
    );
    const [contactoTelefono, setContactoTelefono] = useState(
        valoresIniciales?.contacto_telefono ?? '',
    );
    const [correo, setCorreo] = useState(valoresIniciales?.correo ?? '');

    const [direccion, setDireccion] = useState(
        valoresIniciales?.direccion ?? '',
    );
    const [region, setRegion] = useState(valoresIniciales?.region ?? '');
    const [comuna, setComuna] = useState(valoresIniciales?.comuna ?? '');

    const [banco, setBanco] = useState(
        valoresIniciales?.banco ?? SIN_SELECCION,
    );
    const [bancoLibre, setBancoLibre] = useState('');
    const [tipoCuenta, setTipoCuenta] = useState(
        valoresIniciales?.tipo_cuenta ?? SIN_SELECCION,
    );
    const [numeroCuenta, setNumeroCuenta] = useState(
        valoresIniciales?.numero_cuenta ?? '',
    );
    const [condicionPago, setCondicionPago] = useState(
        valoresIniciales?.condicion_pago ??
            catalogos.condicionesPago.find((o) => o.value === 'dias_30')
                ?.value ??
            catalogos.condicionesPago[0]?.value,
    );
    const [moneda, setMoneda] = useState(
        valoresIniciales?.moneda ??
            catalogos.monedas.find((o) => o.value === 'clp')?.value ??
            catalogos.monedas[0]?.value,
    );
    const [correoPago, setCorreoPago] = useState(
        valoresIniciales?.correo_pago ?? '',
    );
    const [documentoRespaldo, setDocumentoRespaldo] = useState<File | null>(
        null,
    );
    const [notasInternas, setNotasInternas] = useState(
        valoresIniciales?.notas_internas ?? '',
    );
    const [activo, setActivo] = useState(valoresIniciales?.activo ?? true);

    function alternarRubro(valor: string, marcado: boolean) {
        setRubros((actuales) =>
            marcado
                ? [...actuales, valor]
                : actuales.filter((r) => r !== valor),
        );
    }

    const bancoFinal =
        banco === OTRO_BANCO
            ? bancoLibre
            : banco === SIN_SELECCION
              ? ''
              : banco;

    const pasosCompletos: Record<PasoKey, boolean> = {
        identificacion: rutproveedor.trim() !== '' && nombre.trim() !== '',
        clasificacion: rubros.length > 0,
        contacto: contacto.trim() !== '',
        domicilio: direccion.trim() !== '',
        bancarios: bancoFinal.trim() !== '' && numeroCuenta.trim() !== '',
    };

    const totalCompletos = Object.values(pasosCompletos).filter(Boolean).length;
    const completitud = Math.round((totalCompletos / PASOS.length) * 100);

    function enviar() {
        setProcesando(true);
        setErrors({});

        router[metodoHttp](
            accionUrl,
            {
                rutproveedor,
                nombre,
                giro: giro || null,
                tipo_contribuyente:
                    tipoContribuyente === SIN_SELECCION
                        ? null
                        : tipoContribuyente,
                rubros,
                contacto: contacto || null,
                contacto_cargo: contactoCargo || null,
                contacto_telefono: contactoTelefono || null,
                correo: correo || null,
                direccion: direccion || null,
                region: region || null,
                comuna: comuna || null,
                banco: bancoFinal || null,
                tipo_cuenta: tipoCuenta === SIN_SELECCION ? null : tipoCuenta,
                numero_cuenta: numeroCuenta || null,
                condicion_pago: condicionPago,
                moneda: moneda,
                correo_pago: correoPago || null,
                documento_respaldo: documentoRespaldo,
                notas_internas: notasInternas || null,
                activo,
            },
            {
                forceFormData: true,
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
                        ? 'Registrar proveedor'
                        : 'Editar proveedor'}
                </h1>
                {modo === 'crear' && (
                    <Badge
                        variant="outline"
                        className="border-transparent bg-warning-soft text-warning"
                    >
                        Borrador sin guardar
                    </Badge>
                )}
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
                            <div className="grid gap-2">
                                <Label htmlFor="rutproveedor">
                                    RUT
                                    <span className="text-destructive">*</span>
                                </Label>
                                <Input
                                    id="rutproveedor"
                                    value={rutproveedor}
                                    onChange={(e) =>
                                        setRutproveedor(e.target.value)
                                    }
                                />
                                {errors.rutproveedor && (
                                    <p className="text-sm text-destructive">
                                        {errors.rutproveedor}
                                    </p>
                                )}
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="nombre">
                                    Razón social
                                    <span className="text-destructive">*</span>
                                </Label>
                                <Input
                                    id="nombre"
                                    value={nombre}
                                    onChange={(e) => setNombre(e.target.value)}
                                />
                                {errors.nombre && (
                                    <p className="text-sm text-destructive">
                                        {errors.nombre}
                                    </p>
                                )}
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="giro">Giro</Label>
                                <Input
                                    id="giro"
                                    value={giro}
                                    onChange={(e) => setGiro(e.target.value)}
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="tipo_contribuyente">
                                    Tipo de contribuyente
                                </Label>
                                <Select
                                    value={tipoContribuyente}
                                    onValueChange={setTipoContribuyente}
                                >
                                    <SelectTrigger
                                        id="tipo_contribuyente"
                                        className="w-full"
                                    >
                                        <SelectValue placeholder="Selecciona un tipo…" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value={SIN_SELECCION}>
                                            Sin especificar
                                        </SelectItem>
                                        {catalogos.tiposContribuyente.map(
                                            (opcion) => (
                                                <SelectItem
                                                    key={opcion.value}
                                                    value={opcion.value}
                                                >
                                                    {opcion.label}
                                                </SelectItem>
                                            ),
                                        )}
                                    </SelectContent>
                                </Select>
                            </div>
                        </TabsContent>

                        <TabsContent
                            value="clasificacion"
                            className="grid gap-3"
                        >
                            <Label>Rubros</Label>
                            <div className="grid gap-2 sm:grid-cols-2">
                                {catalogos.rubros.map((opcion) => (
                                    <div
                                        key={opcion.value}
                                        className="flex items-center gap-2"
                                    >
                                        <Checkbox
                                            id={`rubro-${opcion.value}`}
                                            checked={rubros.includes(
                                                opcion.value,
                                            )}
                                            onCheckedChange={(checked) =>
                                                alternarRubro(
                                                    opcion.value,
                                                    checked === true,
                                                )
                                            }
                                        />
                                        <Label
                                            htmlFor={`rubro-${opcion.value}`}
                                            className="font-normal"
                                        >
                                            {opcion.label}
                                        </Label>
                                    </div>
                                ))}
                            </div>
                            {errors.rubros && (
                                <p className="text-sm text-destructive">
                                    {errors.rubros}
                                </p>
                            )}
                        </TabsContent>

                        <TabsContent value="contacto" className="grid gap-4">
                            <div className="grid gap-2">
                                <Label htmlFor="contacto">
                                    Nombre de contacto
                                </Label>
                                <Input
                                    id="contacto"
                                    value={contacto}
                                    onChange={(e) =>
                                        setContacto(e.target.value)
                                    }
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="contacto_cargo">Cargo</Label>
                                <Input
                                    id="contacto_cargo"
                                    value={contactoCargo}
                                    onChange={(e) =>
                                        setContactoCargo(e.target.value)
                                    }
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="contacto_telefono">
                                    Teléfono
                                </Label>
                                <Input
                                    id="contacto_telefono"
                                    value={contactoTelefono}
                                    onChange={(e) =>
                                        setContactoTelefono(e.target.value)
                                    }
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="correo">Correo</Label>
                                <Input
                                    id="correo"
                                    type="email"
                                    value={correo}
                                    onChange={(e) => setCorreo(e.target.value)}
                                />
                                {errors.correo && (
                                    <p className="text-sm text-destructive">
                                        {errors.correo}
                                    </p>
                                )}
                            </div>
                        </TabsContent>

                        <TabsContent value="domicilio" className="grid gap-4">
                            <div className="grid gap-2">
                                <Label htmlFor="direccion">Dirección</Label>
                                <Input
                                    id="direccion"
                                    value={direccion}
                                    onChange={(e) =>
                                        setDireccion(e.target.value)
                                    }
                                />
                            </div>

                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="region">Región</Label>
                                    <Input
                                        id="region"
                                        value={region}
                                        onChange={(e) =>
                                            setRegion(e.target.value)
                                        }
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="comuna">Comuna</Label>
                                    <Input
                                        id="comuna"
                                        value={comuna}
                                        onChange={(e) =>
                                            setComuna(e.target.value)
                                        }
                                    />
                                </div>
                            </div>
                        </TabsContent>

                        <TabsContent value="bancarios" className="grid gap-4">
                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="banco">Banco</Label>
                                    <Select
                                        value={banco}
                                        onValueChange={setBanco}
                                    >
                                        <SelectTrigger
                                            id="banco"
                                            className="w-full"
                                        >
                                            <SelectValue placeholder="Selecciona un banco…" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value={SIN_SELECCION}>
                                                Sin especificar
                                            </SelectItem>
                                            {catalogos.bancos.map(
                                                (nombreBanco) => (
                                                    <SelectItem
                                                        key={nombreBanco}
                                                        value={nombreBanco}
                                                    >
                                                        {nombreBanco}
                                                    </SelectItem>
                                                ),
                                            )}
                                            <SelectItem value={OTRO_BANCO}>
                                                Otro…
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                    {banco === OTRO_BANCO && (
                                        <Input
                                            placeholder="Nombre del banco"
                                            value={bancoLibre}
                                            onChange={(e) =>
                                                setBancoLibre(e.target.value)
                                            }
                                        />
                                    )}
                                    {errors.banco && (
                                        <p className="text-sm text-destructive">
                                            {errors.banco}
                                        </p>
                                    )}
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="tipo_cuenta">
                                        Tipo de cuenta
                                    </Label>
                                    <Select
                                        value={tipoCuenta}
                                        onValueChange={setTipoCuenta}
                                    >
                                        <SelectTrigger
                                            id="tipo_cuenta"
                                            className="w-full"
                                        >
                                            <SelectValue placeholder="Selecciona un tipo…" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value={SIN_SELECCION}>
                                                Sin especificar
                                            </SelectItem>
                                            {catalogos.tiposCuenta.map(
                                                (opcion) => (
                                                    <SelectItem
                                                        key={opcion.value}
                                                        value={opcion.value}
                                                    >
                                                        {opcion.label}
                                                    </SelectItem>
                                                ),
                                            )}
                                        </SelectContent>
                                    </Select>
                                </div>
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="numero_cuenta">
                                    N° de cuenta
                                </Label>
                                <Input
                                    id="numero_cuenta"
                                    value={numeroCuenta}
                                    onChange={(e) =>
                                        setNumeroCuenta(e.target.value)
                                    }
                                />
                            </div>

                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="condicion_pago">
                                        Condición de pago
                                    </Label>
                                    <Select
                                        value={condicionPago}
                                        onValueChange={setCondicionPago}
                                    >
                                        <SelectTrigger
                                            id="condicion_pago"
                                            className="w-full"
                                        >
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {catalogos.condicionesPago.map(
                                                (opcion) => (
                                                    <SelectItem
                                                        key={opcion.value}
                                                        value={opcion.value}
                                                    >
                                                        {opcion.label}
                                                    </SelectItem>
                                                ),
                                            )}
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="moneda">Moneda</Label>
                                    <Select
                                        value={moneda}
                                        onValueChange={setMoneda}
                                    >
                                        <SelectTrigger
                                            id="moneda"
                                            className="w-full"
                                        >
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {catalogos.monedas.map((opcion) => (
                                                <SelectItem
                                                    key={opcion.value}
                                                    value={opcion.value}
                                                >
                                                    {opcion.label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="correo_pago">
                                    Correo para pagos
                                </Label>
                                <Input
                                    id="correo_pago"
                                    type="email"
                                    placeholder="pagos@proveedor.cl"
                                    value={correoPago}
                                    onChange={(e) =>
                                        setCorreoPago(e.target.value)
                                    }
                                />
                                {errors.correo_pago && (
                                    <p className="text-sm text-destructive">
                                        {errors.correo_pago}
                                    </p>
                                )}
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="documento_respaldo">
                                    Documento de respaldo
                                </Label>
                                <label
                                    htmlFor="documento_respaldo"
                                    className="flex cursor-pointer flex-col items-center gap-1 rounded-md border border-dashed p-4 text-center text-sm text-muted-foreground hover:bg-muted/30"
                                >
                                    {documentoRespaldo
                                        ? documentoRespaldo.name
                                        : tieneDocumentoRespaldo
                                          ? 'Ya existe un documento adjunto — sube uno nuevo para reemplazarlo'
                                          : 'Adjuntar certificado bancario o e-RUT'}
                                    <span className="text-xs">
                                        PDF o imagen, hasta 8 MB — opcional
                                    </span>
                                </label>
                                <input
                                    id="documento_respaldo"
                                    type="file"
                                    accept=".pdf,.jpg,.jpeg,.png"
                                    className="hidden"
                                    onChange={(e) =>
                                        setDocumentoRespaldo(
                                            e.target.files?.[0] ?? null,
                                        )
                                    }
                                />
                                {errors.documento_respaldo && (
                                    <p className="text-sm text-destructive">
                                        {errors.documento_respaldo}
                                    </p>
                                )}
                            </div>

                            <div className="flex items-center justify-between rounded-md border p-3">
                                <div>
                                    <Label htmlFor="activo">
                                        {modo === 'crear'
                                            ? 'Activar proveedor al guardar'
                                            : 'Proveedor activo'}
                                    </Label>
                                    <p className="text-xs text-muted-foreground">
                                        Quedará disponible para asociar a
                                        órdenes de compra de inmediato.
                                    </p>
                                </div>
                                <Switch
                                    id="activo"
                                    checked={activo}
                                    onCheckedChange={setActivo}
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="notas_internas">
                                    Notas internas
                                </Label>
                                <Textarea
                                    id="notas_internas"
                                    placeholder="Observaciones visibles solo para el equipo de Finanzas…"
                                    value={notasInternas}
                                    onChange={(e) =>
                                        setNotasInternas(e.target.value)
                                    }
                                />
                            </div>
                        </TabsContent>
                    </Tabs>

                    <div className="mt-6 flex items-center justify-between border-t pt-4">
                        <div className="flex gap-2">
                            <Button
                                variant="outline"
                                disabled={procesando}
                                onClick={() => router.get(volverUrl)}
                            >
                                Cancelar
                            </Button>
                            {modo === 'crear' && (
                                <Tooltip>
                                    <TooltipTrigger asChild>
                                        <span>
                                            <Button variant="outline" disabled>
                                                Borrador
                                            </Button>
                                        </span>
                                    </TooltipTrigger>
                                    <TooltipContent>
                                        Disponible próximamente
                                    </TooltipContent>
                                </Tooltip>
                            )}
                        </div>

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
                                disabled={
                                    procesando || !pasosCompletos.identificacion
                                }
                                onClick={enviar}
                            >
                                {modo === 'crear'
                                    ? '✓ Registrar proveedor'
                                    : '✓ Guardar cambios'}
                            </Button>
                        </div>
                    </div>
                </div>

                <div className="flex flex-col gap-4 rounded-xl border p-4">
                    <div>
                        <p className="text-xs text-muted-foreground">Resumen</p>
                        <p className="text-xs text-muted-foreground">
                            Vista previa de la ficha
                        </p>
                    </div>

                    <div className="flex items-center gap-3">
                        <Avatar className="size-9 shrink-0">
                            <AvatarFallback className="bg-accent text-xs font-semibold text-accent-foreground">
                                {nombre.trim() ? getInitials(nombre) : '—'}
                            </AvatarFallback>
                        </Avatar>
                        <div className="min-w-0">
                            <div className="truncate font-medium">
                                {nombre.trim() || 'Sin nombre'}
                            </div>
                            <div className="truncate font-mono text-xs text-muted-foreground">
                                {rutproveedor.trim() || 'RUT —'}
                            </div>
                        </div>
                    </div>

                    <dl className="grid gap-2 text-sm">
                        <div className="flex items-center justify-between">
                            <dt className="text-muted-foreground">Correo</dt>
                            <dd className="truncate">{correo.trim() || '—'}</dd>
                        </div>
                        <div className="flex items-center justify-between">
                            <dt className="text-muted-foreground">
                                Condición de pago
                            </dt>
                            <dd>
                                {catalogos.condicionesPago.find(
                                    (o) => o.value === condicionPago,
                                )?.label ?? '—'}
                            </dd>
                        </div>
                        <div className="flex items-center justify-between">
                            <dt className="text-muted-foreground">Estado</dt>
                            <dd>
                                <Badge
                                    variant="outline"
                                    className={
                                        activo
                                            ? 'border-transparent bg-success-soft text-success'
                                            : 'border-transparent bg-danger-soft text-destructive'
                                    }
                                >
                                    {activo ? 'Activo' : 'Inactivo'}
                                </Badge>
                            </dd>
                        </div>
                    </dl>

                    <div className="border-t pt-4">
                        <div className="mb-2 flex items-center justify-between text-sm">
                            <span className="text-muted-foreground">
                                Completitud del registro
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
                                        {paso.key === 'clasificacion'
                                            ? 'Rubros seleccionados'
                                            : paso.key === 'identificacion'
                                              ? 'Identificación tributaria'
                                              : paso.key === 'contacto'
                                                ? 'Contacto comercial'
                                                : paso.label}
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
