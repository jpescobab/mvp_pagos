export type Indicador = {
    tipo: string;
    valor: string;
    fecha_valor: string | null;
    periodo: string | null;
};

export const ETIQUETAS_INDICADOR: Record<string, string> = {
    UF: 'U.F',
    UTM: 'U.T.M',
    UTA: 'U.T.A',
    IPC: 'I.P.C',
    USD: 'Dólar',
};

export function formatearValorIndicador(indicador: Indicador): string {
    const valor = Number(indicador.valor);

    if (Number.isNaN(valor)) {
        return indicador.valor;
    }

    if (indicador.tipo === 'IPC') {
        return `${new Intl.NumberFormat('es-CL', { maximumFractionDigits: 1 }).format(valor)}%`;
    }

    const decimales =
        indicador.tipo === 'UF' || indicador.tipo === 'USD' ? 2 : 0;

    return `$ ${new Intl.NumberFormat('es-CL', {
        minimumFractionDigits: decimales,
        maximumFractionDigits: decimales,
    }).format(valor)}`;
}
