export type EstadoServicio = 'vigente' | 'por_vencer' | 'vencido' | 'terminado';

export type EstadoPeriodo = 'pagado' | 'en_tramite' | 'sin_caso' | 'ambiguo';

export type ServicioContratado = {
    id: number;
    nombre: string;
    categoria: string;
    monto_mensual_esperado: string;
    moneda: string;
    fecha_inicio: string;
    fecha_fin: string;
    observaciones: string | null;
    terminado_en: string | null;
    estado: EstadoServicio;
    por_vencer: boolean;
    contrato: {
        id: number;
        codigo: string;
        id_institucional: number;
        referencia: string;
        fecha_inicio_vigencia: string;
        fecha_fin_vigencia: string;
    };
    proveedor: {
        id: number | null;
        nombre: string | null;
        rutproveedor: string | null;
    };
    ccosto: { id: number; codigo: string; nombre: string } | null;
    administrador: { id: number; nombre: string } | null;
};

export type PeriodoServicio = {
    periodo: string;
    estado: EstadoPeriodo;
    casos: { id: number; sgf_id: string; estado: string | null }[];
};

export type ContratoOpcion = {
    id: number;
    codigo: string;
    referencia: string;
    proveedor: string | null;
    fecha_inicio_vigencia: string;
    fecha_fin_vigencia: string;
};

export type OpcionesFormularioServicio = {
    categorias: string[];
    contratos: ContratoOpcion[];
    ccostos: { id: number; codigo: string; nombre: string }[];
    funcionarios: { id: number; nombre: string }[];
};
