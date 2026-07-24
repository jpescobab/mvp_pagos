import { Head, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import definiciones from '@/routes/informes-razonados/definiciones';
import type { DefinicionInformeRazonado } from '@/types/informes-razonados';

type PageProps = {
    definicion: DefinicionInformeRazonado;
};

export default function DefinicionInformeRazonadoEditar() {
    const { definicion } = usePage<PageProps>().props;

    const [codigo, setCodigo] = useState(definicion.codigo);
    const [nombre, setNombre] = useState(definicion.nombre);
    const [descripcion, setDescripcion] = useState(definicion.descripcion ?? '');
    const [activo, setActivo] = useState(definicion.activo);
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [procesando, setProcesando] = useState(false);

    function enviar() {
        setProcesando(true);
        setErrors({});

        router.patch(
            definiciones.update(definicion.id).url,
            {
                codigo,
                nombre,
                descripcion: descripcion === '' ? null : descripcion,
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
            <Head title={`Editar definición — ${definicion.nombre}`} />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <h1 className="text-xl font-semibold tracking-tight">
                    Editar definición de informe razonado
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
                        <Label htmlFor="descripcion">Descripción</Label>
                        <Textarea
                            id="descripcion"
                            value={descripcion}
                            onChange={(e) => setDescripcion(e.target.value)}
                        />
                        {errors.descripcion && (
                            <p className="text-sm text-destructive">
                                {errors.descripcion}
                            </p>
                        )}
                    </div>

                    <div className="flex items-center gap-2">
                        <Switch
                            id="activo"
                            checked={activo}
                            onCheckedChange={setActivo}
                        />
                        <Label htmlFor="activo">Activa</Label>
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
                            router.get(definiciones.show(definicion.id).url)
                        }
                    >
                        Cancelar
                    </Button>
                </div>
            </div>
        </>
    );
}

DefinicionInformeRazonadoEditar.layout = {
    breadcrumbs: [
        {
            title: 'Definiciones de Informes Razonados',
            href: definiciones.index(),
        },
        { title: 'Editar', href: '#' },
    ],
};
