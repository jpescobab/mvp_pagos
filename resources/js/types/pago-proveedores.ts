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
    documento_id: number | null;
};

export type Checklist = {
    items: ChecklistItem[];
};

export type ValidacionDocumentoHistorial = {
    estado: string;
    observacion: string | null;
    validado_por: string | null;
    validado_en: string | null;
};

export type DocumentoVinculado = {
    vinculo_id: number;
    documento_id: number;
    tipo_documento: string | null;
    nombre_archivo: string | null;
    estado_vigente: string;
    validaciones: ValidacionDocumentoHistorial[];
};

export type TipoDocumentoSeleccionable = {
    id: number;
    nombre: string;
};

export type Proceso = {
    id: number;
    estado_actual: EstadoWorkflow;
    cerrado_en: string | null;
    historial_transiciones?: HistorialTransicion[];
    transiciones_disponibles: TransicionWorkflow[];
    checklist?: Checklist | null;
    documentos?: DocumentoVinculado[];
};

export type ProcesoAdquisicionVinculado = {
    id: number;
    codigo: string;
    objeto: string;
};

export type RegistroContableCgu = {
    id: number;
    numero_registro: string;
    fecha_registro: string;
    monto: string;
    observaciones: string | null;
    registrado_por: string | null;
};

export type RegistroPagoBancario = {
    id: number;
    numero_operacion: string;
    fecha_pago: string;
    monto: string;
    banco: string | null;
    registrado_por: string | null;
};

export type SnapshotSgf = {
    id: number;
    capturado_en: string;
    hash: string;
    metodo_captura: string | null;
    payload_crudo: Record<string, unknown>;
    payload_normalizado: Record<string, unknown>;
};

export type EgresoCguAsociado = {
    id: number;
    numero_egreso: string;
    fecha: string;
    monto: string;
};

export type Factura = {
    id: number;
    folio: string;
    monto: string;
    fecha_emision: string;
};

export type CasoPagoProveedor = {
    id: number;
    sgf_id: string;
    proveedor: { nombre: string | null; rutproveedor: string | null };
    monto: string;
    sgf_status: string | null;
    sgf_current_group_raw: string | null;
    periodo: string | null;
    observacion: string | null;
    folio_egreso: string | null;
    numero: string | null;
    fecha_sii: string | null;
    observacion_egreso: string | null;
    proceso: Proceso;
    proceso_adquisicion: ProcesoAdquisicionVinculado | null;
    registros_contables_cgu?: RegistroContableCgu[];
    registros_pago_bancario?: RegistroPagoBancario[];
    snapshots_sgf?: SnapshotSgf[];
    egresos_cgu?: EgresoCguAsociado[];
    facturas?: Factura[];
};

export type ProcesoAdquisicionResumen = {
    id: number;
    codigo: string;
    objeto: string;
    proveedor: string | null;
    monto: string;
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
    id: number;
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
