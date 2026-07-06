import { formatMonto, formatPorcentaje } from '@/lib/format';

export type Indicador = {
    codigo: string;
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
    if (Number.isNaN(Number(indicador.valor))) {
        return indicador.valor;
    }

    if (indicador.codigo === 'IPC') {
        return formatPorcentaje(indicador.valor, 1);
    }

    const decimales =
        indicador.codigo === 'UF' || indicador.codigo === 'USD' ? 2 : 0;

    return formatMonto(indicador.valor, {
        minimumFractionDigits: decimales,
        maximumFractionDigits: decimales,
    });
}
