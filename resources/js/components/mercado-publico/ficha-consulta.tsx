import type { ReactNode } from 'react';

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
                {encabezado.acciones && (
                    <div className="flex items-center gap-2">
                        {encabezado.acciones}
                    </div>
                )}
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

type EventoCronograma = {
    estado: string | null;
    fecha: string | null;
};

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
        <ol className="flex flex-wrap items-center gap-3">
            {eventos.map((evento, i) => (
                <li key={i} className="flex items-center gap-3 text-sm">
                    <span className="flex items-center gap-2">
                        <span className="font-medium">
                            {evento.estado ?? 'Sin estado'}
                        </span>
                        <span className="text-muted-foreground">
                            {evento.fecha ?? '—'}
                        </span>
                    </span>
                    {i < eventos.length - 1 && (
                        <span className="text-muted-foreground">→</span>
                    )}
                </li>
            ))}
        </ol>
    );
}
