import { Head, Link, usePage } from '@inertiajs/react';
import { Fragment, useState } from 'react';
import { Button } from '@/components/ui/button';
import auditoria from '@/routes/auditoria';
import type { Paginated } from '@/types/pago-proveedores';
import type { AuditLogEntry } from '@/types/seguridad';

type PageProps = {
    registros: Paginated<AuditLogEntry>;
};

export default function AuditoriaIndex() {
    const { registros: pagina } = usePage<PageProps>().props;
    const [expandidos, setExpandidos] = useState<Set<number>>(new Set());

    function alternarExpandido(id: number) {
        setExpandidos((actual) => {
            const siguiente = new Set(actual);

            if (siguiente.has(id)) {
                siguiente.delete(id);
            } else {
                siguiente.add(id);
            }

            return siguiente;
        });
    }

    return (
        <>
            <Head title="Auditoría" />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <h1 className="text-xl font-semibold tracking-tight">
                    Auditoría
                </h1>

                <div className="overflow-hidden rounded-xl border">
                    <table className="w-full text-sm">
                        <thead className="bg-muted/50 text-left text-muted-foreground">
                            <tr>
                                <th className="px-4 py-2 font-medium">
                                    Fecha
                                </th>
                                <th className="px-4 py-2 font-medium">
                                    Usuario
                                </th>
                                <th className="px-4 py-2 font-medium">
                                    Acción
                                </th>
                                <th className="px-4 py-2 font-medium">
                                    Entidad afectada
                                </th>
                                <th className="px-4 py-2 font-medium"></th>
                            </tr>
                        </thead>
                        <tbody className="divide-y">
                            {pagina.data.length === 0 && (
                                <tr>
                                    <td
                                        colSpan={5}
                                        className="px-4 py-6 text-center text-muted-foreground"
                                    >
                                        Sin registros de auditoría todavía.
                                    </td>
                                </tr>
                            )}
                            {pagina.data.map((registro) => (
                                <Fragment key={registro.id}>
                                    <tr>
                                        <td className="px-4 py-2 font-mono text-xs">
                                            {new Date(
                                                registro.created_at,
                                            ).toLocaleString()}
                                        </td>
                                        <td className="px-4 py-2">
                                            {registro.user ?? 'Sistema'}
                                        </td>
                                        <td className="px-4 py-2 font-mono text-xs">
                                            {registro.action}
                                        </td>
                                        <td className="px-4 py-2 text-muted-foreground">
                                            {registro.auditable_type
                                                ? `${registro.auditable_type.replace('App\\Models\\', '')} #${registro.auditable_id}`
                                                : '—'}
                                        </td>
                                        <td className="px-4 py-2 text-right">
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                onClick={() =>
                                                    alternarExpandido(
                                                        registro.id,
                                                    )
                                                }
                                            >
                                                {expandidos.has(registro.id)
                                                    ? 'Ocultar'
                                                    : 'Ver detalle'}
                                            </Button>
                                        </td>
                                    </tr>
                                    {expandidos.has(registro.id) && (
                                        <tr>
                                            <td
                                                colSpan={5}
                                                className="bg-muted/30 px-4 py-3"
                                            >
                                                <pre className="overflow-x-auto text-xs">
                                                    {JSON.stringify(
                                                        {
                                                            before:
                                                                registro.before,
                                                            after: registro.after,
                                                            metadata:
                                                                registro.metadata,
                                                        },
                                                        null,
                                                        2,
                                                    )}
                                                </pre>
                                            </td>
                                        </tr>
                                    )}
                                </Fragment>
                            ))}
                        </tbody>
                    </table>
                </div>

                <div className="flex items-center justify-between text-sm text-muted-foreground">
                    <span>
                        Mostrando {pagina.meta.from ?? 0}–{pagina.meta.to ?? 0}{' '}
                        de {pagina.meta.total}
                    </span>
                    <div className="flex gap-2">
                        <Link
                            href={pagina.links.prev ?? '#'}
                            className={
                                pagina.links.prev
                                    ? 'underline'
                                    : 'pointer-events-none opacity-50'
                            }
                        >
                            Anterior
                        </Link>
                        <Link
                            href={pagina.links.next ?? '#'}
                            className={
                                pagina.links.next
                                    ? 'underline'
                                    : 'pointer-events-none opacity-50'
                            }
                        >
                            Siguiente
                        </Link>
                    </div>
                </div>
            </div>
        </>
    );
}

AuditoriaIndex.layout = {
    breadcrumbs: [
        {
            title: 'Auditoría',
            href: auditoria.index(),
        },
    ],
};
