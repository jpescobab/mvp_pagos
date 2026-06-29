export type SnapshotSgfResumen = {
    id: number;
    sgf_id: string;
    hash: string;
    capturado_en: string;
};

export type ImportacionSgf = {
    id: number;
    fuente: string;
    iniciado_por: string | null;
    iniciado_en: string;
    finalizado_en: string | null;
    total_filas: number;
    estado: string;
    snapshots?: SnapshotSgfResumen[];
};
