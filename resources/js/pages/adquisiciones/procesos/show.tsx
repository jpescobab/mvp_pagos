import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { EstadoBadge } from '@/components/pago-proveedores/estado-badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Monto } from '@/components/ui/monto';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { formatFechaHora } from '@/lib/format';
import licitacionesMp from '@/routes/adquisiciones/licitaciones_mp';
import ordenesCompraMp from '@/routes/adquisiciones/ordenes_compra_mp';
import procesos from '@/routes/adquisiciones/procesos';
import documentos from '@/routes/procesos/documentos';
import type { ProcesoAdquisicion } from '@/types/adquisiciones';
import type {
    TipoDocumentoSeleccionable,
    TransicionWorkflow,
} from '@/types/pago-proveedores';

type PageProps = {
    proceso: ProcesoAdquisicion;
    tiposDocumento: TipoDocumentoSeleccionable[];
};

export default function ProcesoShow() {
    const { proceso, tiposDocumento, auth } = usePage<PageProps>().props;

    const puedeEditar =
        proceso.proceso.estado_actual.codigo === 'borrador' &&
        auth.permissions.includes('adquisiciones.editar_proceso');
    const [transicionConComentario, setTransicionConComentario] =
        useState<TransicionWorkflow | null>(null);
    const [comentario, setComentario] = useState('');
    const [procesando, setProcesando] = useState(false);
    const [errorTransicion, setErrorTransicion] = useState<string | null>(null);
    const [vistaPreviaPdfAbierta, setVistaPreviaPdfAbierta] = useState(false);

    function ejecutar(transicion: TransicionWorkflow, comentarioTexto = '') {
        setProcesando(true);
        setErrorTransicion(null);

        router.post(
            procesos.transiciones.store(proceso.id).url,
            { codigo: transicion.codigo, comentario: comentarioTexto },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setTransicionConComentario(null);
                    setComentario('');
                },
                onError: (errors) =>
                    setErrorTransicion(
                        (errors as Record<string, string>).transicion ?? null,
                    ),
                onFinish: () => setProcesando(false),
            },
        );
    }

    const historial = [
        ...(proceso.proceso.historial_transiciones ?? []),
    ].reverse();

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

        router.post(
            documentos.store({ proceso: proceso.proceso.id }).url,
            formData,
            {
                preserveScroll: true,
                onSuccess: () => {
                    setArchivo(null);
                    setTipoDocumentoId('');
                },
                onError: (errors) =>
                    setErrorDocumento(
                        (errors as Record<string, string>).archivo ??
                            (errors as Record<string, string>)
                                .tipo_documento_id ??
                            null,
                    ),
                onFinish: () => setSubiendoDocumento(false),
            },
        );
    }

    function desvincularDocumento(vinculoId: number) {
        router.delete(
            documentos.destroy({
                proceso: proceso.proceso.id,
                vinculo: vinculoId,
            }).url,
            { preserveScroll: true },
        );
    }

    const [errorValidacion, setErrorValidacion] = useState<string | null>(null);
    const [documentoARechazar, setDocumentoARechazar] = useState<number | null>(
        null,
    );
    const [observacionRechazo, setObservacionRechazo] = useState('');

    function validarDocumento(documentoId: number) {
        setErrorValidacion(null);

        router.post(
            documentos.validaciones.store({
                proceso: proceso.proceso.id,
                documento: documentoId,
            }).url,
            { estado: 'valido' },
            {
                preserveScroll: true,
                onError: (errors) =>
                    setErrorValidacion(
                        (errors as Record<string, string>).estado ?? null,
                    ),
            },
        );
    }

    function confirmarRechazo() {
        if (documentoARechazar === null) {
            return;
        }

        setErrorValidacion(null);

        router.post(
            documentos.validaciones.store({
                proceso: proceso.proceso.id,
                documento: documentoARechazar,
            }).url,
            { estado: 'rechazado', observacion: observacionRechazo },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setDocumentoARechazar(null);
                    setObservacionRechazo('');
                },
                onError: (errors) =>
                    setErrorValidacion(
                        (errors as Record<string, string>).observacion ??
                            (errors as Record<string, string>).estado ??
                            null,
                    ),
            },
        );
    }

    function subirNuevaVersion(documentoId: number, archivoVersion: File) {
        setErrorDocumento(null);

        const formData = new FormData();
        formData.append('archivo', archivoVersion);

        router.post(
            documentos.versiones.store({
                proceso: proceso.proceso.id,
                documento: documentoId,
            }).url,
            formData,
            {
                preserveScroll: true,
                onError: (errors) =>
                    setErrorDocumento(
                        (errors as Record<string, string>).archivo ?? null,
                    ),
            },
        );
    }

    return (
        <>
            <Head title={`Proceso ${proceso.codigo}`} />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h1 className="text-xl font-semibold tracking-tight">
                            {proceso.nombre ?? proceso.codigo}
                        </h1>
                        <p className="font-mono text-sm text-muted-foreground">
                            código: {proceso.codigo}
                            {proceso.ccosto.nombre &&
                                ` · ${proceso.ccosto.nombre}`}
                        </p>
                    </div>
                    <div className="flex items-center gap-2">
                        {proceso.proveedor.nombre && (
                            <span className="text-sm text-muted-foreground">
                                {proceso.proveedor.nombre}
                            </span>
                        )}
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={() => setVistaPreviaPdfAbierta(true)}
                        >
                            Ver PDF
                        </Button>
                        {puedeEditar && (
                            <Button
                                variant="outline"
                                size="sm"
                                onClick={() =>
                                    router.get(procesos.edit(proceso.id).url)
                                }
                            >
                                Editar
                            </Button>
                        )}
                        <EstadoBadge estado={proceso.proceso.estado_actual} />
                    </div>
                </div>

                <section className="space-y-3 rounded-xl border p-4">
                    <h2 className="text-base font-medium">
                        Antecedentes generales
                    </h2>
                    <dl className="grid gap-x-6 gap-y-2 text-sm sm:grid-cols-2">
                        <div>
                            <dt className="text-muted-foreground">
                                Fecha inicio compra
                            </dt>
                            <dd>{proceso.fecha_inicio ?? '—'}</dd>
                        </div>
                        <div>
                            <dt className="text-muted-foreground">
                                ID requerimiento
                            </dt>
                            <dd>{proceso.id_requerimiento ?? '—'}</dd>
                        </div>
                        <div>
                            <dt className="text-muted-foreground">
                                Unidad requirente
                            </dt>
                            <dd>{proceso.ccosto.nombre ?? '—'}</dd>
                        </div>
                        <div>
                            <dt className="text-muted-foreground">
                                Nombre requirente
                            </dt>
                            <dd>
                                {proceso.funcionario_requirente.nombre ?? '—'}
                            </dd>
                        </div>
                        <div>
                            <dt className="text-muted-foreground">Modalidad</dt>
                            <dd>{proceso.modalidad.nombre ?? '—'}</dd>
                        </div>
                        <div>
                            <dt className="text-muted-foreground">
                                Monto solicitado
                            </dt>
                            <dd>
                                {proceso.moneda_compra ?? 'CLP'}{' '}
                                {proceso.monto_estimado_solicitado ?? '—'}
                                {proceso.moneda_compra !== 'CLP' &&
                                    proceso.paridad && (
                                        <span className="text-muted-foreground">
                                            {' '}
                                            (paridad {proceso.paridad} al{' '}
                                            {proceso.fecha_paridad})
                                        </span>
                                    )}
                            </dd>
                        </div>
                        <div>
                            <dt className="text-muted-foreground">
                                Monto estimado (CLP)
                            </dt>
                            <dd>
                                <Monto valor={proceso.monto_estimado} />
                            </dd>
                        </div>
                        <div>
                            <dt className="text-muted-foreground">
                                ¿En Plan Anual de Compras?
                            </dt>
                            <dd>{proceso.en_plan_compras ? 'Sí' : 'No'}</dd>
                        </div>
                        {proceso.en_plan_compras && proceso.id_pac && (
                            <div>
                                <dt className="text-muted-foreground">
                                    ID del PAC
                                </dt>
                                <dd>{proceso.id_pac}</dd>
                            </div>
                        )}
                        <div>
                            <dt className="text-muted-foreground">
                                Código BIP (SUBT. 31)
                            </dt>
                            <dd>{proceso.codigo_bip ?? '—'}</dd>
                        </div>
                        <div className="sm:col-span-2">
                            <dt className="text-muted-foreground">
                                Características del bien y/o servicio
                            </dt>
                            <dd>{proceso.caracteristicas ?? '—'}</dd>
                        </div>
                        <div className="sm:col-span-2">
                            <dt className="text-muted-foreground">
                                Motivo de contratación
                            </dt>
                            <dd>{proceso.motivo_contratacion ?? '—'}</dd>
                        </div>
                    </dl>
                </section>

                <section className="space-y-3 rounded-xl border p-4">
                    <h2 className="text-base font-medium">
                        Transiciones disponibles
                    </h2>

                    {errorTransicion && (
                        <p className="text-sm text-destructive">
                            {errorTransicion}
                        </p>
                    )}

                    {proceso.proceso.transiciones_disponibles.length === 0 ? (
                        <p className="text-sm text-muted-foreground">
                            No hay transiciones disponibles desde el estado
                            actual.
                        </p>
                    ) : (
                        <div className="flex flex-wrap gap-2">
                            {proceso.proceso.transiciones_disponibles.map(
                                (transicion) => (
                                    <Button
                                        key={transicion.codigo}
                                        variant="outline"
                                        disabled={procesando}
                                        onClick={() =>
                                            transicion.requiere_comentario
                                                ? setTransicionConComentario(
                                                      transicion,
                                                  )
                                                : ejecutar(transicion)
                                        }
                                    >
                                        {transicion.codigo === 'publicar'
                                            ? 'Aprobar solicitud'
                                            : transicion.nombre}
                                    </Button>
                                ),
                            )}
                        </div>
                    )}
                </section>

                <section className="space-y-3 rounded-xl border p-4">
                    <h2 className="text-base font-medium">
                        Casos de pago vinculados
                    </h2>

                    {proceso.casos_pago_proveedor.length === 0 ? (
                        <p className="text-sm text-muted-foreground">
                            Sin casos de pago vinculados todavía.
                        </p>
                    ) : (
                        <ul className="divide-y text-sm">
                            {proceso.casos_pago_proveedor.map((caso) => (
                                <li key={caso.id} className="py-2 font-mono">
                                    {caso.sgf_id}
                                </li>
                            ))}
                        </ul>
                    )}
                </section>

                <section className="space-y-3 rounded-xl border p-4">
                    <h2 className="text-base font-medium">
                        Órdenes de compra (Mercado Público)
                    </h2>

                    {proceso.ordenes_compra_mercado_publico.length === 0 ? (
                        <p className="text-sm text-muted-foreground">
                            Sin órdenes de compra vinculadas todavía.
                        </p>
                    ) : (
                        <ul className="divide-y text-sm">
                            {proceso.ordenes_compra_mercado_publico.map(
                                (orden) => (
                                    <li
                                        key={orden.id}
                                        className="flex items-center justify-between gap-2 py-2"
                                    >
                                        <span className="min-w-0">
                                            <span className="font-mono">
                                                {orden.codigo}
                                            </span>
                                            <span className="text-muted-foreground">
                                                {' · '}
                                                {orden.organismo ?? '—'}
                                                {' · '}
                                                {orden.estado_mercado_publico ??
                                                    '—'}
                                            </span>
                                        </span>
                                        <span className="flex shrink-0 items-center gap-3">
                                            <Monto valor={orden.monto} />
                                            <Link
                                                href={ordenesCompraMp.show.url(
                                                    orden.id,
                                                )}
                                                className="text-primary underline"
                                            >
                                                Ver
                                            </Link>
                                        </span>
                                    </li>
                                ),
                            )}
                        </ul>
                    )}
                </section>

                <section className="space-y-3 rounded-xl border p-4">
                    <h2 className="text-base font-medium">
                        Licitaciones (Mercado Público)
                    </h2>

                    {proceso.licitaciones_mercado_publico.length === 0 ? (
                        <p className="text-sm text-muted-foreground">
                            Sin licitaciones vinculadas todavía.
                        </p>
                    ) : (
                        <ul className="divide-y text-sm">
                            {proceso.licitaciones_mercado_publico.map(
                                (licitacion) => (
                                    <li
                                        key={licitacion.id}
                                        className="flex items-center justify-between gap-2 py-2"
                                    >
                                        <span className="min-w-0">
                                            <span className="font-mono">
                                                {licitacion.codigo}
                                            </span>
                                            <span className="text-muted-foreground">
                                                {licitacion.nombre
                                                    ? ` · ${licitacion.nombre}`
                                                    : ''}
                                                {' · '}
                                                {licitacion.estado_mercado_publico ??
                                                    '—'}
                                            </span>
                                        </span>
                                        <span className="flex shrink-0 items-center gap-3">
                                            <Monto valor={licitacion.monto} />
                                            <Link
                                                href={licitacionesMp.show.url(
                                                    licitacion.id,
                                                )}
                                                className="text-primary underline"
                                            >
                                                Ver
                                            </Link>
                                        </span>
                                    </li>
                                ),
                            )}
                        </ul>
                    )}
                </section>

                <section className="space-y-3 rounded-xl border p-4">
                    <h2 className="text-base font-medium">
                        Checklist documental
                    </h2>

                    {!proceso.proceso.checklist ? (
                        <p className="text-sm text-muted-foreground">
                            Sin checklist generado aún.
                        </p>
                    ) : (
                        <ul className="divide-y text-sm">
                            {proceso.proceso.checklist.items.map((item, i) => (
                                <li
                                    key={i}
                                    className="flex items-center justify-between py-2"
                                >
                                    <span>
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
                                                            proceso.proceso.id,
                                                        documento:
                                                            item.documento_id,
                                                    }).url
                                                }
                                                className="underline"
                                            >
                                                Ver documento
                                            </a>
                                        )}
                                    </span>
                                </li>
                            ))}
                        </ul>
                    )}
                </section>

                <section className="space-y-3 rounded-xl border p-4">
                    <h2 className="text-base font-medium">Documentos</h2>

                    {errorDocumento && (
                        <p className="text-sm text-destructive">
                            {errorDocumento}
                        </p>
                    )}

                    {errorValidacion && (
                        <p className="text-sm text-destructive">
                            {errorValidacion}
                        </p>
                    )}

                    {(proceso.proceso.documentos ?? []).length === 0 ? (
                        <p className="text-sm text-muted-foreground">
                            Sin documentos vinculados todavía.
                        </p>
                    ) : (
                        <ul className="divide-y text-sm">
                            {(proceso.proceso.documentos ?? []).map((doc) => (
                                <li
                                    key={doc.vinculo_id}
                                    className="space-y-2 py-2"
                                >
                                    <div className="flex items-center justify-between">
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
                                                        proceso:
                                                            proceso.proceso.id,
                                                        documento:
                                                            doc.documento_id,
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
                                                    validarDocumento(
                                                        doc.documento_id,
                                                    )
                                                }
                                            >
                                                Validar
                                            </Button>
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                onClick={() =>
                                                    setDocumentoARechazar(
                                                        doc.documento_id,
                                                    )
                                                }
                                            >
                                                Rechazar
                                            </Button>
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
                                            <input
                                                type="file"
                                                accept=".pdf,.jpg,.jpeg,.png"
                                                className="w-32 text-xs"
                                                title="Subir nueva versión"
                                                onChange={(e) => {
                                                    const archivoVersion =
                                                        e.target.files?.[0];

                                                    if (archivoVersion) {
                                                        subirNuevaVersion(
                                                            doc.documento_id,
                                                            archivoVersion,
                                                        );
                                                    }

                                                    e.target.value = '';
                                                }}
                                            />
                                        </div>
                                    </div>

                                    {doc.validaciones.length > 0 && (
                                        <ul className="space-y-1 pl-4 text-xs text-muted-foreground">
                                            {doc.validaciones.map(
                                                (validacion, i) => (
                                                    <li key={i}>
                                                        {validacion.estado} ·{' '}
                                                        {validacion.validado_por ??
                                                            'Sistema'}
                                                        {validacion.validado_en &&
                                                            ` · ${formatFechaHora(validacion.validado_en)}`}
                                                        {validacion.observacion && (
                                                            <span className="italic">
                                                                {' '}
                                                                — “
                                                                {
                                                                    validacion.observacion
                                                                }
                                                                ”
                                                            </span>
                                                        )}
                                                    </li>
                                                ),
                                            )}
                                        </ul>
                                    )}
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
                            <input
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

                <section className="space-y-3 rounded-xl border p-4">
                    <h2 className="text-base font-medium">
                        Historial de transiciones
                    </h2>

                    {historial.length === 0 ? (
                        <p className="text-sm text-muted-foreground">
                            Sin transiciones registradas todavía.
                        </p>
                    ) : (
                        <ul className="divide-y text-sm">
                            {historial.map((item, i) => (
                                <li key={i} className="space-y-1 py-3">
                                    <div className="flex items-center justify-between">
                                        <span className="font-medium">
                                            {item.transicion.nombre}
                                        </span>
                                        <span className="text-muted-foreground">
                                            {formatFechaHora(item.created_at)}
                                        </span>
                                    </div>
                                    <p className="text-muted-foreground">
                                        {item.estado_origen.codigo} →{' '}
                                        {item.estado_destino.codigo} ·{' '}
                                        {item.user.name ?? 'Sistema'}
                                    </p>
                                    {item.comentario && (
                                        <p className="italic">
                                            “{item.comentario}”
                                        </p>
                                    )}
                                </li>
                            ))}
                        </ul>
                    )}
                </section>
            </div>

            <Dialog
                open={transicionConComentario !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        setTransicionConComentario(null);
                        setComentario('');
                    }
                }}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>
                            {transicionConComentario?.nombre}
                        </DialogTitle>
                        <DialogDescription>
                            Esta transición requiere un comentario.
                        </DialogDescription>
                    </DialogHeader>

                    <div className="grid gap-2">
                        <Label htmlFor="comentario">Comentario</Label>
                        <textarea
                            id="comentario"
                            className="min-h-24 rounded-md border bg-background p-2 text-sm"
                            value={comentario}
                            onChange={(e) => setComentario(e.target.value)}
                        />
                    </div>

                    <DialogFooter>
                        <Button
                            disabled={procesando || comentario === ''}
                            onClick={() =>
                                transicionConComentario &&
                                ejecutar(transicionConComentario, comentario)
                            }
                        >
                            Confirmar
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <Dialog
                open={documentoARechazar !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        setDocumentoARechazar(null);
                        setObservacionRechazo('');
                    }
                }}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Rechazar documento</DialogTitle>
                        <DialogDescription>
                            Indica el motivo del rechazo.
                        </DialogDescription>
                    </DialogHeader>

                    <div className="grid gap-2">
                        <Label htmlFor="observacion-rechazo">Observación</Label>
                        <textarea
                            id="observacion-rechazo"
                            className="min-h-24 rounded-md border bg-background p-2 text-sm"
                            value={observacionRechazo}
                            onChange={(e) =>
                                setObservacionRechazo(e.target.value)
                            }
                        />
                    </div>

                    <DialogFooter>
                        <Button
                            disabled={observacionRechazo === ''}
                            onClick={confirmarRechazo}
                        >
                            Confirmar rechazo
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <Dialog
                open={vistaPreviaPdfAbierta}
                onOpenChange={setVistaPreviaPdfAbierta}
            >
                <DialogContent className="max-h-[90vh] max-w-4xl">
                    <DialogHeader>
                        <DialogTitle>
                            Solicitud de compra — {proceso.codigo}
                        </DialogTitle>
                        <DialogDescription>
                            Vista previa del documento. Revísalo antes de
                            enviarlo a autorización.
                        </DialogDescription>
                    </DialogHeader>

                    <iframe
                        src={procesos.pdf(proceso.id).url}
                        title={`Solicitud de compra ${proceso.codigo}`}
                        className="h-[70vh] w-full rounded-md border"
                    />

                    <DialogFooter>
                        <Button variant="outline" asChild>
                            <a
                                href={procesos.pdf(proceso.id).url}
                                download={`${proceso.codigo}.pdf`}
                            >
                                Descargar
                            </a>
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}

ProcesoShow.layout = {
    breadcrumbs: [
        { title: 'Procesos de adquisición', href: procesos.index() },
        { title: 'Detalle', href: '#' },
    ],
};
