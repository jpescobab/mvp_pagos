import { Head, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import tiposCompra from '@/routes/maestros/tipos-compra';
import type { TipoCompraMaestro } from '@/types/maestros';

type PageProps = {
    tipoCompra: TipoCompraMaestro;
};

export default function TiposCompraEditar() {
    const { tipoCompra } = usePage<PageProps>().props;

    const [codigo, setCodigo] = useState(tipoCompra.codigo);
    const [nombre, setNombre] = useState(tipoCompra.nombre);
    const [activo, setActivo] = useState(tipoCompra.activo);
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [procesando, setProcesando] = useState(false);

    function enviar() {
        setProcesando(true);
        setErrors({});

        router.patch(
            tiposCompra.update(tipoCompra.id).url,
            { codigo, nombre, activo },
            {
                onError: (errores) =>
                    setErrors(errores as Record<string, string>),
                onFinish: () => setProcesando(false),
            },
        );
    }

    return (
        <>
            <Head title={`Editar tipo de compra — ${tipoCompra.nombre}`} />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <h1 className="text-xl font-semibold tracking-tight">
                    Editar tipo de compra
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
                            router.get(tiposCompra.show(tipoCompra.id).url)
                        }
                    >
                        Cancelar
                    </Button>
                </div>
            </div>
        </>
    );
}

TiposCompraEditar.layout = {
    breadcrumbs: [
        { title: 'Tipos de Compra', href: tiposCompra.index() },
        { title: 'Editar', href: '#' },
    ],
};
