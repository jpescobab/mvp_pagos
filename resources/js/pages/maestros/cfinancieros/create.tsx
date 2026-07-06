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
import cfinancieros from '@/routes/maestros/cfinancieros';
import type { JurisdiccionSeleccionable } from '@/types/maestros';

type PageProps = {
    jurisdicciones: JurisdiccionSeleccionable[];
};

export default function CfinancierosCrear() {
    const { jurisdicciones } = usePage<PageProps>().props;

    const [codigo, setCodigo] = useState('');
    const [nombre, setNombre] = useState('');
    const [jurisdiccionId, setJurisdiccionId] = useState('');
    const [activo, setActivo] = useState(true);
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [procesando, setProcesando] = useState(false);

    function enviar() {
        setProcesando(true);
        setErrors({});

        router.post(
            cfinancieros.store().url,
            {
                codigo,
                nombre,
                jurisdiccion_id: jurisdiccionId ? Number(jurisdiccionId) : null,
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
            <Head title="Nuevo centro financiero" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <h1 className="text-xl font-semibold tracking-tight">
                    Nuevo centro financiero
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
                        <Label htmlFor="jurisdiccion_id">
                            Jurisdicción
                            <span className="text-destructive">*</span>
                        </Label>
                        <Select
                            value={jurisdiccionId}
                            onValueChange={setJurisdiccionId}
                        >
                            <SelectTrigger
                                id="jurisdiccion_id"
                                className="w-full"
                            >
                                <SelectValue placeholder="Selecciona una jurisdicción" />
                            </SelectTrigger>
                            <SelectContent>
                                {jurisdicciones.map((jurisdiccion) => (
                                    <SelectItem
                                        key={jurisdiccion.id}
                                        value={String(jurisdiccion.id)}
                                    >
                                        {jurisdiccion.codigo} ·{' '}
                                        {jurisdiccion.nombre}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        {errors.jurisdiccion_id && (
                            <p className="text-sm text-destructive">
                                {errors.jurisdiccion_id}
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
                        Crear centro financiero
                    </Button>
                    <Button
                        variant="outline"
                        disabled={procesando}
                        onClick={() => router.get(cfinancieros.index().url)}
                    >
                        Cancelar
                    </Button>
                </div>
            </div>
        </>
    );
}

CfinancierosCrear.layout = {
    breadcrumbs: [
        { title: 'Centros Financieros', href: cfinancieros.index() },
        { title: 'Nuevo', href: cfinancieros.create() },
    ],
};
