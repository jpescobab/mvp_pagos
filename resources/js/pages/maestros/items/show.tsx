import { Head, Link, usePage } from '@inertiajs/react';
import { ClasificadorHijoSeccion } from '@/components/maestros/clasificador-hijo-seccion';
import { ItemStatusBadge } from '@/components/maestros/item-status-badge';
import { Button } from '@/components/ui/button';
import items from '@/routes/maestros/items';
import asignaciones from '@/routes/maestros/items/asignaciones';
import catalogos from '@/routes/maestros/items/catalogos';
import type { ItemPresupuestario } from '@/types/maestros';

type PageProps = {
    item: ItemPresupuestario;
};

export default function ItemsShow() {
    const { item } = usePage<PageProps>().props;

    return (
        <>
            <Head title={item.nombre} />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-xl font-semibold tracking-tight">
                        {item.nombre}
                    </h1>
                    <div className="flex items-center gap-2">
                        <Button asChild variant="outline">
                            <Link href={items.edit(item.id).url}>Editar</Link>
                        </Button>
                    </div>
                </div>

                <dl className="grid grid-cols-2 gap-4 rounded-xl border p-4 text-sm">
                    <div>
                        <dt className="text-muted-foreground">Código</dt>
                        <dd className="font-mono">{item.codigo}</dd>
                    </div>
                    <div>
                        <dt className="text-muted-foreground">Estado</dt>
                        <dd>
                            <ItemStatusBadge activo={item.activo} />
                        </dd>
                    </div>
                    <div className="col-span-2">
                        <dt className="text-muted-foreground">Descripción</dt>
                        <dd>{item.descripcion ?? '—'}</dd>
                    </div>
                </dl>

                <ClasificadorHijoSeccion
                    titulo="Asignaciones"
                    singular="asignación"
                    itemId={item.id}
                    filas={item.asignaciones ?? []}
                    rutas={asignaciones}
                />

                <ClasificadorHijoSeccion
                    titulo="Catálogos"
                    singular="catálogo"
                    itemId={item.id}
                    filas={item.catalogos ?? []}
                    rutas={catalogos}
                />
            </div>
        </>
    );
}

ItemsShow.layout = {
    breadcrumbs: [
        { title: 'Ítems Presupuestarios', href: items.index() },
        { title: 'Detalle', href: '#' },
    ],
};
