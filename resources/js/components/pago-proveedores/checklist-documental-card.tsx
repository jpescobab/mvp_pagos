import { CheckCircle2, Circle, Eye, Link2, Unlink } from 'lucide-react';
import type { Dispatch, SetStateAction } from 'react';
import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type {
    CasoPagoProveedor,
    DocumentoRevinculable,
    DocumentoVinculado,
} from '@/types/pago-proveedores';

type ChecklistDocumentalCardProps = {
    caso: CasoPagoProveedor;
    errorDocumento: string | null;
    documentosHuerfanos: DocumentoVinculado[];
    documentosRevinculables: DocumentoRevinculable[];
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
    reactivarDocumento: (
        tipoDocumentoId: number,
        documentoId: string | undefined,
    ) => void;
    documentoPreviewId: number | null;
    onVerDocumento: (documentoId: number) => void;
    desvincularDocumento: (vinculoId: number) => void;
};

export function ChecklistDocumentalCard({
    caso,
    errorDocumento,
    documentosHuerfanos,
    documentosRevinculables,
    puedeGestionarDocumentos,
    subiendoDocumento,
    subirDocumento,
    huerfanoSeleccionado,
    setHuerfanoSeleccionado,
    vinculandoHuerfano,
    vincularHuerfano,
    reactivarDocumento,
    documentoPreviewId,
    onVerDocumento,
    desvincularDocumento,
}: ChecklistDocumentalCardProps) {
    // Ids de documentos re-vinculables (desvinculados): al elegirlos hay que
    // reactivar el vínculo en vez de reclasificar un huérfano activo.
    const idsRevinculables = new Set(
        documentosRevinculables.map((doc) => String(doc.documento_id)),
    );

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
                        const puedeVincularExistente =
                            puedeGestionarDocumentos &&
                            esPendiente &&
                            item.tipo_documento_id !== null &&
                            documentosHuerfanos.length +
                                documentosRevinculables.length >
                                0;

                        return (
                            <li key={i} className="flex flex-col gap-2 py-2">
                                <div className="flex flex-wrap items-center justify-between gap-2">
                                    <span className="flex min-w-0 items-center gap-2">
                                        {esPendiente ? (
                                            <Circle className="size-4 shrink-0 text-warning" />
                                        ) : (
                                            <CheckCircle2 className="size-4 shrink-0 text-primary" />
                                        )}
                                        <span className="shrink-0">
                                            {item.tipo_documento ??
                                                'Documento sin tipo'}{' '}
                                            <span className="text-muted-foreground">
                                                ({item.tipo_requisito})
                                            </span>
                                        </span>
                                        {item.documento_id !== null &&
                                            item.nombre_archivo && (
                                                <span
                                                    className="truncate text-xs text-muted-foreground"
                                                    title={item.nombre_archivo}
                                                >
                                                    · {item.nombre_archivo}
                                                </span>
                                            )}
                                    </span>
                                    <span className="flex items-center gap-2 text-muted-foreground">
                                        {item.documento_id !== null && (
                                            <button
                                                type="button"
                                                title="Ver documento"
                                                onClick={() =>
                                                    onVerDocumento(
                                                        item.documento_id as number,
                                                    )
                                                }
                                                className={
                                                    item.documento_id ===
                                                    documentoPreviewId
                                                        ? 'text-foreground'
                                                        : 'hover:text-foreground'
                                                }
                                            >
                                                <Eye className="size-4" />
                                            </button>
                                        )}
                                        {puedeGestionarDocumentos &&
                                            item.documento_id !== null &&
                                            (() => {
                                                const vinculo =
                                                    caso.proceso.documentos?.find(
                                                        (doc) =>
                                                            doc.documento_id ===
                                                            item.documento_id,
                                                    );

                                                return (
                                                    vinculo && (
                                                        <button
                                                            type="button"
                                                            title="Desvincular documento"
                                                            onClick={() =>
                                                                desvincularDocumento(
                                                                    vinculo.vinculo_id,
                                                                )
                                                            }
                                                            className="hover:text-destructive"
                                                        >
                                                            <Unlink className="size-4" />
                                                        </button>
                                                    )
                                                );
                                            })()}
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
                                {puedeVincularExistente && (
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
                                                {documentosRevinculables.map(
                                                    (doc) => (
                                                        <SelectItem
                                                            key={`rev-${doc.documento_id}`}
                                                            value={String(
                                                                doc.documento_id,
                                                            )}
                                                        >
                                                            {doc.nombre_archivo ??
                                                                'Documento sin nombre'}{' '}
                                                            (desvinculado)
                                                        </SelectItem>
                                                    ),
                                                )}
                                            </SelectContent>
                                        </Select>
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            title="Vincular documento"
                                            disabled={
                                                vinculandoHuerfano ||
                                                !huerfanoSeleccionado[
                                                    item.tipo_documento_id as number
                                                ]
                                            }
                                            onClick={() => {
                                                const seleccionado =
                                                    huerfanoSeleccionado[
                                                        item.tipo_documento_id as number
                                                    ];
                                                const tipoId =
                                                    item.tipo_documento_id as number;

                                                // Un documento desvinculado se
                                                // reactiva; un huérfano activo se
                                                // reclasifica (comportamiento
                                                // previo).
                                                if (
                                                    seleccionado !== undefined &&
                                                    idsRevinculables.has(
                                                        seleccionado,
                                                    )
                                                ) {
                                                    reactivarDocumento(
                                                        tipoId,
                                                        seleccionado,
                                                    );
                                                } else {
                                                    vincularHuerfano(
                                                        tipoId,
                                                        seleccionado,
                                                    );
                                                }
                                            }}
                                        >
                                            <Link2 className="size-4" />
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
