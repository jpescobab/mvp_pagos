import { Head, Link, usePage } from '@inertiajs/react';
import { ProveedorStatusBadge } from '@/components/maestros/proveedor-status-badge';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { useInitials } from '@/hooks/use-initials';
import proveedores from '@/routes/maestros/proveedores';
import type { CatalogosProveedor, Proveedor } from '@/types/maestros';

type PageProps = {
    proveedor: Proveedor;
    catalogos: CatalogosProveedor;
    tieneDocumentoRespaldo: boolean;
};

function Campo({
    etiqueta,
    valor,
}: {
    etiqueta: string;
    valor: string | null;
}) {
    return (
        <div className="flex flex-col gap-0.5">
            <dt className="text-xs text-muted-foreground">{etiqueta}</dt>
            <dd className="text-sm">{valor?.trim() ? valor : '—'}</dd>
        </div>
    );
}

export default function ProveedorShow() {
    const { proveedor, catalogos, tieneDocumentoRespaldo } =
        usePage<PageProps>().props;
    const getInitials = useInitials();

    const etiquetaTipoContribuyente =
        catalogos.tiposContribuyente.find(
            (o) => o.value === proveedor.tipo_contribuyente,
        )?.label ?? null;
    const etiquetaTipoCuenta =
        catalogos.tiposCuenta.find((o) => o.value === proveedor.tipo_cuenta)
            ?.label ?? null;
    const etiquetaCondicionPago =
        catalogos.condicionesPago.find(
            (o) => o.value === proveedor.condicion_pago,
        )?.label ?? null;
    const etiquetaMoneda =
        catalogos.monedas.find((o) => o.value === proveedor.moneda)?.label ??
        null;
    const rubrosSeleccionados = catalogos.rubros.filter((o) =>
        (proveedor.rubros ?? []).includes(o.value),
    );

    return (
        <>
            <Head title={proveedor.nombre} />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-3">
                        <Avatar className="size-10 shrink-0">
                            <AvatarFallback className="bg-accent text-sm font-semibold text-accent-foreground">
                                {getInitials(proveedor.nombre)}
                            </AvatarFallback>
                        </Avatar>
                        <div>
                            <h1 className="text-xl font-semibold tracking-tight">
                                {proveedor.nombre}
                            </h1>
                            <p className="font-mono text-xs text-muted-foreground">
                                {proveedor.rutproveedor}
                            </p>
                        </div>
                        <ProveedorStatusBadge estado={proveedor.estado} />
                    </div>
                    <div className="flex gap-2">
                        <Button variant="outline" asChild>
                            <Link href={proveedores.index().url}>Volver</Link>
                        </Button>
                        <Button asChild>
                            <Link href={proveedores.edit(proveedor.id).url}>
                                Editar
                            </Link>
                        </Button>
                    </div>
                </div>

                <section className="space-y-3 rounded-xl border p-4">
                    <h2 className="text-base font-medium">
                        Identificación tributaria
                    </h2>
                    <dl className="grid gap-4 sm:grid-cols-2">
                        <Campo etiqueta="Giro" valor={proveedor.giro} />
                        <Campo
                            etiqueta="Tipo de contribuyente"
                            valor={etiquetaTipoContribuyente}
                        />
                    </dl>
                </section>

                <section className="space-y-3 rounded-xl border p-4">
                    <h2 className="text-base font-medium">Clasificación</h2>
                    {rubrosSeleccionados.length === 0 ? (
                        <p className="text-sm text-muted-foreground">
                            Sin rubros seleccionados.
                        </p>
                    ) : (
                        <ul className="flex flex-wrap gap-2 text-sm">
                            {rubrosSeleccionados.map((rubro) => (
                                <li
                                    key={rubro.value}
                                    className="rounded-full border px-2.5 py-1"
                                >
                                    {rubro.label}
                                </li>
                            ))}
                        </ul>
                    )}
                </section>

                <section className="space-y-3 rounded-xl border p-4">
                    <h2 className="text-base font-medium">
                        Contacto comercial
                    </h2>
                    <dl className="grid gap-4 sm:grid-cols-2">
                        <Campo
                            etiqueta="Nombre de contacto"
                            valor={proveedor.contacto}
                        />
                        <Campo
                            etiqueta="Cargo"
                            valor={proveedor.contacto_cargo}
                        />
                        <Campo
                            etiqueta="Teléfono"
                            valor={proveedor.contacto_telefono}
                        />
                        <Campo etiqueta="Correo" valor={proveedor.correo} />
                    </dl>
                </section>

                <section className="space-y-3 rounded-xl border p-4">
                    <h2 className="text-base font-medium">Domicilio</h2>
                    <dl className="grid gap-4 sm:grid-cols-3">
                        <Campo
                            etiqueta="Dirección"
                            valor={proveedor.direccion}
                        />
                        <Campo etiqueta="Región" valor={proveedor.region} />
                        <Campo etiqueta="Comuna" valor={proveedor.comuna} />
                    </dl>
                </section>

                <section className="space-y-3 rounded-xl border p-4">
                    <h2 className="text-base font-medium">Datos bancarios</h2>
                    <dl className="grid gap-4 sm:grid-cols-2">
                        <Campo etiqueta="Banco" valor={proveedor.banco} />
                        <Campo
                            etiqueta="Tipo de cuenta"
                            valor={etiquetaTipoCuenta}
                        />
                        <Campo
                            etiqueta="N° de cuenta"
                            valor={proveedor.numero_cuenta}
                        />
                        <Campo
                            etiqueta="Condición de pago"
                            valor={etiquetaCondicionPago}
                        />
                        <Campo etiqueta="Moneda" valor={etiquetaMoneda} />
                        <Campo
                            etiqueta="Correo para pagos"
                            valor={proveedor.correo_pago}
                        />
                        <Campo
                            etiqueta="Documento de respaldo"
                            valor={tieneDocumentoRespaldo ? 'Adjunto' : null}
                        />
                    </dl>
                </section>

                {proveedor.notas_internas && (
                    <section className="space-y-3 rounded-xl border p-4">
                        <h2 className="text-base font-medium">
                            Notas internas
                        </h2>
                        <p className="text-sm whitespace-pre-wrap text-muted-foreground">
                            {proveedor.notas_internas}
                        </p>
                    </section>
                )}
            </div>
        </>
    );
}

ProveedorShow.layout = {
    breadcrumbs: [
        { title: 'Proveedores', href: proveedores.index() },
        { title: 'Detalle', href: '#' },
    ],
};
