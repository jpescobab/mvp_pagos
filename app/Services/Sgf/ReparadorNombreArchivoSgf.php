<?php

namespace App\Services\Sgf;

/**
 * Repara el mojibake (UTF-8 interpretado como Latin-1) de los nombres de
 * archivo que entrega SGF, p. ej. "NÂ°" → "N°", "aÃ©reo" → "aéreo".
 *
 * Contrato (idéntico al helper equivalente de sgf-scraper.js): solo se
 * repara si el nombre contiene "Ã"/"Â", todos sus caracteres caben en
 * Latin-1 y la re-decodificación como UTF-8 es válida y distinta. En
 * cualquier otro caso se devuelve intacto.
 */
class ReparadorNombreArchivoSgf
{
    public function reparar(string $nombre): string
    {
        if (preg_match('/[ÃÂ]/u', $nombre) !== 1) {
            return $nombre;
        }

        if (preg_match('/[^\x{0000}-\x{00FF}]/u', $nombre) === 1) {
            return $nombre;
        }

        // "Ã¿"/"Â¿": el byte de continuación (0x80-0x9F, p. ej. de "É" o
        // "Ñ") ya llegó degradado a "¿". El roundtrip daría un carácter
        // válido pero equivocado ("ÿ"), así que no se repara: se deja para
        // revisión humana en vez de inventar texto.
        if (preg_match('/[ÃÂ]¿/u', $nombre) === 1) {
            return $nombre;
        }

        $bytes = mb_convert_encoding($nombre, 'ISO-8859-1', 'UTF-8');

        if (! mb_check_encoding($bytes, 'UTF-8') || $bytes === $nombre) {
            return $nombre;
        }

        return $bytes;
    }

    /**
     * Repara solo el nombre de archivo (último segmento) de una ruta,
     * conservando los directorios intactos.
     */
    public function repararRuta(string $ruta): string
    {
        $directorio = dirname($ruta);
        $nombre = basename($ruta);
        $reparado = $this->reparar($nombre);

        if ($reparado === $nombre) {
            return $ruta;
        }

        return $directorio === '.' ? $reparado : $directorio.'/'.$reparado;
    }
}
