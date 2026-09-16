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
import casos from '@/routes/pago-proveedores/casos';
import detalleFactura from '@/routes/pago-proveedores/facturas/detalle-factura';
import type { CcostoSeleccionable } from '@/types/adquisiciones';
import type {
    DetalleFactura,
    FacturaResumen,
    TipoCompraSeleccionable,
} from '@/types/pago-proveedores';

type PageProps = {
    factura: FacturaResumen;
    detalleFactura: DetalleFactura;
    tiposCompra: TipoCompraSeleccionable[];
    ccostos: CcostoSeleccionable[];
};

export default function DetalleFacturaEditar() {
    const {
        factura,
        detalleFactura: detalle,
        tiposCompra,
        ccostos,
    } = usePage<PageProps>().props;

    const [tipoCompraId, setTipoCompraId] = useState(
        String(detalle.tipo_compra_id),
    );
    const [ccostoId, setCcostoId] = useState(String(detalle.ccosto_id));
    const [cantidad, setCantidad] = useState(detalle.cantidad);
    const [unidadMedida, setUnidadMedida] = useState(
        detalle.unidad_medida ?? '',
    );
    const [monto, setMonto] = useState(detalle.monto);
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [procesando, setProcesando] = useState(false);

    function enviar() {
        setProcesando(true);
        setErrors({});

        router.patch(
            detalleFactura.update(factura.id).url,
            {
                tipo_compra_id: Number(tipoCompraId),
                ccosto_id: Number(ccostoId),
                cantidad,
                unidad_medida: unidadMedida || null,
                monto,
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
            <Head title={`Editar detalle de factura — ${factura.folio}`} />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <h1 className="text-xl font-semibold tracking-tight">
                    Editar detalle de factura — {factura.folio}
                </h1>

                <div className="grid max-w-xl gap-4 rounded-xl border p-4">
                    <div className="grid gap-2">
                        <Label htmlFor="tipo_compra_id">
                            Tipo de compra
                            <span className="text-destructive">*</span>
                        </Label>
                        <Select
                            value={tipoCompraId}
                            onValueChange={setTipoCompraId}
                        >
                            <SelectTrigger
                                id="tipo_compra_id"
                                className="w-full"
                            >
                                <SelectValue placeholder="Selecciona un tipo de compra" />
                            </SelectTrigger>
                            <SelectContent>
                                {tiposCompra.map((tipo) => (
                                    <SelectItem
                                        key={tipo.id}
                                        value={String(tipo.id)}
                                    >
                                        {tipo.nombre}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        {errors.tipo_compra_id && (
                            <p className="text-sm text-destructive">
                                {errors.tipo_compra_id}
                            </p>
                        )}
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="ccosto_id">
                            Centro de costo
                            <span className="text-destructive">*</span>
                        </Label>
                        <Select value={ccostoId} onValueChange={setCcostoId}>
                            <SelectTrigger id="ccosto_id" className="w-full">
                                <SelectValue placeholder="Selecciona un centro de costo" />
                            </SelectTrigger>
                            <SelectContent>
                                {ccostos.map((ccosto) => (
                                    <SelectItem
                                        key={ccosto.id}
                                        value={String(ccosto.id)}
                                    >
                                        {ccosto.codigo} · {ccosto.nombre}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        {errors.ccosto_id && (
                            <p className="text-sm text-destructive">
                                {errors.ccosto_id}
                            </p>
                        )}
                    </div>

                    <div className="grid grid-cols-2 gap-4">
                        <div className="grid gap-2">
                            <Label htmlFor="cantidad">
                                Cantidad
                                <span className="text-destructive">*</span>
                            </Label>
                            <Input
                                id="cantidad"
                                type="number"
                                step="0.01"
                                value={cantidad}
                                onChange={(e) => setCantidad(e.target.value)}
                            />
                            {errors.cantidad && (
                                <p className="text-sm text-destructive">
                                    {errors.cantidad}
                                </p>
                            )}
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="unidad_medida">
                                Unidad de medida
                            </Label>
                            <Input
                                id="unidad_medida"
                                value={unidadMedida}
                                onChange={(e) =>
                                    setUnidadMedida(e.target.value)
                                }
                            />
                            {errors.unidad_medida && (
                                <p className="text-sm text-destructive">
                                    {errors.unidad_medida}
                                </p>
                            )}
                        </div>
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="monto">
                            Monto
                            <span className="text-destructive">*</span>
                        </Label>
                        <Input
                            id="monto"
                            type="number"
                            step="0.01"
                            value={monto}
                            onChange={(e) => setMonto(e.target.value)}
                        />
                        {errors.monto && (
                            <p className="text-sm text-destructive">
                                {errors.monto}
                            </p>
                        )}
                    </div>
                </div>

                <div className="flex gap-2">
                    <Button disabled={procesando} onClick={enviar}>
                        Guardar cambios
                    </Button>
                    <Button
                        variant="outline"
                        disabled={procesando}
                        onClick={() =>
                            router.get(
                                casos.show(factura.caso_pago_proveedor_id).url,
                            )
                        }
                    >
                        Cancelar
                    </Button>
                </div>
            </div>
        </>
    );
}

DetalleFacturaEditar.layout = {
    breadcrumbs: [
        { title: 'Casos de pago', href: casos.index() },
        { title: 'Detalle', href: '#' },
        { title: 'Editar detalle de factura', href: '#' },
    ],
};
