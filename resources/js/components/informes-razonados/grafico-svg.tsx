import type { ReactNode } from 'react';
import type {
    DatosGraficoCanonico,
    SerieGrafico,
} from '@/types/informes-razonados';

const ANCHO = 640;
const ALTO = 320;
const MARGEN_IZQ = 48;
const MARGEN_DER = 16;
const MARGEN_SUP = 16;
const MARGEN_INF = 44;

// Paleta fija de contraste alto, legible sobre fondo claro y oscuro. Espejo de
// la usada por GraficoSvgRenderer (PHP) para que la vista y la exportación
// dibujen el mismo gráfico.
const PALETA = [
    '#2563eb',
    '#16a34a',
    '#dc2626',
    '#d97706',
    '#7c3aed',
    '#0891b2',
    '#db2777',
    '#65a30d',
];

type Props = {
    tipo: string;
    datos: DatosGraficoCanonico | Record<string, unknown>;
};

function normalizar(
    datos: DatosGraficoCanonico | Record<string, unknown>,
): DatosGraficoCanonico | null {
    const posibles = datos as Partial<DatosGraficoCanonico>;
    const categorias = posibles.categorias;
    const series = posibles.series;

    if (
        !Array.isArray(categorias) ||
        categorias.length === 0 ||
        !Array.isArray(series) ||
        series.length === 0
    ) {
        return null;
    }

    const cantidad = categorias.length;
    const seriesNormalizadas: SerieGrafico[] = [];

    for (const serie of series) {
        const valores = (serie as Partial<SerieGrafico>)?.valores;

        if (
            !Array.isArray(valores) ||
            valores.length !== cantidad ||
            valores.some((v) => typeof v !== 'number' || Number.isNaN(v))
        ) {
            return null;
        }

        seriesNormalizadas.push({
            nombre: String((serie as Partial<SerieGrafico>)?.nombre ?? ''),
            valores: valores.map((v) => Number(v)),
        });
    }

    return {
        categorias: categorias.map((c) => String(c)),
        series: seriesNormalizadas,
    };
}

function esVacio(
    datos: DatosGraficoCanonico | Record<string, unknown>,
): boolean {
    const posibles = datos as Partial<DatosGraficoCanonico>;

    return (
        Object.keys(datos).length === 0 ||
        (Array.isArray(posibles.categorias) &&
            posibles.categorias.length === 0) ||
        (Array.isArray(posibles.series) && posibles.series.length === 0)
    );
}

function recortar(texto: string, largo: number): string {
    return texto.length > largo ? `${texto.slice(0, largo - 1)}…` : texto;
}

function formatoNumero(valor: number): string {
    if (Math.abs(valor - Math.round(valor)) < 0.01) {
        return String(Math.round(valor));
    }

    return valor.toFixed(1);
}

function Fallback({ mensaje }: { mensaje: string }) {
    return <p className="text-sm text-muted-foreground italic">{mensaje}</p>;
}

export function GraficoSvg({ tipo, datos }: Props) {
    const normalizado = normalizar(datos);

    if (!normalizado) {
        return (
            <Fallback
                mensaje={
                    esVacio(datos)
                        ? 'Sin datos para graficar'
                        : 'Formato de datos no reconocido'
                }
            />
        );
    }

    const contenido =
        tipo === 'torta'
            ? renderTorta(normalizado)
            : renderCartesiano(normalizado, tipo);

    return (
        <svg
            viewBox={`0 0 ${ANCHO} ${ALTO}`}
            className="h-auto w-full max-w-2xl text-foreground"
            role="img"
            preserveAspectRatio="xMidYMid meet"
        >
            {contenido}
        </svg>
    );
}

