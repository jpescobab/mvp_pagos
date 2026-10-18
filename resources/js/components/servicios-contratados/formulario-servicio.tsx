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
import type {
    OpcionesFormularioServicio,
    ServicioContratado,
} from '@/types/servicios-contratados';

type Props = {
    opciones: OpcionesFormularioServicio;
    servicio?: ServicioContratado;
    errors: Record<string, string>;
    procesando: boolean;
    onEnviar: (datos: Record<string, unknown>) => void;
};

const SIN_VALOR = 'ninguno';

export function FormularioServicio({
    opciones,
    servicio,
    errors,
    procesando,
    onEnviar,
}: Props) {
    const [contratoId, setContratoId] = useState(
        servicio ? String(servicio.contrato.id) : '',
    );
    const [nombre, setNombre] = useState(servicio?.nombre ?? '');
    const [categoria, setCategoria] = useState(servicio?.categoria ?? '');
    const [monto, setMonto] = useState(servicio?.monto_mensual_esperado ?? '');
    const [moneda, setMoneda] = useState(servicio?.moneda ?? 'CLP');
    const [fechaInicio, setFechaInicio] = useState(
        servicio?.fecha_inicio ?? '',
    );
    const [fechaFin, setFechaFin] = useState(servicio?.fecha_fin ?? '');
    const [ccostoId, setCcostoId] = useState(
        servicio?.ccosto ? String(servicio.ccosto.id) : SIN_VALOR,
    );
    const [administradorId, setAdministradorId] = useState(
        servicio?.administrador ? String(servicio.administrador.id) : SIN_VALOR,
    );
    const [observaciones, setObservaciones] = useState(
        servicio?.observaciones ?? '',
    );

    const contrato = opciones.contratos.find(
        (c) => String(c.id) === contratoId,
    );

    function error(campo: string) {
        return (
            errors[campo] && (
                <p className="text-sm text-destructive">{errors[campo]}</p>
            )
        );
    }

    function enviar() {
        onEnviar({
            ...(servicio ? {} : { contrato_id: contratoId || null }),
            nombre,
            categoria,
            monto_mensual_esperado: monto,
            moneda,
            fecha_inicio: fechaInicio,
            fecha_fin: fechaFin,
            ccosto_id: ccostoId === SIN_VALOR ? null : ccostoId,
            administrador_funcionario_id:
                administradorId === SIN_VALOR ? null : administradorId,
            observaciones: observaciones || null,
        });
    }

    return (
        <div className="grid max-w-2xl gap-4 rounded-xl border p-4">
            <div className="grid gap-2">
                <Label>
                    Contrato aprobado<span className="text-destructive">*</span>
                </Label>
                {servicio ? (
                    <p className="text-sm">
                        {servicio.contrato.codigo} ·{' '}
                        {servicio.contrato.referencia}
                    </p>
                ) : (
                    <Select value={contratoId} onValueChange={setContratoId}>
                        <SelectTrigger>
                            <SelectValue placeholder="Selecciona un contrato" />
                        </SelectTrigger>
                        <SelectContent>
                            {opciones.contratos.map((c) => (
                                <SelectItem key={c.id} value={String(c.id)}>
                                    {c.codigo} ·{' '}
                                    {c.proveedor ?? 'Sin proveedor'}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                )}
                {contrato && (
                    <p className="text-xs text-muted-foreground">
                        Vigencia del contrato: {contrato.fecha_inicio_vigencia}{' '}
                        a {contrato.fecha_fin_vigencia}
                    </p>
                )}
                {error('contrato_id')}
            </div>

            <div className="grid grid-cols-2 gap-4">
                <div className="grid gap-2">
                    <Label htmlFor="nombre">
                        Nombre<span className="text-destructive">*</span>
                    </Label>
                    <Input
                        id="nombre"
                        value={nombre}
                        onChange={(e) => setNombre(e.target.value)}
                    />
                    {error('nombre')}
                </div>

                <div className="grid gap-2">
                    <Label>
                        Categoría<span className="text-destructive">*</span>
                    </Label>
                    <Select value={categoria} onValueChange={setCategoria}>
                        <SelectTrigger>
                            <SelectValue placeholder="Selecciona" />
                        </SelectTrigger>
                        <SelectContent>
                            {opciones.categorias.map((c) => (
                                <SelectItem key={c} value={c}>
                                    {c}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    {error('categoria')}
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="monto">
                        Monto mensual esperado
                        <span className="text-destructive">*</span>
                    </Label>
                    <Input
                        id="monto"
                        type="number"
                        value={monto}
                        onChange={(e) => setMonto(e.target.value)}
                    />
                    {error('monto_mensual_esperado')}
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="moneda">
                        Moneda<span className="text-destructive">*</span>
                    </Label>
                    <Input
                        id="moneda"
                        maxLength={3}
                        value={moneda}
                        onChange={(e) =>
                            setMoneda(e.target.value.toUpperCase())
                        }
                    />
                    {error('moneda')}
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="fecha_inicio">
                        Inicio<span className="text-destructive">*</span>
                    </Label>
                    <Input
                        id="fecha_inicio"
                        type="date"
                        value={fechaInicio}
                        onChange={(e) => setFechaInicio(e.target.value)}
                    />
                    {error('fecha_inicio')}
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="fecha_fin">
                        Fin<span className="text-destructive">*</span>
                    </Label>
                    <Input
                        id="fecha_fin"
                        type="date"
                        value={fechaFin}
                        onChange={(e) => setFechaFin(e.target.value)}
                    />
                    {error('fecha_fin')}
                </div>

                <div className="grid gap-2">
                    <Label>Centro de costo</Label>
                    <Select value={ccostoId} onValueChange={setCcostoId}>
                        <SelectTrigger>
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value={SIN_VALOR}>—</SelectItem>
                            {opciones.ccostos.map((c) => (
                                <SelectItem key={c.id} value={String(c.id)}>
                                    {c.codigo} · {c.nombre}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    {error('ccosto_id')}
                </div>

                <div className="grid gap-2">
                    <Label>Administrador del contrato</Label>
                    <Select
                        value={administradorId}
                        onValueChange={setAdministradorId}
                    >
                        <SelectTrigger>
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value={SIN_VALOR}>—</SelectItem>
                            {opciones.funcionarios.map((f) => (
                                <SelectItem key={f.id} value={String(f.id)}>
                                    {f.nombre}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    {error('administrador_funcionario_id')}
                </div>
            </div>

            <div className="grid gap-2">
                <Label htmlFor="observaciones">Observaciones</Label>
                <textarea
                    id="observaciones"
                    className="min-h-20 rounded-md border bg-background p-2 text-sm"
                    value={observaciones}
                    onChange={(e) => setObservaciones(e.target.value)}
                />
                {error('observaciones')}
            </div>

            <div>
                <Button disabled={procesando} onClick={enviar}>
                    Guardar
                </Button>
            </div>
        </div>
    );
}
