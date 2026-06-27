export type EstadoWorkflow = {
    codigo: string;
    nombre: string;
    es_inicial: boolean;
    es_final: boolean;
};

export type TransicionWorkflow = {
    codigo: string;
    nombre: string;
    requiere_comentario: boolean;
};

export type HistorialTransicion = {
    transicion: { codigo: string; nombre: string };
    estado_origen: { codigo: string };
    estado_destino: { codigo: string };
    user: { name: string | null };
    comentario: string | null;
    created_at: string;
};

export type ChecklistItem = {
    tipo_documento: string | null;
    tipo_requisito: string;
    estado_cumplimiento: string;
};

export type Checklist = {
    items: ChecklistItem[];
};

export type Proceso = {
    estado_actual: EstadoWorkflow;
    cerrado_en: string | null;
    historial_transiciones?: HistorialTransicion[];
    transiciones_disponibles: TransicionWorkflow[];
    checklist?: Checklist | null;
};

export type CasoPagoProveedor = {
    id: number;
    sgf_id: string;
    proveedor: { nombre: string | null; rutproveedor: string | null };
    monto: string;
    sgf_status: string | null;
    sgf_current_group_raw: string | null;
    proceso: Proceso;
};

export type CasoSeleccionable = {
    id: number;
    sgf_id: string;
    proveedor: { nombre: string | null };
    monto: string;
};

export type EgresoCguItem = {
    caso: { sgf_id: string };
    monto: string;
};

export type EgresoCgu = {
    numero_egreso: string;
    fecha: string;
    monto_total: string;
    observaciones: string | null;
    items: EgresoCguItem[];
};

export type PaginationLink = {
    url: string | null;
    label: string;
    page: number | null;
    active: boolean;
};

export type Paginated<T> = {
    data: T[];
    links: {
        first: string | null;
        last: string | null;
        prev: string | null;
        next: string | null;
    };
    meta: {
        current_page: number;
        from: number | null;
        last_page: number;
        links: PaginationLink[];
        path: string;
        per_page: number;
        to: number | null;
        total: number;
    };
};
