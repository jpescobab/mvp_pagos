import { Link } from '@inertiajs/react';
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

export type ContratoFormValues = {
    id_institucional: string;
    modalidad_compra: 'licitacion' | 'trato_directo' | 'fuera_de_portal';
    id_proceso_mp: string;
    tipo_contrato: 'contrato' | 'convenio_precio' | 'orden_compra' | 'arriendo';
    referencia: string;
    fecha_inicio_vigencia: string;
    fecha_fin_vigencia: string;
    materia: string;
    submateria: string;
    tiene_convenio_precio: boolean;
    tiene_calendario_pago: boolean;
    periodicidad_pago:
        | 'mensual'
        | 'bimestral'
        | 'trimestral'
        | 'semestral'
        | 'anual'
        | 'unica'
        | '';
    monto_total: string;
    proveedor_rutproveedor: string;
    proveedor_nombre: string;
};

export const CONTRATO_FORM_VALORES_INICIALES: ContratoFormValues = {
    id_institucional: '',
    modalidad_compra: 'licitacion',
    id_proceso_mp: '',
    tipo_contrato: 'contrato',
    referencia: '',
    fecha_inicio_vigencia: '',
    fecha_fin_vigencia: '',
    materia: '',
    submateria: '',
    tiene_convenio_precio: false,
    tiene_calendario_pago: false,
    periodicidad_pago: '',
    monto_total: '',
    proveedor_rutproveedor: '',
    proveedor_nombre: '',
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

type ContratoFormProps = {
    valores: ContratoFormValues;
    onChange: (valores: ContratoFormValues) => void;
    errors: Record<string, string>;
    procesando: boolean;
    onSubmit: () => void;
    textoBoton: string;
    hrefCancelar: string;
    modoEdicion?: boolean;
};

export function ContratoForm({
    valores,
    onChange,
    errors,
    procesando,
    onSubmit,
    textoBoton,
    hrefCancelar,
    modoEdicion,
}: ContratoFormProps) {
    function set<K extends keyof ContratoFormValues>(
        campo: K,
        valor: ContratoFormValues[K],
    ) {
        onChange({ ...valores, [campo]: valor });
    }

    const camposIncompletos =
        !valores.id_institucional ||
        !valores.referencia ||
        !valores.fecha_inicio_vigencia ||
        !valores.fecha_fin_vigencia ||
        (!modoEdicion && !valores.proveedor_rutproveedor);

    return (
        <div className="grid max-w-3xl gap-6">
            <SeccionCard numero={1} titulo="Identificación del contrato">
                <div className="grid gap-2">
                    <Label htmlFor="id_institucional">
                        ID institucional
                        <span className="text-destructive">*</span>
                    </Label>
                    <Input
                        id="id_institucional"
                        type="number"
                        value={valores.id_institucional}
                        onChange={(e) =>
                            set('id_institucional', e.target.value)
                        }
                    />
                    {errors.id_institucional && (
                        <p className="text-sm text-destructive">
                            {errors.id_institucional}
                        </p>
                    )}
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="id_proceso_mp">
                        ID proceso Mercado Público
                    </Label>
                    <Input
                        id="id_proceso_mp"
                        placeholder="Ej: 2182-5-LE26"
                        value={valores.id_proceso_mp}
                        onChange={(e) => set('id_proceso_mp', e.target.value)}
                    />
                    {errors.id_proceso_mp && (
                        <p className="text-sm text-destructive">
                            {errors.id_proceso_mp}
                        </p>
                    )}
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="modalidad_compra">
                        Modalidad de compra
                        <span className="text-destructive">*</span>
                    </Label>
                    <Select
                        value={valores.modalidad_compra}
                        onValueChange={(v) =>
                            set(
                                'modalidad_compra',
                                v as ContratoFormValues['modalidad_compra'],
                            )
                        }
                    >
                        <SelectTrigger id="modalidad_compra" className="w-full">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="licitacion">
                                Licitación
                            </SelectItem>
                            <SelectItem value="trato_directo">
                                Trato directo
                            </SelectItem>
                            <SelectItem value="fuera_de_portal">
                                Fuera de portal
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    {errors.modalidad_compra && (
                        <p className="text-sm text-destructive">
                            {errors.modalidad_compra}
                        </p>
                    )}
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="tipo_contrato">
                        Tipo de contrato
                        <span className="text-destructive">*</span>
                    </Label>
                    <Select
                        value={valores.tipo_contrato}
                        onValueChange={(v) =>
                            set(
                                'tipo_contrato',
                                v as ContratoFormValues['tipo_contrato'],
                            )
                        }
                    >
                        <SelectTrigger id="tipo_contrato" className="w-full">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="contrato">Contrato</SelectItem>
                            <SelectItem value="convenio_precio">
                                Convenio de precio
                            </SelectItem>
                            <SelectItem value="orden_compra">
                                Orden de compra
                            </SelectItem>
                            <SelectItem value="arriendo">Arriendo</SelectItem>
                        </SelectContent>
                    </Select>
                    {errors.tipo_contrato && (
                        <p className="text-sm text-destructive">
                            {errors.tipo_contrato}
                        </p>
                    )}
                </div>

                <div className="col-span-2 grid gap-2">
                    <Label htmlFor="referencia">
                        Referencia
                        <span className="text-destructive">*</span>
                    </Label>
                    <textarea
                        id="referencia"
                        className="min-h-20 rounded-md border bg-background p-2 text-sm"
                        value={valores.referencia}
                        onChange={(e) => set('referencia', e.target.value)}
                    />
                    {errors.referencia && (
                        <p className="text-sm text-destructive">
                            {errors.referencia}
                        </p>
                    )}
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="materia">Materia</Label>
                    <Input
                        id="materia"
                        value={valores.materia}
                        onChange={(e) => set('materia', e.target.value)}
                    />
                    {errors.materia && (
                        <p className="text-sm text-destructive">
                            {errors.materia}
                        </p>
                    )}
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="submateria">Submateria</Label>
                    <Input
                        id="submateria"
                        value={valores.submateria}
                        onChange={(e) => set('submateria', e.target.value)}
                    />
                    {errors.submateria && (
                        <p className="text-sm text-destructive">
                            {errors.submateria}
                        </p>
                    )}
                </div>
            </SeccionCard>

            <SeccionCard numero={2} titulo="Vigencia">
                <div className="grid gap-2">
                    <Label htmlFor="fecha_inicio_vigencia">
                        Inicio de vigencia
                        <span className="text-destructive">*</span>
                    </Label>
                    <Input
                        id="fecha_inicio_vigencia"
                        type="date"
                        value={valores.fecha_inicio_vigencia}
                        onChange={(e) =>
                            set('fecha_inicio_vigencia', e.target.value)
                        }
                    />
                    {errors.fecha_inicio_vigencia && (
                        <p className="text-sm text-destructive">
                            {errors.fecha_inicio_vigencia}
                        </p>
                    )}
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="fecha_fin_vigencia">
                        Fin de vigencia
                        <span className="text-destructive">*</span>
                    </Label>
                    <Input
                        id="fecha_fin_vigencia"
                        type="date"
                        value={valores.fecha_fin_vigencia}
                        onChange={(e) =>
                            set('fecha_fin_vigencia', e.target.value)
                        }
                    />
                    {errors.fecha_fin_vigencia && (
                        <p className="text-sm text-destructive">
                            {errors.fecha_fin_vigencia}
                        </p>
                    )}
                </div>
            </SeccionCard>

            {!modoEdicion && (
                <SeccionCard numero={3} titulo="Proveedor">
                    <div className="grid gap-2">
                        <Label htmlFor="proveedor_rutproveedor">
                            RUT del proveedor
                            <span className="text-destructive">*</span>
                        </Label>
                        <Input
                            id="proveedor_rutproveedor"
                            placeholder="Ej: 77634019-7"
                            value={valores.proveedor_rutproveedor}
                            onChange={(e) =>
                                set('proveedor_rutproveedor', e.target.value)
                            }
                        />
                        {errors.proveedor && (
                            <p className="text-sm text-destructive">
                                {errors.proveedor}
                            </p>
                        )}
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="proveedor_nombre">
                            Nombre del proveedor
                        </Label>
                        <Input
                            id="proveedor_nombre"
                            value={valores.proveedor_nombre}
                            onChange={(e) =>
                                set('proveedor_nombre', e.target.value)
                            }
                        />
                    </div>
                </SeccionCard>
            )}

            <SeccionCard
                numero={modoEdicion ? 3 : 4}
                titulo="Convenio de precio"
            >
                <div className="col-span-2 flex items-center gap-2">
                    <input
                        id="tiene_convenio_precio"
                        type="checkbox"
                        className="size-4 cursor-pointer accent-primary"
                        checked={valores.tiene_convenio_precio}
                        onChange={(e) =>
                            set('tiene_convenio_precio', e.target.checked)
                        }
                    />
                    <Label
                        htmlFor="tiene_convenio_precio"
                        className="cursor-pointer"
                    >
                        Este contrato tiene convenio de precio
                    </Label>
                </div>
                {valores.tiene_convenio_precio && (
                    <p className="col-span-2 text-sm text-muted-foreground">
                        Los ítems del convenio de precio se agregan desde el
                        detalle del contrato, una vez creado.
                    </p>
                )}
            </SeccionCard>

            <SeccionCard
                numero={modoEdicion ? 4 : 5}
                titulo="Calendario de pago"
            >
                <div className="col-span-2 flex items-center gap-2">
                    <input
                        id="tiene_calendario_pago"
                        type="checkbox"
                        className="size-4 cursor-pointer accent-primary"
                        checked={valores.tiene_calendario_pago}
                        onChange={(e) =>
                            set('tiene_calendario_pago', e.target.checked)
                        }
                    />
                    <Label
                        htmlFor="tiene_calendario_pago"
                        className="cursor-pointer"
                    >
                        Este contrato se paga en cuotas periódicas
                    </Label>
                </div>

                {valores.tiene_calendario_pago && (
                    <>
                        <div className="grid gap-2">
                            <Label htmlFor="periodicidad_pago">
                                Periodicidad
                                <span className="text-destructive">*</span>
                            </Label>
                            <Select
                                value={valores.periodicidad_pago}
                                onValueChange={(v) =>
                                    set(
                                        'periodicidad_pago',
                                        v as ContratoFormValues['periodicidad_pago'],
                                    )
                                }
                            >
                                <SelectTrigger
                                    id="periodicidad_pago"
                                    className="w-full"
                                >
                                    <SelectValue placeholder="Selecciona una periodicidad" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="mensual">
                                        Mensual
                                    </SelectItem>
                                    <SelectItem value="bimestral">
                                        Bimestral
                                    </SelectItem>
                                    <SelectItem value="trimestral">
                                        Trimestral
                                    </SelectItem>
                                    <SelectItem value="semestral">
                                        Semestral
                                    </SelectItem>
                                    <SelectItem value="anual">Anual</SelectItem>
                                    <SelectItem value="unica">Única</SelectItem>
                                </SelectContent>
                            </Select>
                            {errors.periodicidad_pago && (
                                <p className="text-sm text-destructive">
                                    {errors.periodicidad_pago}
                                </p>
                            )}
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="monto_total">Monto total</Label>
                            <Input
                                id="monto_total"
                                type="number"
                                step="0.01"
                                min="0"
                                value={valores.monto_total}
                                onChange={(e) =>
                                    set('monto_total', e.target.value)
                                }
                            />
                            {errors.monto_total && (
                                <p className="text-sm text-destructive">
                                    {errors.monto_total}
                                </p>
                            )}
                        </div>

                        <p className="col-span-2 text-sm text-muted-foreground">
                            El calendario de cuotas se genera desde el detalle
                            del contrato, una vez creado.
                        </p>
                    </>
                )}
            </SeccionCard>

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
