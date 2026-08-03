import type { Proceso } from './pago-proveedores';

export type ContratoProveedor = {
    id: number | null;
    nombre: string | null;
    rutproveedor: string | null;
};

export type ContratoItemConvenioPrecio = {
    id: number;
    descripcion: string;
    unidad_medida: string | null;
    precio_unitario: string;
    moneda: string | null;
    vigente_desde: string | null;
    vigente_hasta: string | null;
};

export type ContratoCuota = {
    id: number;
    numero_cuota: number;
    fecha_vencimiento: string;
    monto: string;
    moneda: string | null;
    estado: 'pendiente' | 'pagada';
    esta_vencida: boolean;
    caso_pago_proveedor: { id: number; sgf_id: string } | null;
};

export type Contrato = {
    id: number;
    id_institucional: number;
    codigo: string;
    modalidad_compra: 'licitacion' | 'trato_directo' | 'fuera_de_portal';
    id_proceso_mp: string | null;
    tipo_contrato: 'contrato' | 'convenio_precio' | 'orden_compra' | 'arriendo';
    referencia: string;
    fecha_inicio_vigencia: string;
    fecha_fin_vigencia: string;
    proveedor: ContratoProveedor;
    materia: string | null;
    submateria: string | null;
    tiene_convenio_precio: boolean;
    tiene_calendario_pago: boolean;
    periodicidad_pago:
        | 'mensual'
        | 'bimestral'
        | 'trimestral'
        | 'semestral'
        | 'anual'
        | 'unica'
        | null;
    monto_total: string | null;
    proceso_adquisicion?: { id: number; codigo: string } | null;
    licitacion_mercado_publico?: { id: number; codigo: string } | null;
    proceso?: Proceso;
    items_convenio_precio?: ContratoItemConvenioPrecio[];
    cuotas?: ContratoCuota[];
    ordenes_compra_mercado_publico?: { id: number; codigo: string }[];
};

export type ContratoSeleccionable = {
    id: number;
    codigo: string;
};

export type CasoPagoProveedorSeleccionable = {
    id: number;
    sgf_id: string;
    proveedor: string | null;
    monto: string;
};
