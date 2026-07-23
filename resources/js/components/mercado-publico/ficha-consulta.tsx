import { Check, ExternalLink, FileJson, FileText } from 'lucide-react';
import type { ReactNode } from 'react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { formatFecha, formatFechaHora } from '@/lib/format';

/**
 * Componente genérico de "ficha" para consultas a Mercado Público: no conoce
 * los campos de una Orden de Compra ni de una Licitación, solo renderiza las
 * secciones que le pasa el llamador en el orden recibido (encabezado +
 * cronograma como segunda sección + el resto). Pensado para que un cambio
 * futuro que agregue la consulta de Licitaciones reutilice el mismo layout.
 */

export type SeccionFichaConsulta = {
    key: string;
    titulo: string;
    contenido: ReactNode;
};

export type EncabezadoFichaConsulta = {
    titulo: string;
    subtitulo?: ReactNode;
    montoDestacado?: ReactNode;
    acciones?: ReactNode;
};

type FichaConsultaMercadoPublicoProps = {
    encabezado: EncabezadoFichaConsulta;
    secciones: SeccionFichaConsulta[];
};

export function FichaConsultaMercadoPublico({
    encabezado,
    secciones,
}: FichaConsultaMercadoPublicoProps) {
    return (
        <div className="space-y-4">
            <div className="flex flex-wrap items-start justify-between gap-4 rounded-xl border p-4">
                <div>
                    <h1 className="text-xl font-semibold tracking-tight">
                        {encabezado.titulo}
                    </h1>
                    {encabezado.subtitulo && (
                        <div className="text-sm text-muted-foreground">
                            {encabezado.subtitulo}
                        </div>
                    )}
                </div>
                <div className="flex flex-wrap items-center gap-4">
                    {encabezado.montoDestacado && (
                        <div className="text-right">
                            {encabezado.montoDestacado}
                        </div>
                    )}
                    {encabezado.acciones && (
                        <div className="flex items-center gap-2">
                            {encabezado.acciones}
                        </div>
                    )}
                </div>
            </div>

            {secciones.map((seccion) => (
                <section
                    key={seccion.key}
                    className="space-y-3 rounded-xl border p-4"
                >
                    <h2 className="text-base font-medium">{seccion.titulo}</h2>
                    {seccion.contenido}
                </section>
            ))}
        </div>
    );
}

/**
 * Acciones del encabezado de la ficha de una consulta a Mercado Público:
 * "Ver JSON" muestra el payload crudo del snapshot ya vinculado (sin volver a
 * consultar Mercado Público); "Mercado Público" abre en una pestaña nueva el
 * detalle oficial en mercadopublico.cl (`urlDetalle`, provisto por el
 * llamador porque cada dominio expone su propia URL pública); "Ver PDF"
 * descarga el PDF a través de un endpoint propio (`urlPdf`), que cada dominio
 * resuelve a su manera —la Orden de Compra redirige al enlace público, la
 * Licitación entrega el archivo— sin que este componente tenga que saberlo.
 */
export function AccionesEncabezadoFichaMercadoPublico({
    payloadCrudo,
    urlDetalle,
    urlPdf,
}: {
    payloadCrudo: unknown;
    urlDetalle: string;
    urlPdf: string;
}) {
    const [jsonAbierto, setJsonAbierto] = useState(false);
    const tieneJson = payloadCrudo !== null && payloadCrudo !== undefined;

    return (
        <div className="flex items-center gap-2">
            <Dialog open={jsonAbierto} onOpenChange={setJsonAbierto}>
                <DialogTrigger asChild>
                    <Button variant="outline" size="sm" disabled={!tieneJson}>
                        <FileJson className="size-4" />
                        Ver JSON
                    </Button>
                </DialogTrigger>
                <DialogContent className="max-w-2xl">
                    <DialogHeader>
                        <DialogTitle>
                            Payload crudo de Mercado Público
                        </DialogTitle>
                    </DialogHeader>
                    <pre className="max-h-[60vh] overflow-auto rounded-md bg-muted p-3 text-xs">
                        {JSON.stringify(payloadCrudo, null, 2)}
                    </pre>
                </DialogContent>
            </Dialog>

            <Button variant="outline" size="sm" asChild>
                <a href={urlPdf} target="_blank" rel="noopener noreferrer">
                    <FileText className="size-4" />
                    Ver PDF
                </a>
            </Button>

            <Button variant="outline" size="sm" asChild>
                <a href={urlDetalle} target="_blank" rel="noopener noreferrer">
                    <ExternalLink className="size-4" />
                    Mercado Público
                </a>
            </Button>
        </div>
    );
}

type EventoCronograma = {
    estado: string | null;
    fecha: string | null;
};

/**
 * Mercado Público entrega la fecha de cada hito como solo-fecha (`2026-04-20`)
 * o como fecha y hora (`2026-04-20 09:15:00`); se muestra la hora únicamente
 * cuando viene informada, en vez de inventar una hora `00:00` engañosa.
 */
function formatearFechaHora(fecha: string | null): string {
    if (!fecha) {
        return '—';
    }

    const soloFecha = /^\d{4}-\d{2}-\d{2}$/.test(fecha.trim());
    const iso = fecha.replace(' ', 'T');

    if (Number.isNaN(new Date(iso).getTime())) {
        return fecha;
    }

    return soloFecha ? formatFecha(iso) : formatFechaHora(iso);
}

export function CronogramaTimeline({
    eventos,
}: {
    eventos: EventoCronograma[];
}) {
    if (eventos.length === 0) {
        return (
            <p className="text-sm text-muted-foreground">
                Sin cronograma informado.
            </p>
        );
    }

    return (
        <ol className="flex items-start">
            {eventos.map((evento, i) => {
                const completado = Boolean(evento.fecha);

                return (
                    <li
                        key={i}
                        className="flex flex-1 items-center last:flex-none"
                    >
                        <div className="flex flex-col items-center gap-2 text-center">
                            <span
                                className={
                                    completado
                                        ? 'flex size-8 items-center justify-center rounded-full border-2 border-success bg-success/10 text-success'
                                        : 'flex size-8 items-center justify-center rounded-full border-2 border-muted-foreground/30 text-muted-foreground'
                                }
                            >
                                <Check className="size-4" />
                            </span>
                            <div className="space-y-0.5">
                                <p className="text-xs font-medium tracking-wide uppercase">
                                    {evento.estado ?? 'Sin estado'}
                                </p>
                                <p className="text-xs text-muted-foreground">
                                    {formatearFechaHora(evento.fecha)}
                                </p>
                                {completado && (
                                    <p className="text-xs font-medium text-success">
                                        Completado
                                    </p>
                                )}
                            </div>
                        </div>
                        {i < eventos.length - 1 && (
                            <span
                                className="mx-2 h-0.5 flex-1 bg-success/40"
                                aria-hidden
                            />
                        )}
                    </li>
                );
            })}
        </ol>
    );
}
