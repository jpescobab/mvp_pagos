export type Proveedor = {
    id: number;
    rutproveedor: string;
    nombre: string;
    correo: string | null;
    direccion: string | null;
    contacto: string | null;
    activo: boolean;
};
