import { Link, usePage } from '@inertiajs/react';
import { CheckCircle2, Circle } from 'lucide-react';
import { Button } from '@/components/ui/button';
import egresosCgu from '@/routes/pago-proveedores/egresos-cgu';
import type { CasoPagoProveedor } from '@/types/pago-proveedores';

type CriterioPreparacion = {
    etiqueta: string;
    cumplido: boolean;
    detalle: string;
};

/**
 * Replica en el cliente el mismo criterio de ListoParaEgresoResolver
 * (app/Services/PagoProveedores/ListoParaEgresoResolver.php), que es la
 * fuente de verdad — si ese criterio cambia, este helper debe actualizarse
 * junto con él.
 */
function calcularPreparacionEgreso(
    caso: CasoPagoProveedor,
): CriterioPreparacion[] {
    const itemsObligatorios = (caso.proceso.checklist?.items ?? []).filter(
        (item) => item.tipo_requisito === 'obligatorio',
    );
    const checklistCompleto =
        itemsObligatorios.length > 0 &&
        itemsObligatorios.every((item) => item.documento_id !== null);

    return [
        {
            etiqueta: 'Tipo de proceso',
            cumplido: caso.proceso.tipo_proceso_pago_id !== null,
            detalle: caso.proceso.tipo_proceso_pago?.nombre ?? 'Sin clasificar',
        },
        {
            etiqueta: 'Traspaso (CGU)',
            cumplido: (caso.registros_contables_cgu ?? []).length > 0,
            detalle:
                caso.registros_contables_cgu?.[0]?.numero_registro ??
                'Sin registrar',
        },
        {
            etiqueta: 'Checklist documental',
            cumplido: checklistCompleto,
            detalle:
                itemsObligatorios.length > 0
                    ? `${itemsObligatorios.filter((item) => item.documento_id !== null).length} / ${itemsObligatorios.length} obligatorios`
                    : 'Sin checklist generado',
        },
        {
            etiqueta: 'Proveedor identificado',
            cumplido: caso.proveedor.nombre !== null,
            detalle: caso.proveedor.nombre ?? 'No identificado',
        },
    ];
}

export function PreparacionEgresoCard({ caso }: { caso: CasoPagoProveedor }) {
    const { auth } = usePage().props;
    const criterios = calcularPreparacionEgreso(caso);
    const completados = criterios.filter((c) => c.cumplido).length;
    const porcentaje = (completados / criterios.length) * 100;
    const listoParaEgreso = completados === criterios.length;
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
                        key={criterio.etiqueta}
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
