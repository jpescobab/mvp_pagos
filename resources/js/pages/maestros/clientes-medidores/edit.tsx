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
import clientesMedidores from '@/routes/maestros/clientes-medidores';
import type {
    CcostoSeleccionable,
    ProveedorSeleccionable,
} from '@/types/adquisiciones';
import type { ClienteMedidor } from '@/types/maestros';

const SIN_PROVEEDOR = 'sin-proveedor';

type PageProps = {
    clienteMedidor: ClienteMedidor;
    ccostos: CcostoSeleccionable[];
    proveedores: ProveedorSeleccionable[];
};

export default function ClientesMedidoresEditar() {
    const { clienteMedidor, ccostos, proveedores } = usePage<PageProps>().props;

    const [numeroCliente, setNumeroCliente] = useState(
        clienteMedidor.numero_cliente,
    );
    const [ccostoId, setCcostoId] = useState(String(clienteMedidor.ccosto.id));
    const [proveedorId, setProveedorId] = useState(
        clienteMedidor.proveedor
            ? String(clienteMedidor.proveedor.id)
            : SIN_PROVEEDOR,
    );
    const [tipoSuministro, setTipoSuministro] = useState(
        clienteMedidor.tipo_suministro,
    );
    const [direccionSuministro, setDireccionSuministro] = useState(
        clienteMedidor.direccion_suministro ?? '',
    );
    const [activo, setActivo] = useState(clienteMedidor.activo);
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [procesando, setProcesando] = useState(false);

    function enviar() {
        setProcesando(true);
        setErrors({});

        router.patch(
            clientesMedidores.update(clienteMedidor.id).url,
            {
                numero_cliente: numeroCliente,
                ccosto_id: ccostoId ? Number(ccostoId) : null,
                proveedor_id:
                    proveedorId === SIN_PROVEEDOR ? null : Number(proveedorId),
                tipo_suministro: tipoSuministro,
                direccion_suministro: direccionSuministro || null,
                activo,
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
            <Head
                title={`Editar cliente medidor — ${clienteMedidor.numero_cliente}`}
            />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <h1 className="text-xl font-semibold tracking-tight">
                    Editar cliente medidor
                </h1>

                <div className="grid max-w-xl gap-4 rounded-xl border p-4">
                    <div className="grid gap-2">
                        <Label htmlFor="numero_cliente">
                            N.º de cliente
                            <span className="text-destructive">*</span>
                        </Label>
                        <Input
                            id="numero_cliente"
                            value={numeroCliente}
                            onChange={(e) => setNumeroCliente(e.target.value)}
                        />
                        {errors.numero_cliente && (
                            <p className="text-sm text-destructive">
                                {errors.numero_cliente}
                            </p>
                        )}
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="ccosto_id">
                            Centro de costo
                            <span className="text-destructive">*</span>
                        </Label>
                        <Select value={ccostoId} onValueChange={setCcostoId}>
                            <SelectTrigger id="ccosto_id" className="w-full">
                                <SelectValue placeholder="Selecciona un centro de costo" />
                            </SelectTrigger>
                            <SelectContent>
                                {ccostos.map((ccosto) => (
                                    <SelectItem
                                        key={ccosto.id}
                                        value={String(ccosto.id)}
                                    >
                                        {ccosto.codigo} · {ccosto.nombre}
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
                        <Label htmlFor="proveedor_id">
                            Proveedor de servicio
                        </Label>
                        <Select
                            value={proveedorId}
                            onValueChange={setProveedorId}
                        >
                            <SelectTrigger id="proveedor_id" className="w-full">
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

                    <div className="grid gap-2">
                        <Label htmlFor="tipo_suministro">
                            Tipo de suministro
                            <span className="text-destructive">*</span>
                        </Label>
                        <Input
                            id="tipo_suministro"
                            value={tipoSuministro}
                            onChange={(e) => setTipoSuministro(e.target.value)}
                        />
                        {errors.tipo_suministro && (
                            <p className="text-sm text-destructive">
                                {errors.tipo_suministro}
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
                        {errors.direccion_suministro && (
                            <p className="text-sm text-destructive">
                                {errors.direccion_suministro}
                            </p>
                        )}
                    </div>

                    <div className="flex items-center gap-2">
                        <Switch
                            id="activo"
                            checked={activo}
                            onCheckedChange={setActivo}
                        />
                        <Label htmlFor="activo">Activo</Label>
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
                            router.get(
                                clientesMedidores.show(clienteMedidor.id).url,
                            )
                        }
                    >
                        Cancelar
                    </Button>
                </div>
            </div>
        </>
    );
}

ClientesMedidoresEditar.layout = {
    breadcrumbs: [
        { title: 'Clientes Medidores', href: clientesMedidores.index() },
        { title: 'Editar', href: '#' },
    ],
};
