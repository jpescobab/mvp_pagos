import { Head, usePage } from '@inertiajs/react';
import definiciones from '@/routes/workflow/definiciones';
import type { DefinicionWorkflow } from '@/types/workflow';

type PageProps = {
    definicion: DefinicionWorkflow;
};

export default function DefinicionWorkflowShow() {
    const { definicion } = usePage<PageProps>().props;

    return (
        <>
            <Head title={`Workflow: ${definicion.nombre}`} />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div>
                    <h1 className="text-xl font-semibold tracking-tight">
                        {definicion.nombre}
                    </h1>
                    <p className="font-mono text-sm text-muted-foreground">
                        código: {definicion.codigo}
                        {' · '}
                        {definicion.activo ? 'Activo' : 'Inactivo'}
                    </p>
                    {definicion.descripcion && (
                        <p className="mt-1 text-sm text-muted-foreground">
                            {definicion.descripcion}
                        </p>
                    )}
                </div>

                <section className="space-y-3 rounded-xl border p-4">
                    <h2 className="text-base font-medium">Estados</h2>

                    <ul className="divide-y text-sm">
                        {(definicion.estados ?? []).map((estado) => (
                            <li
                                key={estado.id}
                                className="flex items-center justify-between py-2"
                            >
                                <span className="font-mono">
                                    {estado.codigo}
                                </span>
                                <span className="text-muted-foreground">
                                    {estado.nombre}
                                    {estado.es_inicial && ' · inicial'}
                                    {estado.es_final && ' · final'}
                                </span>
                            </li>
                        ))}
                    </ul>
                </section>

                <section className="space-y-3 rounded-xl border p-4">
                    <h2 className="text-base font-medium">Transiciones</h2>

                    <ul className="divide-y text-sm">
                        {(definicion.transiciones ?? []).map((transicion) => {
                            const detalles = [
                                transicion.permiso_requerido &&
                                    `Permiso: ${transicion.permiso_requerido}`,
                                transicion.documentos_requeridos &&
                                    transicion.documentos_requeridos.length >
                                        0 &&
                                    `Documentos: ${transicion.documentos_requeridos.join(', ')}`,
                                transicion.requiere_comentario &&
                                    'Requiere comentario',
                            ].filter(Boolean);

                            return (
                                <li
                                    key={transicion.id}
                                    className="space-y-1 py-3"
                                >
                                    <div className="flex items-center justify-between">
                                        <span className="font-medium">
                                            {transicion.nombre}
                                        </span>
                                        <span className="font-mono text-muted-foreground">
                                            {transicion.estado_origen} →{' '}
                                            {transicion.estado_destino}
                                        </span>
                                    </div>
                                    {detalles.length > 0 && (
                                        <p className="text-muted-foreground">
                                            {detalles.join(' · ')}
                                        </p>
                                    )}
                                </li>
                            );
                        })}
                    </ul>
                </section>
            </div>
        </>
    );
}

DefinicionWorkflowShow.layout = {
    breadcrumbs: [
        { title: 'Definiciones de Workflow', href: definiciones.index() },
        { title: 'Detalle', href: '#' },
    ],
};
