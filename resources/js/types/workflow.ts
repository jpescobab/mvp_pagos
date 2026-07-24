export type EstadoWorkflowResumen = {
    id: number;
    codigo: string;
    nombre: string;
    es_inicial: boolean;
    es_final: boolean;
};

export type TransicionWorkflowResumen = {
    id: number;
    codigo: string;
    nombre: string;
    estado_origen: string;
    estado_destino: string;
    permiso_requerido: string | null;
    documentos_requeridos: string[] | null;
    requiere_comentario: boolean;
};

export type DefinicionWorkflow = {
    id: number;
    codigo: string;
    nombre: string;
    descripcion: string | null;
    activo: boolean;
    estados_count?: number;
    transiciones_count?: number;
    estados?: EstadoWorkflowResumen[];
    transiciones?: TransicionWorkflowResumen[];
};

export type NotificacionWorkflow = {
    id: string;
    descripcion: string | null;
    estado_nuevo: string | null;
    estado_anterior: string | null;
    url: string | null;
    leida: boolean;
    created_at: string | null;
};
