export type SnapshotSgfResumen = {
    id: number;
    referencia_externa: string;
    hash: string;
    capturado_en: string;
    proveedor: string | null;
    rut: string | null;
    monto: number | null;
    estado_sgf: string | null;
    folio_egreso: string | null;
    numero: string | null;
    periodo: string | null;
    fecha_sii: string | null;
    observacion: string | null;
    caso_id: number | null;
    caso_estado: string | null;
    listo_para_egreso: boolean;
};

export type ResumenImportacionSgf = {
    monto_total: number;
    proveedores_identificados: number;
    proveedores_no_identificados: number;
    casos_listos: number;
    casos_pendientes: number;
};

export type ImportacionSgf = {
    id: number;
    tipo: string;
    mecanismo: string;
    iniciado_por: string | null;
    iniciado_en: string;
    finalizado_en: string | null;
    total_elementos: number;
    estado: string;
    error: string | null;
    snapshots?: SnapshotSgfResumen[];
    resumen?: ResumenImportacionSgf | null;
};
