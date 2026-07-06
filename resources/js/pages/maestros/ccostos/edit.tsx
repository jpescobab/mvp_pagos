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
import ccostos from '@/routes/maestros/ccostos';
import type { Ccosto, CfinancieroSeleccionable } from '@/types/maestros';

type PageProps = {
    ccosto: Ccosto;
    cfinancieros: CfinancieroSeleccionable[];
};

export default function CcostosEditar() {
    const { ccosto, cfinancieros } = usePage<PageProps>().props;

    const [codigo, setCodigo] = useState(ccosto.codigo);
    const [nombre, setNombre] = useState(ccosto.nombre);
    const [cfinancieroId, setCfinancieroId] = useState(
        String(ccosto.cfinanciero.id),
    );
    const [codEdificio, setCodEdificio] = useState(ccosto.cod_edificio ?? '');
    const [activo, setActivo] = useState(ccosto.activo);
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [procesando, setProcesando] = useState(false);

    function enviar() {
        setProcesando(true);
        setErrors({});

        router.patch(
            ccostos.update(ccosto.id).url,
            {
                codigo,
                nombre,
                cfinanciero_id: cfinancieroId ? Number(cfinancieroId) : null,
                cod_edificio: codEdificio || null,
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
            <Head title={`Editar centro de costo — ${ccosto.nombre}`} />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <h1 className="text-xl font-semibold tracking-tight">
                    Editar centro de costo
                </h1>

                <div className="grid max-w-xl gap-4 rounded-xl border p-4">
                    <div className="grid gap-2">
                        <Label htmlFor="codigo">
                            Código
                            <span className="text-destructive">*</span>
                        </Label>
                        <Input
                            id="codigo"
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
                        <Label htmlFor="nombre">
                            Nombre
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
                        <Label htmlFor="cfinanciero_id">
                            Centro financiero
                            <span className="text-destructive">*</span>
                        </Label>
                        <Select
                            value={cfinancieroId}
                            onValueChange={setCfinancieroId}
                        >
                            <SelectTrigger
                                id="cfinanciero_id"
                                className="w-full"
                            >
                                <SelectValue placeholder="Selecciona un centro financiero" />
                            </SelectTrigger>
                            <SelectContent>
                                {cfinancieros.map((cfinanciero) => (
                                    <SelectItem
                                        key={cfinanciero.id}
                                        value={String(cfinanciero.id)}
                                    >
                                        {cfinanciero.codigo} ·{' '}
                                        {cfinanciero.nombre}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        {errors.cfinanciero_id && (
                            <p className="text-sm text-destructive">
                                {errors.cfinanciero_id}
                            </p>
                        )}
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="cod_edificio">Código de edificio</Label>
                        <Input
                            id="cod_edificio"
                            value={codEdificio}
                            onChange={(e) => setCodEdificio(e.target.value)}
                        />
                        {errors.cod_edificio && (
                            <p className="text-sm text-destructive">
                                {errors.cod_edificio}
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
                        onClick={() => router.get(ccostos.show(ccosto.id).url)}
                    >
                        Cancelar
                    </Button>
                </div>
            </div>
        </>
    );
}

CcostosEditar.layout = {
    breadcrumbs: [
        { title: 'Centros de Costos', href: ccostos.index() },
        { title: 'Editar', href: '#' },
    ],
};
