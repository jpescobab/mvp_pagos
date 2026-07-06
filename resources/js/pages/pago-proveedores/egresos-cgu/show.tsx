import { Head, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Monto } from '@/components/ui/monto';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import documentos from '@/routes/egresos-cgu/documentos';
import egresosCgu from '@/routes/pago-proveedores/egresos-cgu';
import type {
    EgresoCgu,
    TipoDocumentoSeleccionable,
} from '@/types/pago-proveedores';

type PageProps = {
    egreso: EgresoCgu;
    tiposDocumento: TipoDocumentoSeleccionable[];
};

export default function EgresoCguShow() {
    const { egreso, tiposDocumento } = usePage<PageProps>().props;

    const [tipoDocumentoId, setTipoDocumentoId] = useState<string>('');
    const [archivo, setArchivo] = useState<File | null>(null);
    const [subiendoDocumento, setSubiendoDocumento] = useState(false);
    const [errorDocumento, setErrorDocumento] = useState<string | null>(null);

    function subirDocumento() {
        if (archivo === null || tipoDocumentoId === '') {
            return;
        }

        setSubiendoDocumento(true);
        setErrorDocumento(null);

        const formData = new FormData();
        formData.append('archivo', archivo);
        formData.append('tipo_documento_id', tipoDocumentoId);

        router.post(documentos.store(egreso.id).url, formData, {
            preserveScroll: true,
            onSuccess: () => {
                setArchivo(null);
                setTipoDocumentoId('');
            },
            onError: (errors) =>
                setErrorDocumento(
                    (errors as Record<string, string>).archivo ??
                        (errors as Record<string, string>).tipo_documento_id ??
                        null,
                ),
            onFinish: () => setSubiendoDocumento(false),
        });
    }

    function desvincularDocumento(vinculoId: number) {
        router.delete(
            documentos.destroy({ egresoCgu: egreso.id, vinculo: vinculoId })
                .url,
            { preserveScroll: true },
        );
    }

    return (
        <>
            <Head title={`Egreso ${egreso.numero_egreso}`} />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div>
                    <h1 className="text-xl font-semibold tracking-tight">
                        Egreso {egreso.numero_egreso}
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        {new Date(egreso.fecha).toLocaleDateString()} · Monto
                        total <Monto valor={egreso.monto_total} />
                    </p>
                    {egreso.observaciones && (
                        <p className="mt-2 text-sm text-muted-foreground italic">
                            “{egreso.observaciones}”
                        </p>
                    )}
                </div>

                <section className="space-y-3 rounded-xl border p-4">
                    <h2 className="text-base font-medium">Casos cubiertos</h2>

                    {egreso.items.length === 0 ? (
                        <p className="text-sm text-muted-foreground">
                            Sin casos cubiertos.
                        </p>
                    ) : (
                        <ul className="divide-y text-sm">
                            {egreso.items.map((item, i) => (
                                <li
                                    key={i}
                                    className="flex items-center justify-between py-2"
                                >
                                    <span className="font-mono">
                                        {item.caso.sgf_id}
                                    </span>
                                    <Monto valor={item.monto} />
                                </li>
                            ))}
                        </ul>
                    )}
                </section>

                <section className="space-y-3 rounded-xl border p-4">
                    <h2 className="text-base font-medium">
                        Documentos (comprobantes)
                    </h2>

                    {errorDocumento && (
                        <p className="text-sm text-destructive">
                            {errorDocumento}
                        </p>
                    )}

                    {(egreso.documentos ?? []).length === 0 ? (
                        <p className="text-sm text-muted-foreground">
                            Sin documentos vinculados todavía.
                        </p>
                    ) : (
                        <ul className="divide-y text-sm">
                            {(egreso.documentos ?? []).map((doc) => (
                                <li
                                    key={doc.vinculo_id}
                                    className="flex items-center justify-between py-2"
                                >
                                    <span>
                                        {doc.tipo_documento ??
                                            'Documento sin tipo'}{' '}
                                        <span className="text-muted-foreground">
                                            ({doc.nombre_archivo}) ·{' '}
                                            {doc.estado_vigente}
                                        </span>
                                    </span>
                                    <div className="flex gap-2">
                                        <a
                                            href={
                                                documentos.descargar({
                                                    egresoCgu: egreso.id,
                                                    documento: doc.documento_id,
                                                }).url
                                            }
                                            className="text-sm underline"
                                        >
                                            Descargar
                                        </a>
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            onClick={() =>
                                                desvincularDocumento(
                                                    doc.vinculo_id,
                                                )
                                            }
                                        >
                                            Desvincular
                                        </Button>
                                    </div>
                                </li>
                            ))}
                        </ul>
                    )}

                    <div className="flex flex-wrap items-end gap-2">
                        <div className="space-y-1">
                            <Label htmlFor="tipo-documento">
                                Tipo de documento
                            </Label>
                            <Select
                                value={tipoDocumentoId}
                                onValueChange={setTipoDocumentoId}
                            >
                                <SelectTrigger id="tipo-documento">
                                    <SelectValue placeholder="Selecciona un tipo" />
                                </SelectTrigger>
                                <SelectContent>
                                    {tiposDocumento.map((tipo) => (
                                        <SelectItem
                                            key={tipo.id}
                                            value={String(tipo.id)}
                                        >
                                            {tipo.nombre}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                        <div className="space-y-1">
                            <Label htmlFor="archivo">Archivo</Label>
                            <Input
                                id="archivo"
                                type="file"
                                accept=".pdf,.jpg,.jpeg,.png"
                                onChange={(e) =>
                                    setArchivo(e.target.files?.[0] ?? null)
                                }
                                className="text-sm"
                            />
                        </div>
                        <Button
                            disabled={
                                subiendoDocumento ||
                                archivo === null ||
                                tipoDocumentoId === ''
                            }
                            onClick={subirDocumento}
                        >
                            Subir
                        </Button>
                    </div>
                </section>
            </div>
        </>
    );
}

EgresoCguShow.layout = {
    breadcrumbs: [
        { title: 'Egresos CGU', href: egresosCgu.index() },
        { title: 'Detalle', href: '#' },
    ],
};
