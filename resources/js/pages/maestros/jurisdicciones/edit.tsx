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
import jurisdicciones from '@/routes/maestros/jurisdicciones';
import type { InstitucionSeleccionable, Jurisdiccion } from '@/types/maestros';

type PageProps = {
    jurisdiccion: Jurisdiccion;
    instituciones: InstitucionSeleccionable[];
};

export default function JurisdiccionesEditar() {
    const { jurisdiccion, instituciones } = usePage<PageProps>().props;

    const [institucionId, setInstitucionId] = useState(
        String(jurisdiccion.institucion.id),
    );
    const [codigo, setCodigo] = useState(jurisdiccion.codigo);
    const [nombre, setNombre] = useState(jurisdiccion.nombre);
    const [descripcion, setDescripcion] = useState(
        jurisdiccion.descripcion ?? '',
    );
    const [activo, setActivo] = useState(jurisdiccion.activo);
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [procesando, setProcesando] = useState(false);

    function enviar() {
        setProcesando(true);
        setErrors({});

        router.patch(
            jurisdicciones.update(jurisdiccion.id).url,
            {
                institucion_id: institucionId ? Number(institucionId) : null,
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
            <Head title={`Editar jurisdicción — ${jurisdiccion.nombre}`} />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <h1 className="text-xl font-semibold tracking-tight">
                    Editar jurisdicción
                </h1>

                <div className="grid max-w-xl gap-4 rounded-xl border p-4">
                    <div className="grid gap-2">
                        <Label htmlFor="institucion_id">
                            Institución
                            <span className="text-destructive">*</span>
                        </Label>
                        <Select
                            value={institucionId}
                            onValueChange={setInstitucionId}
                        >
                            <SelectTrigger
                                id="institucion_id"
                                className="w-full"
                            >
                                <SelectValue placeholder="Selecciona una institución" />
                            </SelectTrigger>
                            <SelectContent>
                                {instituciones.map((institucion) => (
                                    <SelectItem
                                        key={institucion.id}
                                        value={String(institucion.id)}
                                    >
                                        {institucion.codigo} ·{' '}
                                        {institucion.nombre}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        {errors.institucion_id && (
                            <p className="text-sm text-destructive">
                                {errors.institucion_id}
                            </p>
                        )}
                    </div>

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
                            router.get(jurisdicciones.show(jurisdiccion.id).url)
                        }
                    >
                        Cancelar
                    </Button>
                </div>
            </div>
        </>
    );
}

JurisdiccionesEditar.layout = {
    breadcrumbs: [
        { title: 'Jurisdicciones', href: jurisdicciones.index() },
        { title: 'Editar', href: '#' },
    ],
};
