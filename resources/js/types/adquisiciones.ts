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
