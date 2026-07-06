import type { Proceso } from '@/types/pago-proveedores';

export type CasoPagoProveedorVinculado = {
    id: number;
    sgf_id: string;
};

export type ProcesoAdquisicion = {
    id: number;
    codigo: string;
    modalidad: { codigo: string | null; nombre: string | null };
    ccosto: { codigo: string | null; nombre: string | null };
    proveedor: { nombre: string | null; rutproveedor: string | null };
    monto: string | null;
    objeto: string;
    proceso: Proceso;
    casos_pago_proveedor: CasoPagoProveedorVinculado[];
};

export type ModalidadSeleccionable = {
    id: number;
    codigo: string;
    nombre: string;
};

export type CcostoSeleccionable = {
    id: number;
    codigo: string;
    nombre: string;
};

export type ProveedorSeleccionable = {
    id: number;
    nombre: string;
    rutproveedor: string | null;
};

export type OrganismoCompradorMercadoPublico = {
    nombre: string | null;
    unidad: string | null;
    rut: string | null;
};

export type EventoCronogramaMercadoPublico = {
    estado: string | null;
    fecha: string | null;
};

export type OrdenCompraMercadoPublicoItem = {
    id: number;
    codigo_producto: string | null;
    descripcion: string;
    cantidad: string;
    precio_unitario: string;
    monto_total: string;
};

export type OrdenCompraMercadoPublico = {
    id: number;
    codigo: string;
    estado_mercado_publico: string | null;
    moneda: string | null;
    forma_pago: string | null;
    plazo_entrega_dias: number | null;
    monto_neto: string | null;
    monto_total: string | null;
    fecha_emision: string | null;
    organismo_comprador: OrganismoCompradorMercadoPublico | null;
    cronograma: EventoCronogramaMercadoPublico[];
    payload_crudo?: unknown;
    proveedor: {
        id: number;
        nombre: string;
        rutproveedor: string | null;
    } | null;
    proceso_adquisicion: { id: number; codigo: string } | null;
    items?: OrdenCompraMercadoPublicoItem[];
};

export type ItemPayloadNormalizadoMercadoPublico = {
    codigo_producto: string | null;
    descripcion: string;
    cantidad: number;
    precio_unitario: number;
    monto_total: number;
};

export type PayloadNormalizadoOrdenCompraMercadoPublico = {
    codigo: string;
    estado: string | null;
    moneda: string | null;
    forma_pago: string | null;
    plazo_entrega_dias: number | null;
    monto_neto: number | null;
    monto_total: number | null;
    fecha_emision: string | null;
    organismo_comprador: OrganismoCompradorMercadoPublico;
    cronograma: EventoCronogramaMercadoPublico[];
    proveedor: { rut: string | null; nombre: string | null };
    items: ItemPayloadNormalizadoMercadoPublico[];
};

export type DiferenciaCampoOrdenCompraMercadoPublico = {
    local: unknown;
    api: unknown;
};

export type AdjudicacionLicitacionMercadoPublico = {
    tipo: number | null;
    fecha: string | null;
    numero: string | null;
    numero_oferentes: number | null;
    url_acta: string | null;
};

export type AdjudicacionItemLicitacionMercadoPublico = {
    rut_proveedor: string | null;
    nombre_proveedor: string | null;
    cantidad: number | null;
    monto_unitario: number | null;
};

export type LicitacionMercadoPublicoItem = {
    id: number;
    correlativo: number | null;
    codigo_producto: string | null;
    categoria: string | null;
    nombre_producto: string | null;
    descripcion: string;
    unidad_medida: string | null;
    cantidad: string;
    adjudicacion: AdjudicacionItemLicitacionMercadoPublico | null;
};

export type LicitacionMercadoPublico = {
    id: number;
    codigo: string;
    nombre: string | null;
    estado_mercado_publico: string | null;
    codigo_estado_mercado_publico: number | null;
    moneda: string | null;
    monto_estimado: string | null;
    organismo_comprador: OrganismoCompradorMercadoPublico | null;
    cronograma: EventoCronogramaMercadoPublico[];
    adjudicacion: AdjudicacionLicitacionMercadoPublico | null;
    payload_crudo?: unknown;
    proceso_adquisicion: { id: number; codigo: string } | null;
    items?: LicitacionMercadoPublicoItem[];
};

export type ItemPayloadNormalizadoLicitacionMercadoPublico = {
    correlativo: number | null;
    codigo_producto: string | null;
    categoria: string | null;
    nombre_producto: string | null;
    descripcion: string;
    unidad_medida: string | null;
    cantidad: number;
    adjudicacion: AdjudicacionItemLicitacionMercadoPublico | null;
};

export type PayloadNormalizadoLicitacionMercadoPublico = {
    codigo: string;
    nombre: string | null;
    estado: string | null;
    codigo_estado: number | null;
    moneda: string | null;
    monto_estimado: number | null;
    organismo_comprador: OrganismoCompradorMercadoPublico;
    cronograma: EventoCronogramaMercadoPublico[];
    adjudicacion: AdjudicacionLicitacionMercadoPublico | null;
    items: ItemPayloadNormalizadoLicitacionMercadoPublico[];
};

export type DiferenciaCampoLicitacionMercadoPublico = {
    local: unknown;
    api: unknown;
};
