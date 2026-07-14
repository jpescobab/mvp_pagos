import { CheckCircle2, Circle } from 'lucide-react';
import type { Dispatch, SetStateAction } from 'react';
import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import documentos from '@/routes/procesos/documentos';
import type {
    CasoPagoProveedor,
    DocumentoVinculado,
} from '@/types/pago-proveedores';

type ChecklistDocumentalCardProps = {
    caso: CasoPagoProveedor;
    errorDocumento: string | null;
    documentosHuerfanos: DocumentoVinculado[];
    puedeGestionarDocumentos: boolean;
    subiendoDocumento: boolean;
    subirDocumento: (tipoDocumentoId: string, archivo: File) => void;
    huerfanoSeleccionado: Record<number, string | undefined>;
    setHuerfanoSeleccionado: Dispatch<
        SetStateAction<Record<number, string | undefined>>
    >;
    vinculandoHuerfano: boolean;
    vincularHuerfano: (
        tipoDocumentoId: number,
        documentoId: string | undefined,
    ) => void;
};

export function ChecklistDocumentalCard({
    caso,
    errorDocumento,
    documentosHuerfanos,
    puedeGestionarDocumentos,
    subiendoDocumento,
    subirDocumento,
    huerfanoSeleccionado,
    setHuerfanoSeleccionado,
    vinculandoHuerfano,
    vincularHuerfano,
}: ChecklistDocumentalCardProps) {
    return (
        <section className="space-y-3 rounded-xl border p-4">
            <h2 className="text-base font-medium">Checklist documental</h2>

            {errorDocumento && (
                <p className="text-sm text-destructive">{errorDocumento}</p>
            )}

            {!caso.proceso.checklist ? (
                <p className="text-sm text-muted-foreground">
                    Sin checklist generado aún.
                </p>
            ) : (
                <ul className="divide-y text-sm">
                    {caso.proceso.checklist.items.map((item, i) => {
                        const esPendiente =
                            item.estado_cumplimiento === 'pendiente';
                        const puedeVincularHuerfano =
                            puedeGestionarDocumentos &&
                            esPendiente &&
                            item.tipo_documento_id !== null &&
                            documentosHuerfanos.length > 0;

                        return (
                            <li key={i} className="flex flex-col gap-2 py-2">
                                <div className="flex flex-wrap items-center justify-between gap-2">
                                    <span className="flex items-center gap-2">
                                        {esPendiente ? (
                                            <Circle className="size-4 shrink-0 text-muted-foreground" />
                                        ) : (
                                            <CheckCircle2 className="size-4 shrink-0 text-success" />
                                        )}
                                        {item.tipo_documento ??
                                            'Documento sin tipo'}{' '}
                                        <span className="text-muted-foreground">
                                            ({item.tipo_requisito})
                                        </span>
                                    </span>
                                    <span className="flex items-center gap-2 text-muted-foreground">
                                        {item.estado_cumplimiento}
                                        {item.documento_id !== null && (
                                            <a
                                                href={
                                                    documentos.descargar({
                                                        proceso:
                                                            caso.proceso.id,
                                                        documento:
                                                            item.documento_id,
                                                    }).url
                                                }
                                                className="underline"
                                            >
                                                Ver documento
                                            </a>
                                        )}
                                        {puedeGestionarDocumentos &&
                                            esPendiente &&
                                            item.tipo_documento_id !== null && (
                                                <input
                                                    type="file"
                                                    accept=".pdf,.jpg,.jpeg,.png"
                                                    className="w-28 text-xs"
                                                    disabled={subiendoDocumento}
                                                    onChange={(e) => {
                                                        const archivoElegido =
                                                            e.target.files?.[0];

                                                        if (
                                                            archivoElegido &&
                                                            item.tipo_documento_id !==
                                                                null
                                                        ) {
                                                            subirDocumento(
                                                                String(
                                                                    item.tipo_documento_id,
                                                                ),
                                                                archivoElegido,
                                                            );
                                                        }

                                                        e.target.value = '';
                                                    }}
                                                />
                                            )}
                                    </span>
                                </div>
                                {puedeVincularHuerfano && (
                                    <div className="flex items-center gap-2 rounded-md bg-muted p-2">
                                        <span className="text-xs text-muted-foreground">
                                            o vincula uno ya importado:
                                        </span>
                                        <Select
                                            value={
                                                huerfanoSeleccionado[
                                                    item.tipo_documento_id as number
                                                ]
                                            }
                                            onValueChange={(valor) =>
                                                setHuerfanoSeleccionado(
                                                    (actual) => ({
                                                        ...actual,
                                                        [item.tipo_documento_id as number]:
                                                            valor,
                                                    }),
                                                )
                                            }
                                        >
                                            <SelectTrigger className="h-8 flex-1 text-xs">
                                                <SelectValue placeholder="Elegir documento…" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {documentosHuerfanos.map(
                                                    (doc) => (
                                                        <SelectItem
                                                            key={
                                                                doc.documento_id
                                                            }
                                                            value={String(
                                                                doc.documento_id,
                                                            )}
                                                        >
                                                            {doc.nombre_archivo ??
                                                                'Documento sin nombre'}{' '}
                                                            (
                                                            {doc.tipo_documento ??
                                                                'sin tipo'}
                                                            )
                                                        </SelectItem>
                                                    ),
                                                )}
                                            </SelectContent>
                                        </Select>
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            disabled={
                                                vinculandoHuerfano ||
                                                !huerfanoSeleccionado[
                                                    item.tipo_documento_id as number
                                                ]
                                            }
                                            onClick={() =>
                                                vincularHuerfano(
                                                    item.tipo_documento_id as number,
                                                    huerfanoSeleccionado[
                                                        item.tipo_documento_id as number
                                                    ],
                                                )
                                            }
                                        >
                                            Vincular
                                        </Button>
                                    </div>
                                )}
                            </li>
                        );
                    })}
                </ul>
            )}
        </section>
    );
}
