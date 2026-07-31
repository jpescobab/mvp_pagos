import type { Proceso } from './pago-proveedores';

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

export type LineaPresupuestoSeleccionable = {
    id: number;
    codigo: string;
    etiqueta: string;
    denominacion: string;
    unidad_ejecutora: string;
    n_ue: string;
    subtitulo: string;
};

export type ProcesoAdquisicionSeleccionable = {
    id: number;
    codigo: string;
};

export type CertificadoDisponibilidadPresupuestaria = {
    id: number;
    folio: string;
    presupuesto_id: number;
    cfinanciero: { codigo: string | null; nombre: string | null };
    denominacion: string;
    unidad_ejecutora: string;
    n_ue: string;
    subtitulo: string;
    tipo_gasto: 'GO' | 'INI';
    codigo_iniciativa: string | null;
    nombre: string;
    nombre_iniciativa: string | null;
    programa_presupuestario: string;
    caracter_gasto: 'transitorio' | 'permanente';
    medio_solicitud: 'Requerimiento' | 'Oficio' | 'Otro' | null;
    fecha_solicitud: string | null;
    moneda_compra: 'CLP' | 'UF' | 'USD';
    total_moneda_compra: string;
    fecha_paridad: string | null;
    paridad: string | null;
    monto: string;
    anio_validez: number;
    requerimiento_numero: string | null;
    mercado_publico_tipo: string | null;
    mercado_publico_id: number | null;
    proceso_adquisicion?: { id: number; codigo: string } | null;
    saldo_disponible_al_emitir: string | null;
    hubo_sobregiro_al_emitir: boolean;
    cdp_original?: { id: number; folio: string } | null;
    firmado_por?: string | null;
    firmado_en: string | null;
    proceso?: Proceso;
};
