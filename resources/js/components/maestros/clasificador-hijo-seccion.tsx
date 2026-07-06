import { router } from '@inertiajs/react';
import { useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import type { Asignacion, Catalogo } from '@/types/maestros';

type Fila = Asignacion | Catalogo;

type RouteModule = {
    store: (itemId: number) => { url: string };
    update: (args: [number, number]) => { url: string };
    destroy: (args: [number, number]) => { url: string };
};

type Borrador = {
    codigo: string;
    nombre: string;
    descripcion: string;
    activo: boolean;
};

const BORRADOR_VACIO: Borrador = {
    codigo: '',
    nombre: '',
    descripcion: '',
    activo: true,
};

export function ClasificadorHijoSeccion({
    titulo,
    singular,
    itemId,
    filas,
    rutas,
}: {
    titulo: string;
    singular: string;
    itemId: number;
    filas: Fila[];
    rutas: RouteModule;
}) {
    const [editandoId, setEditandoId] = useState<number | null>(null);
    const [borrador, setBorrador] = useState<Borrador>(BORRADOR_VACIO);
    const [nuevo, setNuevo] = useState<Borrador>(BORRADOR_VACIO);
    const [eliminando, setEliminando] = useState<Fila | null>(null);
    const [errores, setErrores] = useState<Record<string, string>>({});
    const [procesando, setProcesando] = useState(false);

    function crear() {
        setProcesando(true);
        setErrores({});

        router.post(
            rutas.store(itemId).url,
            {
                codigo: nuevo.codigo,
                nombre: nuevo.nombre,
                descripcion: nuevo.descripcion || null,
                activo: nuevo.activo,
            },
            {
                preserveScroll: true,
                onSuccess: () => setNuevo(BORRADOR_VACIO),
                onError: (errors) =>
                    setErrores(errors as Record<string, string>),
                onFinish: () => setProcesando(false),
            },
        );
    }

    function comenzarEdicion(fila: Fila) {
        setEditandoId(fila.id);
        setErrores({});
        setBorrador({
            codigo: fila.codigo,
            nombre: fila.nombre,
            descripcion: fila.descripcion ?? '',
            activo: fila.activo,
        });
    }

    function guardarEdicion() {
        if (editandoId === null) {
            return;
        }

        setProcesando(true);
        setErrores({});

        router.patch(
            rutas.update([itemId, editandoId]).url,
            {
                codigo: borrador.codigo,
                nombre: borrador.nombre,
                descripcion: borrador.descripcion || null,
                activo: borrador.activo,
            },
            {
                preserveScroll: true,
                onSuccess: () => setEditandoId(null),
                onError: (errors) =>
                    setErrores(errors as Record<string, string>),
                onFinish: () => setProcesando(false),
            },
        );
    }

    function eliminar() {
        if (eliminando === null) {
            return;
        }

        setProcesando(true);

        router.delete(rutas.destroy([itemId, eliminando.id]).url, {
            preserveScroll: true,
            onFinish: () => {
                setProcesando(false);
                setEliminando(null);
            },
        });
    }

    return (
        <section className="space-y-3 rounded-xl border p-4">
            <h2 className="text-base font-medium">{titulo}</h2>

            {filas.length === 0 ? (
                <p className="text-sm text-muted-foreground">
                    Sin {titulo.toLowerCase()} registradas todavía.
                </p>
            ) : (
                <ul className="divide-y text-sm">
                    {filas.map((fila) =>
                        editandoId === fila.id ? (
                            <li key={fila.id} className="space-y-2 py-2">
                                {errores.codigo && (
                                    <p className="text-sm text-destructive">
                                        {errores.codigo}
                                    </p>
                                )}
                                {errores.nombre && (
                                    <p className="text-sm text-destructive">
                                        {errores.nombre}
                                    </p>
                                )}
                                <div className="flex flex-wrap items-end gap-2">
                                    <div className="space-y-1">
                                        <Label>Código</Label>
                                        <Input
                                            value={borrador.codigo}
                                            onChange={(e) =>
                                                setBorrador((b) => ({
                                                    ...b,
                                                    codigo: e.target.value,
                                                }))
                                            }
                                        />
                                    </div>
                                    <div className="space-y-1">
                                        <Label>Nombre</Label>
                                        <Input
                                            value={borrador.nombre}
                                            onChange={(e) =>
                                                setBorrador((b) => ({
                                                    ...b,
                                                    nombre: e.target.value,
                                                }))
                                            }
                                        />
                                    </div>
                                    <div className="flex items-center gap-2">
                                        <Switch
                                            checked={borrador.activo}
                                            onCheckedChange={(activo) =>
                                                setBorrador((b) => ({
                                                    ...b,
                                                    activo,
                                                }))
                                            }
                                        />
                                        <Label>Activo</Label>
                                    </div>
                                </div>
                                <Textarea
                                    placeholder="Descripción"
                                    value={borrador.descripcion}
                                    onChange={(e) =>
                                        setBorrador((b) => ({
                                            ...b,
                                            descripcion: e.target.value,
                                        }))
                                    }
                                />
                                <div className="flex gap-2">
                                    <Button
                                        size="sm"
                                        disabled={procesando}
                                        onClick={guardarEdicion}
                                    >
                                        Guardar
                                    </Button>
                                    <Button
                                        size="sm"
                                        variant="outline"
                                        disabled={procesando}
                                        onClick={() => setEditandoId(null)}
                                    >
                                        Cancelar
                                    </Button>
                                </div>
                            </li>
                        ) : (
                            <li
                                key={fila.id}
                                className="flex items-center justify-between gap-2 py-2"
                            >
                                <div className="min-w-0">
                                    <div className="flex items-center gap-2">
                                        <span className="font-mono">
                                            {fila.codigo}
                                        </span>
                                        <span>{fila.nombre}</span>
                                        {!fila.activo && (
                                            <Badge
                                                variant="outline"
                                                className="border-transparent bg-danger-soft text-destructive"
                                            >
                                                Inactivo
                                            </Badge>
                                        )}
                                    </div>
                                    {fila.descripcion && (
                                        <p className="truncate text-muted-foreground">
                                            {fila.descripcion}
                                        </p>
                                    )}
                                </div>
                                <div className="flex shrink-0 gap-2">
                                    <Button
                                        size="sm"
                                        variant="outline"
                                        onClick={() => comenzarEdicion(fila)}
                                    >
                                        Editar
                                    </Button>
                                    <Button
                                        size="sm"
                                        variant="outline"
                                        onClick={() => setEliminando(fila)}
                                    >
                                        Eliminar
                                    </Button>
                                </div>
                            </li>
                        ),
                    )}
                </ul>
            )}

            <div className="flex flex-wrap items-end gap-2">
                <div className="space-y-1">
                    <Label htmlFor={`codigo-nuevo-${singular}`}>Código</Label>
                    <Input
                        id={`codigo-nuevo-${singular}`}
                        value={nuevo.codigo}
                        onChange={(e) =>
                            setNuevo((n) => ({
                                ...n,
                                codigo: e.target.value,
                            }))
                        }
                    />
                </div>
                <div className="space-y-1">
                    <Label htmlFor={`nombre-nuevo-${singular}`}>Nombre</Label>
                    <Input
                        id={`nombre-nuevo-${singular}`}
                        value={nuevo.nombre}
                        onChange={(e) =>
                            setNuevo((n) => ({
                                ...n,
                                nombre: e.target.value,
                            }))
                        }
                    />
                </div>
                <Button disabled={procesando} onClick={crear}>
                    Agregar {singular}
                </Button>
            </div>

            <Dialog
                open={eliminando !== null}
                onOpenChange={(open) => !open && setEliminando(null)}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Eliminar {singular}</DialogTitle>
                        <DialogDescription>
                            ¿Confirmas eliminar "{eliminando?.nombre}"? Esta
                            acción no se puede deshacer.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button
                            variant="outline"
                            onClick={() => setEliminando(null)}
                            disabled={procesando}
                        >
                            Cancelar
                        </Button>
                        <Button
                            variant="destructive"
                            onClick={eliminar}
                            disabled={procesando}
                        >
                            Eliminar
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </section>
    );
}
