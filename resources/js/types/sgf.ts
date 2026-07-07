export type SnapshotSgfResumen = {
    id: number;
    referencia_externa: string;
    hash: string;
    capturado_en: string;
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
};
