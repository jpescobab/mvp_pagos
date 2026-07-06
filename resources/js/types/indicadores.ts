export type IndicadorEconomico = {
    id: number;
    codigo: string;
    fecha_valor: string | null;
    periodo: string | null;
    valor: string;
    fuente: string;
    vigente_desde: string | null;
    vigente_hasta: string | null;
};
