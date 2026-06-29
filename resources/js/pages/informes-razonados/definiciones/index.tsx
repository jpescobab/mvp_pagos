import { Head, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import definiciones from '@/routes/informes-razonados/definiciones';
import type { DefinicionInformeRazonado } from '@/types/informes-razonados';

type PageProps = {
    definiciones: DefinicionInformeRazonado[];
};

export default function DefinicionesInformeRazonadoIndex() {
    const { definiciones: lista } = usePage<PageProps>().props;

    const [codigo, setCodigo] = useState('');
    const [nombre, setNombre] = useState('');
    const [descripcion, setDescripcion] = useState('');
    const [procesando, setProcesando] = useState(false);
    const [error, setError] = useState<string | null>(null);

    function crear() {
        setProcesando(true);
        setError(null);

        router.post(
            definiciones.store().url,
            { codigo, nombre, descripcion: descripcion || null },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setCodigo('');
                    setNombre('');
                    setDescripcion('');
                },
                onError: (errors) =>
                    setError(
                        Object.values(errors as Record<string, string>)[0] ??
                            null,
                    ),
                onFinish: () => setProcesando(false),
            },
        );
    }

    return (
        <>
            <Head title="Definiciones de Informes Razonados" />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <h1 className="text-xl font-semibold tracking-tight">
                    Definiciones de Informes Razonados
                </h1>

                <section className="space-y-3 rounded-xl border p-4">
                    <h2 className="text-base font-medium">
                        Crear definición
                    </h2>

                    {error && (
                        <p className="text-sm text-destructive">{error}</p>
                    )}

                    <div className="flex flex-wrap items-end gap-2">
                        <div className="space-y-1">
                            <Label htmlFor="codigo-definicion">Código</Label>
                            <Input
                                id="codigo-definicion"
                                value={codigo}
                                onChange={(e) => setCodigo(e.target.value)}
                            />
                        </div>
                        <div className="space-y-1">
                            <Label htmlFor="nombre-definicion">Nombre</Label>
                            <Input
                                id="nombre-definicion"
                                value={nombre}
                                onChange={(e) => setNombre(e.target.value)}
                            />
                        </div>
                        <div className="space-y-1">
                            <Label htmlFor="descripcion-definicion">
                                Descripción
                            </Label>
                            <Input
                                id="descripcion-definicion"
                                value={descripcion}
                                onChange={(e) =>
                                    setDescripcion(e.target.value)
                                }
                            />
                        </div>
                        <Button
                            disabled={
                                procesando || codigo === '' || nombre === ''
                            }
                            onClick={crear}
                        >
                            Crear
                        </Button>
                    </div>
                </section>

                <div className="overflow-hidden rounded-xl border">
                    <table className="w-full text-sm">
                        <thead className="bg-muted/50 text-left text-muted-foreground">
                            <tr>
                                <th className="px-4 py-2 font-medium">
                                    Código
                                </th>
                                <th className="px-4 py-2 font-medium">
                                    Nombre
                                </th>
                                <th className="px-4 py-2 font-medium">
                                    Descripción
                                </th>
                                <th className="px-4 py-2 font-medium">
                                    Estado
                                </th>
                                <th className="px-4 py-2 font-medium">
                                    Ejecuciones
                                </th>
                            </tr>
                        </thead>
                        <tbody className="divide-y">
                            {lista.length === 0 && (
                                <tr>
                                    <td
                                        colSpan={5}
                                        className="px-4 py-6 text-center text-muted-foreground"
                                    >
                                        Sin definiciones todavía.
                                    </td>
                                </tr>
                            )}
                            {lista.map((definicion) => (
                                <tr key={definicion.id}>
                                    <td className="px-4 py-2 font-mono">
                                        {definicion.codigo}
                                    </td>
                                    <td className="px-4 py-2">
                                        {definicion.nombre}
                                    </td>
                                    <td className="px-4 py-2 text-muted-foreground">
                                        {definicion.descripcion}
                                    </td>
                                    <td className="px-4 py-2">
                                        {definicion.activo ? (
                                            <span className="text-green-600">
                                                Activo
                                            </span>
                                        ) : (
                                            <span className="text-muted-foreground">
                                                Inactivo
                                            </span>
                                        )}
                                    </td>
                                    <td className="px-4 py-2 text-muted-foreground">
                                        {definicion.ejecuciones_count}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>
        </>
    );
}

DefinicionesInformeRazonadoIndex.layout = {
    breadcrumbs: [
        {
            title: 'Definiciones de Informes Razonados',
            href: definiciones.index(),
        },
    ],
};
