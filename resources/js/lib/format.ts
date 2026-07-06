type ValorNumerico = number | string | null | undefined;

function aNumero(valor: ValorNumerico): number | null {
    if (valor === null || valor === undefined) {
        return null;
    }

    const numero = typeof valor === 'number' ? valor : Number(valor);

    return Number.isNaN(numero) ? null : numero;
}

export function formatNumero(
    valor: ValorNumerico,
    opciones?: Intl.NumberFormatOptions,
): string {
    const numero = aNumero(valor);

    if (numero === null) {
        return '—';
    }

    return new Intl.NumberFormat('es-CL', opciones).format(numero);
}

export function formatMonto(
    valor: ValorNumerico,
    opciones?: Intl.NumberFormatOptions,
): string {
    const numero = aNumero(valor);

    if (numero === null) {
        return '—';
    }

    return `$ ${new Intl.NumberFormat('es-CL', {
        maximumFractionDigits: 0,
        ...opciones,
    }).format(numero)}`;
}

export function formatPorcentaje(
    valor: ValorNumerico,
    decimales: number = 1,
): string {
    const numero = aNumero(valor);

    if (numero === null) {
        return '—';
    }

    return `${new Intl.NumberFormat('es-CL', {
        minimumFractionDigits: decimales,
        maximumFractionDigits: decimales,
    }).format(numero)}%`;
}

export function esNegativo(valor: ValorNumerico): boolean {
    const numero = aNumero(valor);

    return numero !== null && numero < 0;
}
