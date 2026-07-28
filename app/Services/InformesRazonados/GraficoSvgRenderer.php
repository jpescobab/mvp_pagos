<?php

namespace App\Services\InformesRazonados;

/**
 * Renderiza un gráfico de informe razonado como SVG inline, server-side y sin
 * depender de JavaScript, de modo que los formatos que no ejecutan JS (PDF)
 * incluyan el gráfico dibujado. Es un renderer puro: recibe tipo + datos y
 * devuelve una cadena de marcado; no toca base de datos ni estado.
 *
 * Forma canónica esperada de `datos`:
 *   { "categorias": string[], "series": [{ "nombre": string, "valores": number[] }] }
 *
 * Datos vacíos o de forma no reconocida producen un fallback textual, nunca una
 * excepción, para no romper la exportación completa por un solo gráfico.
 */
class GraficoSvgRenderer
{
    private const ANCHO = 640;

    private const ALTO = 320;

    private const MARGEN_IZQ = 48;

    private const MARGEN_DER = 16;

    private const MARGEN_SUP = 16;

    private const MARGEN_INF = 44;

    /**
     * Paleta fija de contraste alto, legible tanto en pantalla como impresa.
     *
     * @var array<int, string>
     */
    private const PALETA = ['#2563eb', '#16a34a', '#dc2626', '#d97706', '#7c3aed', '#0891b2', '#db2777', '#65a30d'];

    private const COLOR_EJE = '#94a3b8';

    private const COLOR_TEXTO = '#334155';

    /**
     * @param  array<string, mixed>  $datos
     */
    public function render(string $tipo, array $datos, string $titulo = ''): string
    {
        $normalizado = $this->normalizar($datos);

        if ($normalizado === null) {
            return $this->fallback(
                $this->esVacio($datos)
                    ? 'Sin datos para graficar'
                    : 'Formato de datos no reconocido'
            );
        }

        [$categorias, $series] = $normalizado;

        return match ($tipo) {
            'barra' => $this->renderCartesiano($categorias, $series, $titulo, 'barra'),
            'linea' => $this->renderCartesiano($categorias, $series, $titulo, 'linea'),
            'area' => $this->renderCartesiano($categorias, $series, $titulo, 'area'),
            'torta' => $this->renderTorta($categorias, $series[0], $titulo),
            default => $this->fallback('Formato de datos no reconocido'),
        };
    }

    /**
     * Devuelve [categorias, series] normalizados si `datos` tiene la forma
     * canónica y es graficable; null en caso contrario.
     *
     * @param  array<string, mixed>  $datos
     * @return array{0: array<int, string>, 1: array<int, array{nombre: string, valores: array<int, float>}>}|null
     */
    private function normalizar(array $datos): ?array
    {
        $categorias = $datos['categorias'] ?? null;
        $series = $datos['series'] ?? null;

        if (! is_array($categorias) || $categorias === [] || ! is_array($series) || $series === []) {
            return null;
        }

        $categorias = array_map(static fn ($c): string => (string) $c, array_values($categorias));
        $cantidad = count($categorias);

        $normalizadas = [];

        foreach (array_values($series) as $serie) {
            if (! is_array($serie)) {
                return null;
            }

            $valores = $serie['valores'] ?? null;

            if (! is_array($valores) || count($valores) !== $cantidad) {
                return null;
            }

            foreach ($valores as $valor) {
                if (! is_numeric($valor)) {
                    return null;
                }
            }

            $normalizadas[] = [
                'nombre' => (string) ($serie['nombre'] ?? ''),
                'valores' => array_map(static fn ($v): float => (float) $v, array_values($valores)),
            ];
        }

        return [$categorias, $normalizadas];
    }

    /**
     * @param  array<string, mixed>  $datos
     */
    private function esVacio(array $datos): bool
    {
        $categorias = $datos['categorias'] ?? null;
        $series = $datos['series'] ?? null;

        return $datos === []
            || (is_array($categorias) && $categorias === [])
            || (is_array($series) && $series === []);
    }

