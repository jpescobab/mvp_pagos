export type SistemaExterno = {
    id: number;
    codigo: string;
    nombre: string;
    tipo_integracion: string;
    activo: boolean;
    trabajos_integracion_count: number;
};

export type SistemaExternoSeleccionable = {
    id: number;
    codigo: string;
    nombre: string;
};

export type PerfilAutenticacionNavegador = {
    id: number;
    nombre: string;
    almacen_secreto: string;
    referencia_secreto: string;
    activo: boolean;
    creado_por: string | null;
};

export type ConectorAutomatizacionNavegador = {
    id: number;
    codigo: string;
    nombre: string;
    descripcion: string | null;
    activo: boolean;
    esta_autorizado: boolean;
    autorizado_por: string | null;
    autorizado_en: string | null;
    sistema_externo: { codigo: string; nombre: string };
    perfiles: PerfilAutenticacionNavegador[];
};
