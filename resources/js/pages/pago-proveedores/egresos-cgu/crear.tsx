import { Head, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import egresosCgu from '@/routes/pago-proveedores/egresos-cgu';
import type { CasoSeleccionable } from '@/types/pago-proveedores';

type PageProps = {
    casos: CasoSeleccionable[];
};

export default function EgresosCguCrear() {
    const { casos } = usePage<PageProps>().props;

    const [numeroEgreso, setNumeroEgreso] = useState('');
    const [fecha, setFecha] = useState('');
    const [observaciones, setObservaciones] = useState('');
    const [seleccion, setSeleccion] = useState<Record<number, string>>({});
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [procesando, setProcesando] = useState(false);

    function alternarCaso(caso: CasoSeleccionable, marcado: boolean) {
        setSeleccion((actual) => {
            const siguiente = { ...actual };

            if (marcado) {
                siguiente[caso.id] = caso.monto;
            } else {
                delete siguiente[caso.id];
            }

            return siguiente;
        });
    }

    function enviar() {
        setProcesando(true);
        setErrors({});

        router.post(
            egresosCgu.store().url,
            {
                numero_egreso: numeroEgreso,
                fecha,
                observaciones: observaciones || null,
                casos: Object.entries(seleccion).map(([casoId, monto]) => ({
                    caso_pago_proveedor_id: Number(casoId),
                    monto,
                })),
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
            <Head title="Nuevo egreso CGU" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <h1 className="text-xl font-semibold tracking-tight">
                    Nuevo egreso CGU
                </h1>

                <div className="grid max-w-xl gap-4">
                    <div className="grid gap-2">
                        <Label htmlFor="numero_egreso">
                            N° de egreso
                            <span className="text-destructive">*</span>
                        </Label>
                        <Input
                            id="numero_egreso"
                            className="font-mono"
                            value={numeroEgreso}
                            onChange={(e) => setNumeroEgreso(e.target.value)}
                        />
                        {errors.numero_egreso && (
                            <p className="text-sm text-destructive">
                                {errors.numero_egreso}
                            </p>
                        )}
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="fecha">
                            Fecha<span className="text-destructive">*</span>
                        </Label>
                        <Input
                            id="fecha"
                            type="date"
                            value={fecha}
                            onChange={(e) => setFecha(e.target.value)}
                        />
                        {errors.fecha && (
                            <p className="text-sm text-destructive">
                                {errors.fecha}
                            </p>
                        )}
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="observaciones">Observaciones</Label>
                        <textarea
                            id="observaciones"
                            className="min-h-20 rounded-md border bg-background p-2 text-sm"
                            value={observaciones}
                            onChange={(e) => setObservaciones(e.target.value)}
                        />
                    </div>
                </div>

                <section className="space-y-3 rounded-xl border p-4">
                    <h2 className="text-base font-medium">
                        Casos a cubrir
                        <span className="text-destructive">*</span>
                    </h2>

                    {errors.casos && (
                        <p className="text-sm text-destructive">
                            {errors.casos}
                        </p>
                    )}

                    {casos.length === 0 ? (
                        <p className="text-sm text-muted-foreground">
                            No hay casos de pago de proveedores disponibles.
                        </p>
                    ) : (
                        <ul className="divide-y">
                            {casos.map((caso) => (
                                <li
                                    key={caso.id}
                                    className="flex flex-wrap items-center gap-4 py-2"
                                >
                                    <Checkbox
                                        checked={
                                            seleccion[caso.id] !== undefined
                                        }
                                        onCheckedChange={(marcado) =>
                                            alternarCaso(caso, marcado === true)
                                        }
                                    />
                                    <span className="min-w-48 flex-1 text-sm">
                                        {caso.proveedor.nombre ?? caso.sgf_id}{' '}
                                        <span className="font-mono text-xs text-muted-foreground">
                                            {caso.sgf_id}
                                        </span>
                                    </span>
                                    <Input
                                        type="number"
                                        step="0.01"
                                        className="w-40"
                                        disabled={
                                            seleccion[caso.id] === undefined
                                        }
                                        value={seleccion[caso.id] ?? ''}
                                        onChange={(e) =>
                                            setSeleccion((actual) => ({
                                                ...actual,
                                                [caso.id]: e.target.value,
                                            }))
                                        }
                                    />
                                </li>
                            ))}
                        </ul>
                    )}
                </section>

                <div>
                    <Button disabled={procesando} onClick={enviar}>
                        Crear egreso
                    </Button>
                </div>
            </div>
        </>
    );
}

EgresosCguCrear.layout = {
    breadcrumbs: [
        { title: 'Egresos CGU', href: egresosCgu.index() },
        { title: 'Nuevo', href: egresosCgu.create() },
    ],
};