    /**
     * Renderiza barra / línea / área en un plano cartesiano compartido.
     *
     * @param  array<int, string>  $categorias
     * @param  array<int, array{nombre: string, valores: array<int, float>}>  $series
     */
    private function renderCartesiano(array $categorias, array $series, string $titulo, string $modo): string
    {
        $izq = self::MARGEN_IZQ;
        $sup = self::MARGEN_SUP;
        $anchoInterno = self::ANCHO - self::MARGEN_IZQ - self::MARGEN_DER;
        $altoInterno = self::ALTO - self::MARGEN_SUP - self::MARGEN_INF;
        $base = $sup + $altoInterno;

        [$min, $max] = $this->rango($series);
        $escalaY = fn (float $v): float => $base - ($altoInterno * ($v - $min) / ($max - $min));

        $n = count($categorias);
        $anchoBanda = $anchoInterno / $n;
        $centroX = fn (int $i): float => $izq + $anchoBanda * ($i + 0.5);

        $piezas = [];

        // Ejes y grilla horizontal con etiquetas de valor.
        $lineaCero = $escalaY(max($min, min($max, 0.0)));
        $piezas[] = $this->linea($izq, $base, $izq + $anchoInterno, $base, self::COLOR_EJE);
        foreach ($this->marcasY($min, $max) as $valorMarca) {
            $y = $escalaY($valorMarca);
            $piezas[] = $this->linea($izq, $y, $izq + $anchoInterno, $y, '#e2e8f0');
            $piezas[] = $this->texto($izq - 6, $y + 3, $this->formatoNumero($valorMarca), 'end', 10, self::COLOR_TEXTO);
        }

        // Etiquetas de categoría bajo el eje X.
        foreach ($categorias as $i => $categoria) {
            $piezas[] = $this->texto($centroX($i), $base + 16, $this->recortar($categoria, 12), 'middle', 10, self::COLOR_TEXTO);
        }

        foreach ($series as $indiceSerie => $serie) {
            $color = self::PALETA[$indiceSerie % count(self::PALETA)];

            if ($modo === 'barra') {
                $anchoGrupo = $anchoBanda * 0.7;
                $anchoBarra = $anchoGrupo / count($series);
                foreach ($serie['valores'] as $i => $valor) {
                    $x = $centroX($i) - ($anchoGrupo / 2) + ($anchoBarra * $indiceSerie);
                    $yArriba = min($escalaY($valor), $lineaCero);
                    $alto = abs($escalaY($valor) - $lineaCero);
                    $piezas[] = sprintf(
                        '<rect x="%s" y="%s" width="%s" height="%s" fill="%s" />',
                        $this->num($x),
                        $this->num($yArriba),
                        $this->num(max($anchoBarra - 1, 1)),
                        $this->num(max($alto, 0.5)),
                        $color
                    );
                }
            } else {
                $puntos = [];
                foreach ($serie['valores'] as $i => $valor) {
                    $puntos[] = $this->num($centroX($i)).','.$this->num($escalaY($valor));
                }

                if ($modo === 'area') {
                    $poligono = $this->num($centroX(0)).','.$this->num($lineaCero).' '
                        .implode(' ', $puntos).' '
                        .$this->num($centroX($n - 1)).','.$this->num($lineaCero);
                    $piezas[] = sprintf(
                        '<polygon points="%s" fill="%s" fill-opacity="0.18" stroke="none" />',
                        $poligono,
                        $color
                    );
                }

                $piezas[] = sprintf(
                    '<polyline points="%s" fill="none" stroke="%s" stroke-width="2" />',
                    implode(' ', $puntos),
                    $color
                );
            }
        }

        $piezas[] = $this->leyenda($series, $izq, self::ALTO - 4);

        return $this->envolver(implode('', $piezas), $titulo);
    }

    /**
     * @param  array<int, string>  $categorias
     * @param  array{nombre: string, valores: array<int, float>}  $serie
     */
    private function renderTorta(array $categorias, array $serie, string $titulo): string
    {
        $valores = array_map(static fn (float $v): float => max($v, 0.0), $serie['valores']);
        $total = array_sum($valores);

        if ($total <= 0.0) {
            return $this->fallback('Sin datos para graficar');
        }

        $cx = self::MARGEN_IZQ + 110.0;
        $cy = self::ALTO / 2.0;
        $r = 120.0;

        $piezas = [];
        $angulo = -M_PI / 2.0; // arranca arriba

        foreach ($valores as $i => $valor) {
            $color = self::PALETA[$i % count(self::PALETA)];
            $fraccion = $valor / $total;

            if ($fraccion <= 0.0) {
                continue;
            }

            if ($fraccion >= 0.9999) {
                $piezas[] = sprintf('<circle cx="%s" cy="%s" r="%s" fill="%s" />', $this->num($cx), $this->num($cy), $this->num($r), $color);
                break;
            }

            $siguiente = $angulo + 2.0 * M_PI * $fraccion;
            $x1 = $cx + $r * cos($angulo);
            $y1 = $cy + $r * sin($angulo);
            $x2 = $cx + $r * cos($siguiente);
            $y2 = $cy + $r * sin($siguiente);
            $arcoGrande = ($siguiente - $angulo) > M_PI ? 1 : 0;

            $piezas[] = sprintf(
                '<path d="M %s %s L %s %s A %s %s 0 %d 1 %s %s Z" fill="%s" />',
                $this->num($cx),
                $this->num($cy),
                $this->num($x1),
                $this->num($y1),
                $this->num($r),
                $this->num($r),
                $arcoGrande,
                $this->num($x2),
                $this->num($y2),
                $color
            );

            $angulo = $siguiente;
        }

        // Leyenda de categorías a la derecha del círculo.
        $leyendaX = $cx + $r + 24.0;
        $leyendaY = self::MARGEN_SUP + 12.0;
        foreach ($categorias as $i => $categoria) {
            $color = self::PALETA[$i % count(self::PALETA)];
            $y = $leyendaY + $i * 18.0;
            $porcentaje = $total > 0 ? round(($valores[$i] / $total) * 100) : 0;
            $piezas[] = sprintf('<rect x="%s" y="%s" width="10" height="10" fill="%s" />', $this->num($leyendaX), $this->num($y - 9), $color);
            $piezas[] = $this->texto($leyendaX + 16, $y, $this->recortar($categoria, 18).' ('.$porcentaje.'%)', 'start', 10, self::COLOR_TEXTO);
        }

        return $this->envolver(implode('', $piezas), $titulo);
    }

