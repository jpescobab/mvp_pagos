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
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';

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
 * Acciones del encabezado de la ficha de una OC: "Ver JSON" muestra el
 * payload crudo del snapshot ya vinculado (sin volver a consultar Mercado
 * Público), mientras que "Ver PDF" y "Ver en Mercado Público" quedan
 * deshabilitadas porque hoy no existe una fuente de PDF ni un enlace externo
 * verificado hacia el detalle público de una OC individual.
 */
export function AccionesEncabezadoFichaMercadoPublico({
    payloadCrudo,
}: {
    payloadCrudo: unknown;
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

            <Tooltip>
                <TooltipTrigger asChild>
                    <span>
                        <Button variant="outline" size="sm" disabled>
                            <FileText className="size-4" />
                            Ver PDF
                        </Button>
                    </span>
                </TooltipTrigger>
                <TooltipContent>Disponible próximamente</TooltipContent>
            </Tooltip>

            <Tooltip>
                <TooltipTrigger asChild>
                    <span>
                        <Button variant="outline" size="sm" disabled>
                            <ExternalLink className="size-4" />
                            Mercado Público
                        </Button>
                    </span>
                </TooltipTrigger>
                <TooltipContent>Disponible próximamente</TooltipContent>
            </Tooltip>
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
    const valor = new Date(fecha.replace(' ', 'T'));

    if (Number.isNaN(valor.getTime())) {
        return fecha;
    }

    return soloFecha ? valor.toLocaleDateString() : valor.toLocaleString();
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
