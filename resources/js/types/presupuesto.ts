export type ImportacionPresupuesto = {
    id: number;
    nro_version: string;
    anio: number;
    estado: string;
    total_recibidos: number;
    total_creados: number;
    total_actualizados: number;
    total_omitidos: number;
    total_fallidos: number;
    advertencias: string[] | null;
    iniciado_en: string | null;
    finalizado_en: string | null;
    creado_por: string | null;
};

export type Presupuesto = {
    id: number;
    anio: number;
    monto_asignado: string;
    cfinanciero: { id: number; codigo: string; nombre: string };
    catalogo: { id: number; codigo: string; nombre: string };
    plan_tarea: { id: number; codigo: string; nombre: string };
};
