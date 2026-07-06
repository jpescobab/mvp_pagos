export type Proveedor = {
    id: number;
    rutproveedor: string;
    nombre: string;
    correo: string | null;
    direccion: string | null;
    contacto: string | null;
    activo: boolean;
    giro: string | null;
    tipo_contribuyente: string | null;
    rubros: string[] | null;
    contacto_cargo: string | null;
    contacto_telefono: string | null;
    region: string | null;
    comuna: string | null;
    banco: string | null;
    tipo_cuenta: string | null;
    numero_cuenta: string | null;
    condicion_pago: string | null;
    moneda: string | null;
    correo_pago: string | null;
    notas_internas: string | null;
};

export type OpcionCatalogo = {
    value: string;
    label: string;
};

export type CatalogosProveedor = {
    tiposContribuyente: OpcionCatalogo[];
    rubros: OpcionCatalogo[];
    tiposCuenta: OpcionCatalogo[];
    condicionesPago: OpcionCatalogo[];
    monedas: OpcionCatalogo[];
    bancos: string[];
};

export type ClienteMedidor = {
    id: number;
    numero_cliente: string;
    proveedor: { id: number; nombre: string; rutproveedor: string } | null;
    ccosto: { id: number; codigo: string; nombre: string };
    tipo_suministro: string;
    direccion_suministro: string | null;
    activo: boolean;
};

export type Cfinanciero = {
    id: number;
    codigo: string;
    nombre: string;
    activo: boolean;
    jurisdiccion: { id: number; nombre: string };
};

export type Ccosto = {
    id: number;
    codigo: string;
    nombre: string;
    cod_edificio: string | null;
    activo: boolean;
    cfinanciero: { id: number; nombre: string };
};

export type Asignacion = {
    id: number;
    codigo: string;
    nombre: string;
    descripcion: string | null;
    activo: boolean;
};

export type Catalogo = {
    id: number;
    codigo: string;
    nombre: string;
    descripcion: string | null;
    activo: boolean;
};

export type ItemPresupuestario = {
    id: number;
    codigo: string;
    nombre: string;
    descripcion: string | null;
    activo: boolean;
    asignaciones?: Asignacion[];
    catalogos?: Catalogo[];
};

export type JurisdiccionSeleccionable = {
    id: number;
    codigo: string;
    nombre: string;
};

export type CfinancieroSeleccionable = {
    id: number;
    codigo: string;
    nombre: string;
};
