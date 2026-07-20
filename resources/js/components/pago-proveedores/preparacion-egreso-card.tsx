import { Link, usePage } from '@inertiajs/react';
import { CheckCircle2, Circle } from 'lucide-react';
import { Button } from '@/components/ui/button';
import egresosCgu from '@/routes/pago-proveedores/egresos-cgu';
import type { CasoPagoProveedor } from '@/types/pago-proveedores';

export function PreparacionEgresoCard({ caso }: { caso: CasoPagoProveedor }) {
    const { auth } = usePage().props;
    const criterios = caso.preparacion_egreso ?? [];
    const completados = criterios.filter((c) => c.cumplido).length;
    const porcentaje =
        criterios.length > 0 ? (completados / criterios.length) * 100 : 0;
    const listoParaEgreso =
        criterios.length > 0 && completados === criterios.length;
    const sinEgresoAsociado = (caso.egresos_cgu ?? []).length === 0;
    const puedeRegistrarEgreso = auth.permissions.includes(
        'pago_proveedores.registrar_egreso',
    );

    return (
        <section className="space-y-3 rounded-xl border p-4">
            <div className="flex flex-wrap items-center justify-between gap-2">
                <div>
                    <h2 className="text-base font-medium">
                        Preparación para Asignar Egreso
                    </h2>
                    <p className="text-sm text-muted-foreground">
                        Criterios que habilitan avanzar este caso desde una
                        importación SGF
                    </p>
                </div>
                <div className="flex items-center gap-3">
                    <span
                        className={`font-mono text-sm font-semibold ${
                            listoParaEgreso ? 'text-success' : 'text-warning'
                        }`}
                    >
                        {completados} / {criterios.length} completo
                    </span>
                    {listoParaEgreso &&
                        sinEgresoAsociado &&
                        puedeRegistrarEgreso && (
                            <Button asChild size="sm">
                                <Link
                                    href={
                                        egresosCgu.create({
                                            query: {
                                                caso_pago_proveedor_id:
                                                    caso.id,
                                            },
                                        }).url
                                    }
                                >
                                    Crear Egreso CGU con este caso
                                </Link>
                            </Button>
                        )}
                </div>
            </div>

            <div className="h-1.5 overflow-hidden rounded-full bg-muted">
                <div
                    className="h-full rounded-full bg-success"
                    style={{ width: `${porcentaje}%` }}
                />
            </div>

            <div className="grid grid-cols-2 gap-2 sm:grid-cols-4">
                {criterios.map((criterio) => (
                    <div
                        key={criterio.criterio}
                        className={`flex flex-col gap-1 rounded-md border p-2.5 ${
                            criterio.cumplido
                                ? 'border-transparent bg-success-soft'
                                : 'border-transparent bg-warning-soft'
                        }`}
                    >
                        <div
                            className={`flex items-center gap-1.5 text-xs font-semibold ${
                                criterio.cumplido
                                    ? 'text-success'
                                    : 'text-warning'
                            }`}
                        >
                            {criterio.cumplido ? (
                                <CheckCircle2 className="size-3.5 shrink-0" />
                            ) : (
                                <Circle className="size-3.5 shrink-0" />
                            )}
                            {criterio.etiqueta}
                        </div>
                        <span className="truncate text-xs text-muted-foreground">
                            {criterio.detalle}
                        </span>
                    </div>
                ))}
            </div>
        </section>
    );
}
