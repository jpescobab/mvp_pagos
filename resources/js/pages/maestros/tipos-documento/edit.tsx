import { Head, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import tiposDocumento from '@/routes/maestros/tipos-documento';
import type { TipoDocumentoMaestro } from '@/types/maestros';

type PageProps = {
    tipoDocumento: TipoDocumentoMaestro;
};

export default function TiposDocumentoEditar() {
    const { tipoDocumento } = usePage<PageProps>().props;

    const [codigo, setCodigo] = useState(tipoDocumento.codigo);
    const [nombre, setNombre] = useState(tipoDocumento.nombre);
    const [descripcion, setDescripcion] = useState(
        tipoDocumento.descripcion ?? '',
    );
    const [activo, setActivo] = useState(tipoDocumento.activo);
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [procesando, setProcesando] = useState(false);

    function enviar() {
        setProcesando(true);
        setErrors({});

        router.patch(
            tiposDocumento.update(tipoDocumento.id).url,
            { codigo, nombre, descripcion: descripcion || null, activo },
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
                title={`Editar tipo de documento — ${tipoDocumento.nombre}`}
            />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <h1 className="text-xl font-semibold tracking-tight">
                    Editar tipo de documento
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
                        <Input
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
                                tiposDocumento.show(tipoDocumento.id).url,
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

TiposDocumentoEditar.layout = {
    breadcrumbs: [
        { title: 'Tipos de Documento', href: tiposDocumento.index() },
        { title: 'Editar', href: '#' },
    ],
};
