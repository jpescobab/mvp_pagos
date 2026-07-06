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
