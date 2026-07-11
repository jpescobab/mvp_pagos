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

type ValorFecha = string | number | Date | null | undefined;

function aFecha(valor: ValorFecha): Date | null {
    if (valor === null || valor === undefined || valor === '') {
        return null;
    }

    const fecha = valor instanceof Date ? valor : new Date(valor);

    return Number.isNaN(fecha.getTime()) ? null : fecha;
}

/**
 * Fecha + hora determinista. Usa locale y zona horaria fijos con componentes
 * explícitos para que el render del servidor (SSR de Inertia) y el del cliente
 * produzcan el MISMO texto y no rompan la hidratación de React.
 */
export function formatFechaHora(valor: ValorFecha): string {
    const fecha = aFecha(valor);

    if (fecha === null) {
        return '—';
    }

    return new Intl.DateTimeFormat('es-CL', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: false,
        timeZone: 'America/Santiago',
    }).format(fecha);
}

/**
 * Solo fecha (sin hora), determinista para SSR igual que {@link formatFechaHora}.
 * Usa `UTC` a propósito: las columnas `date` llegan como `...T00:00:00Z`, así
 * que formatearlas en una zona con offset las correría un día. En UTC se
 * preserva la fecha civil tal cual.
 */
export function formatFecha(valor: ValorFecha): string {
    const fecha = aFecha(valor);

    if (fecha === null) {
        return '—';
    }

    return new Intl.DateTimeFormat('es-CL', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        timeZone: 'UTC',
    }).format(fecha);
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

export function restarMontos(
    minuendo: ValorNumerico,
    sustraendo: ValorNumerico,
): number | null {
    const a = aNumero(minuendo);
    const b = aNumero(sustraendo);

    return a === null || b === null ? null : a - b;
}
