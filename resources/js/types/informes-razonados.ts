import type { Proceso } from '@/types/pago-proveedores';

export type DefinicionInformeRazonado = {
    id: number;
    codigo: string;
    nombre: string;
    descripcion: string | null;
    activo: boolean;
    ejecuciones_count: number;
};

export type SeccionInformeRazonado = {
    id: number;
    codigo: string;
    titulo: string;
    orden: number;
};

export type MetricaInformeRazonado = {
    id: number;
    codigo: string;
    etiqueta: string;
    valor: string | null;
    unidad: string | null;
};

export type GraficoInformeRazonado = {
    id: number;
    codigo: string;
    titulo: string;
    tipo: string;
};

export type NarrativaInformeRazonado = {
    id: number;
    contenido: string;
    generado_por_ia: boolean;
    revisado_en: string | null;
};

export type ExcepcionInformeRazonado = {
    id: number;
    codigo: string;
    descripcion: string;
    severidad: string;
};

export type SnapshotInformeRazonado = {
    id: number;
    hash: string;
    capturado_en: string;
};

export type AprobacionInformeRazonado = {
    id: number;
    decision: string;
    comentario: string | null;
    aprobado_por: string | null;
    decidido_en: string;
};

export type ExportacionInformeRazonado = {
    id: number;
    formato: string;
    generado_en: string;
};

export type EjecucionInformeRazonado = {
    id: number;
    definicion: { id: number; codigo: string; nombre: string };
    corte: { id: number; estado: string; periodo_codigo: string | null };
    generado_por: string | null;
    generado_en: string;
    proceso?: Proceso;
    secciones?: SeccionInformeRazonado[];
    metricas?: MetricaInformeRazonado[];
    graficos?: GraficoInformeRazonado[];
    narrativas?: NarrativaInformeRazonado[];
    excepciones?: ExcepcionInformeRazonado[];
    snapshots?: SnapshotInformeRazonado[];
    aprobaciones?: AprobacionInformeRazonado[];
    exportaciones?: ExportacionInformeRazonado[];
};

export type DefinicionSeleccionable = {
    id: number;
    codigo: string;
    nombre: string;
};

export type CorteSeleccionable = {
    id: number;
    periodo_reportabilidad_id: number;
    fecha_corte: string;
};