    /**
     * Rango [min, max] a graficar, incluyendo siempre el cero como referencia y
     * evitando un rango degenerado (min == max).
     *
     * @param  array<int, array{nombre: string, valores: array<int, float>}>  $series
     * @return array{0: float, 1: float}
     */
    private function rango(array $series): array
    {
        $todos = [0.0];
        foreach ($series as $serie) {
            foreach ($serie['valores'] as $valor) {
                $todos[] = $valor;
            }
        }

        $min = min($todos);
        $max = max($todos);

        if ($min === $max) {
            $max = $min + 1.0;
        }

        return [$min, $max];
    }

    /**
     * @return array<int, float>
     */
    private function marcasY(float $min, float $max): array
    {
        $pasos = 4;
        $marcas = [];
        for ($i = 0; $i <= $pasos; $i++) {
            $marcas[] = $min + ($max - $min) * ($i / $pasos);
        }

        return $marcas;
    }

    /**
     * @param  array<int, array{nombre: string, valores: array<int, float>}>  $series
     */
    private function leyenda(array $series, float $x, float $y): string
    {
        if (count($series) <= 1) {
            return '';
        }

        $piezas = [];
        $desplazamiento = $x;
        foreach ($series as $i => $serie) {
            $color = self::PALETA[$i % count(self::PALETA)];
            $piezas[] = sprintf('<rect x="%s" y="%s" width="10" height="10" fill="%s" />', $this->num($desplazamiento), $this->num($y - 9), $color);
            $etiqueta = $this->recortar($serie['nombre'], 16);
            $piezas[] = $this->texto($desplazamiento + 14, $y, $etiqueta, 'start', 10, self::COLOR_TEXTO);
            $desplazamiento += 30 + mb_strlen($etiqueta) * 6.2;
        }

        return implode('', $piezas);
    }

    private function envolver(string $contenido, string $titulo): string
    {
        $tituloSeguro = $this->escapar($titulo !== '' ? $titulo : 'Gráfico');

        return sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 %1$d %2$d" width="%1$d" height="%2$d" role="img" aria-label="%3$s" font-family="Arial, Helvetica, sans-serif">'
                .'<title>%3$s</title>%4$s</svg>',
            self::ANCHO,
            self::ALTO,
            $tituloSeguro,
            $contenido
        );
    }

    private function fallback(string $mensaje): string
    {
        return '<p class="grafico-vacio" style="color:#888;font-style:italic;">'.$this->escapar($mensaje).'</p>';
    }

    private function linea(float $x1, float $y1, float $x2, float $y2, string $color): string
    {
        return sprintf(
            '<line x1="%s" y1="%s" x2="%s" y2="%s" stroke="%s" stroke-width="1" />',
            $this->num($x1),
            $this->num($y1),
            $this->num($x2),
            $this->num($y2),
            $color
        );
    }

    private function texto(float $x, float $y, string $texto, string $anclaje, int $tamano, string $color): string
    {
        return sprintf(
            '<text x="%s" y="%s" text-anchor="%s" font-size="%d" fill="%s">%s</text>',
            $this->num($x),
            $this->num($y),
            $anclaje,
            $tamano,
            $color,
            $this->escapar($texto)
        );
    }

    private function recortar(string $texto, int $largo): string
    {
        return mb_strlen($texto) > $largo ? mb_substr($texto, 0, $largo - 1).'…' : $texto;
    }

    private function formatoNumero(float $valor): string
    {
        if (abs($valor - round($valor)) < 0.01) {
            return (string) (int) round($valor);
        }

        return number_format($valor, 1, ',', '.');
    }

    private function num(float $valor): string
    {
        return rtrim(rtrim(number_format($valor, 2, '.', ''), '0'), '.');
    }

    private function escapar(string $texto): string
    {
        return htmlspecialchars($texto, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }
}
