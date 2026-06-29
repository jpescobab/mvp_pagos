export type Proveedor = {
    id: number;
    rutproveedor: string;
    nombre: string;
    correo: string | null;
    direccion: string | null;
    contacto: string | null;
    activo: boolean;
};

export type ClienteMedidor = {
    id: number;
    numero_cliente: string;
    proveedor: { nombre: string; rutproveedor: string } | null;
    ccosto: { codigo: string; nombre: string };
    tipo_suministro: string;
    direccion_suministro: string | null;
    activo: boolean;
};
