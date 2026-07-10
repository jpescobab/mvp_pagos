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
import procesos from '@/routes/adquisiciones/procesos';
import type {
    CcostoSeleccionable,
    ModalidadSeleccionable,
    ProveedorSeleccionable,
} from '@/types/adquisiciones';

const SIN_PROVEEDOR = 'sin-proveedor';

type PageProps = {
    modalidades: ModalidadSeleccionable[];
    ccostos: CcostoSeleccionable[];
    proveedores: ProveedorSeleccionable[];
};

export default function ProcesosCrear() {
    const { modalidades, ccostos, proveedores } = usePage<PageProps>().props;

    const [codigo, setCodigo] = useState('');
    const [modalidadId, setModalidadId] = useState('');
    const [ccostoId, setCcostoId] = useState('');
    const [proveedorId, setProveedorId] = useState(SIN_PROVEEDOR);
    const [monto, setMonto] = useState('');
    const [objeto, setObjeto] = useState('');
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [procesando, setProcesando] = useState(false);

    function enviar() {
        setProcesando(true);
        setErrors({});

        router.post(
            procesos.store().url,
            {
                codigo,
                modalidad_id: modalidadId ? Number(modalidadId) : null,
                ccosto_id: ccostoId ? Number(ccostoId) : null,
                proveedor_id:
                    proveedorId === SIN_PROVEEDOR ? null : Number(proveedorId),
                monto: monto || null,
                objeto,
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
            <Head title="Nuevo proceso de adquisición" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <h1 className="text-xl font-semibold tracking-tight">
                    Nuevo proceso de adquisición
                </h1>

                <div className="grid max-w-xl gap-4">
                    <div className="grid gap-2">
                        <Label htmlFor="codigo">
                            Código<span className="text-destructive">*</span>
                        </Label>
                        <Input
                            id="codigo"
                            className="font-mono"
                            value={codigo}
                            onChange={(e) => setCodigo(e.target.value)}
                        />
                        {errors.codigo && (
                            <p className="text-sm text-destructive">
                                {errors.codigo}
                            </p>
                        )}
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="modalidad_id">
                            Modalidad
                            <span className="text-destructive">*</span>
                        </Label>
                        <Select
                            value={modalidadId}
                            onValueChange={setModalidadId}
                        >
                            <SelectTrigger id="modalidad_id" className="w-full">
                                <SelectValue placeholder="Selecciona una modalidad" />
                            </SelectTrigger>
                            <SelectContent>
                                {modalidades.map((modalidad) => (
                                    <SelectItem
                                        key={modalidad.id}
                                        value={String(modalidad.id)}
                                    >
                                        {modalidad.nombre}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        {errors.modalidad_id && (
                            <p className="text-sm text-destructive">
                                {errors.modalidad_id}
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
                        <Label htmlFor="proveedor_id">Proveedor</Label>
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
                        <Label htmlFor="monto">Monto</Label>
                        <Input
                            id="monto"
                            type="number"
                            step="0.01"
                            value={monto}
                            onChange={(e) => setMonto(e.target.value)}
                        />
                        {errors.monto && (
                            <p className="text-sm text-destructive">
                                {errors.monto}
                            </p>
                        )}
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="objeto">
                            Objeto<span className="text-destructive">*</span>
                        </Label>
                        <textarea
                            id="objeto"
                            className="min-h-20 rounded-md border bg-background p-2 text-sm"
                            value={objeto}
                            onChange={(e) => setObjeto(e.target.value)}
                        />
                        {errors.objeto && (
                            <p className="text-sm text-destructive">
                                {errors.objeto}
                            </p>
                        )}
                    </div>
                </div>

                <div>
                    <Button disabled={procesando} onClick={enviar}>
                        Crear proceso
                    </Button>
                </div>
            </div>
        </>
    );
}

ProcesosCrear.layout = {
    breadcrumbs: [
        { title: 'Procesos de adquisición', href: procesos.index() },
        { title: 'Nuevo', href: procesos.create() },
    ],
};
