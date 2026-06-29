export type CorteReportabilidad = {
    id: number;
    fecha_corte: string;
    estado: string;
    publicado_por: string | null;
    publicado_en: string | null;
    periodo?: { id: number; codigo: string };
    items_count: number;
    snapshots_count: number;
    ejecuciones_informe_razonado_count: number;
};

export type PeriodoReportabilidad = {
    id: number;
    codigo: string;
    fecha_inicio: string;
    fecha_fin: string;
    estado: string;
    cortes_count: number;
    cortes: CorteReportabilidad[];
};