function renderCartesiano(datos: DatosGraficoCanonico, tipo: string) {
    const { categorias, series } = datos;
    const anchoInterno = ANCHO - MARGEN_IZQ - MARGEN_DER;
    const altoInterno = ALTO - MARGEN_SUP - MARGEN_INF;
    const base = MARGEN_SUP + altoInterno;

    const todos = [0, ...series.flatMap((s) => s.valores)];
    const min = Math.min(...todos);
    let max = Math.max(...todos);

    if (min === max) {
        max = min + 1;
    }

    const escalaY = (v: number) =>
        base - (altoInterno * (v - min)) / (max - min);

    const n = categorias.length;
    const anchoBanda = anchoInterno / n;
    const centroX = (i: number) => MARGEN_IZQ + anchoBanda * (i + 0.5);
    const lineaCero = escalaY(Math.max(min, Math.min(max, 0)));

    const marcas = Array.from(
        { length: 5 },
        (_, i) => min + ((max - min) * i) / 4,
    );

    const elementos: ReactNode[] = [];

    // Eje X y grilla horizontal.
    elementos.push(
        <line
            key="eje-x"
            x1={MARGEN_IZQ}
            y1={base}
            x2={MARGEN_IZQ + anchoInterno}
            y2={base}
            stroke="currentColor"
            strokeOpacity={0.35}
        />,
    );
    marcas.forEach((valor, i) => {
        const y = escalaY(valor);
        elementos.push(
            <line
                key={`grid-${i}`}
                x1={MARGEN_IZQ}
                y1={y}
                x2={MARGEN_IZQ + anchoInterno}
                y2={y}
                stroke="currentColor"
                strokeOpacity={0.12}
            />,
            <text
                key={`marca-${i}`}
                x={MARGEN_IZQ - 6}
                y={y + 3}
                textAnchor="end"
                fontSize={10}
                fill="currentColor"
                fillOpacity={0.7}
            >
                {formatoNumero(valor)}
            </text>,
        );
    });

    // Etiquetas de categoría.
    categorias.forEach((categoria, i) => {
        elementos.push(
            <text
                key={`cat-${i}`}
                x={centroX(i)}
                y={base + 16}
                textAnchor="middle"
                fontSize={10}
                fill="currentColor"
                fillOpacity={0.7}
            >
                {recortar(categoria, 12)}
            </text>,
        );
    });

    series.forEach((serie, indiceSerie) => {
        const color = PALETA[indiceSerie % PALETA.length];

        if (tipo === 'barra') {
            const anchoGrupo = anchoBanda * 0.7;
            const anchoBarra = anchoGrupo / series.length;
            serie.valores.forEach((valor, i) => {
                const x =
                    centroX(i) - anchoGrupo / 2 + anchoBarra * indiceSerie;
                const yArriba = Math.min(escalaY(valor), lineaCero);
                const alto = Math.abs(escalaY(valor) - lineaCero);
                elementos.push(
                    <rect
                        key={`barra-${indiceSerie}-${i}`}
                        x={x}
                        y={yArriba}
                        width={Math.max(anchoBarra - 1, 1)}
                        height={Math.max(alto, 0.5)}
                        fill={color}
                    />,
                );
            });
        } else {
            const puntos = serie.valores
                .map((valor, i) => `${centroX(i)},${escalaY(valor)}`)
                .join(' ');

            if (tipo === 'area') {
                const poligono = `${centroX(0)},${lineaCero} ${puntos} ${centroX(
                    n - 1,
                )},${lineaCero}`;
                elementos.push(
                    <polygon
                        key={`area-${indiceSerie}`}
                        points={poligono}
                        fill={color}
                        fillOpacity={0.18}
                        stroke="none"
                    />,
                );
            }

            elementos.push(
                <polyline
                    key={`linea-${indiceSerie}`}
                    points={puntos}
                    fill="none"
                    stroke={color}
                    strokeWidth={2}
                />,
            );
        }
    });

    // Leyenda (multi-serie).
    if (series.length > 1) {
        let desplazamiento = MARGEN_IZQ;
        series.forEach((serie, i) => {
            const color = PALETA[i % PALETA.length];
            const etiqueta = recortar(serie.nombre, 16);
            elementos.push(
                <rect
                    key={`ley-sw-${i}`}
                    x={desplazamiento}
                    y={ALTO - 13}
                    width={10}
                    height={10}
                    fill={color}
                />,
                <text
                    key={`ley-tx-${i}`}
                    x={desplazamiento + 14}
                    y={ALTO - 4}
                    fontSize={10}
                    fill="currentColor"
                    fillOpacity={0.8}
                >
                    {etiqueta}
                </text>,
            );
            desplazamiento += 30 + etiqueta.length * 6.2;
        });
    }

    return elementos;
}

function renderTorta(datos: DatosGraficoCanonico) {
    const { categorias, series } = datos;
    const valores = series[0].valores.map((v) => Math.max(v, 0));
    const total = valores.reduce((a, b) => a + b, 0);

    if (total <= 0) {
        return (
            <text x={ANCHO / 2} y={ALTO / 2}>
                Sin datos para graficar
            </text>
        );
    }

    const cx = MARGEN_IZQ + 110;
    const cy = ALTO / 2;
    const r = 120;

    const elementos: ReactNode[] = [];
    let angulo = -Math.PI / 2;

    valores.forEach((valor, i) => {
        const color = PALETA[i % PALETA.length];
        const fraccion = valor / total;

        if (fraccion <= 0) {
            return;
        }

        if (fraccion >= 0.9999) {
            elementos.push(
                <circle
                    key={`slice-${i}`}
                    cx={cx}
                    cy={cy}
                    r={r}
                    fill={color}
                />,
            );

            return;
        }

        const siguiente = angulo + 2 * Math.PI * fraccion;
        const x1 = cx + r * Math.cos(angulo);
        const y1 = cy + r * Math.sin(angulo);
        const x2 = cx + r * Math.cos(siguiente);
        const y2 = cy + r * Math.sin(siguiente);
        const arcoGrande = siguiente - angulo > Math.PI ? 1 : 0;

        elementos.push(
            <path
                key={`slice-${i}`}
                d={`M ${cx} ${cy} L ${x1} ${y1} A ${r} ${r} 0 ${arcoGrande} 1 ${x2} ${y2} Z`}
                fill={color}
            />,
        );

        angulo = siguiente;
    });

    // Leyenda a la derecha.
    const leyendaX = cx + r + 24;
    categorias.forEach((categoria, i) => {
        const color = PALETA[i % PALETA.length];
        const y = MARGEN_SUP + 12 + i * 18;
        const porcentaje = Math.round((valores[i] / total) * 100);
        elementos.push(
            <rect
                key={`ley-sw-${i}`}
                x={leyendaX}
                y={y - 9}
                width={10}
                height={10}
                fill={color}
            />,
            <text
                key={`ley-tx-${i}`}
                x={leyendaX + 16}
                y={y}
                fontSize={10}
                fill="currentColor"
                fillOpacity={0.8}
            >
                {`${recortar(categoria, 18)} (${porcentaje}%)`}
            </text>,
        );
    });

    return elementos;
}
