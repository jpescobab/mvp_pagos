<?php

namespace App\Services\Sgf;

use App\Models\VersionDocumento;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Repara los nombres con mojibake de documentos SGF ya importados:
 * `documentos.titulo`, `versiones_documento.nombre_archivo`/`ruta_archivo` y
 * el archivo físico. No toca vínculos, hashes ni snapshots (evidencia).
 */
class ReparadorDocumentosSgfExistentes
{
    public const RESULTADO_REPARADO = 'reparado';

    public const RESULTADO_REPARADO_SIN_ARCHIVO = 'reparado_sin_archivo_fisico';

    public const RESULTADO_CONFLICTO = 'conflicto_destino';

    public const RESULTADO_SIMULADO = 'simulado';

    public function __construct(
        private readonly ReparadorNombreArchivoSgf $reparador,
        private readonly AuditLogger $auditoria,
    ) {}

    /**
     * @return list<array{documento_id: int, version_id: int, nombre_antes: string, nombre_despues: string, ruta_antes: string, ruta_despues: string, resultado: string}>
     */
    public function reparar(bool $simular = false): array
    {
        $resultados = [];

        $versiones = VersionDocumento::query()
            ->where('ruta_archivo', 'like', 'sgf-documentos/%')
            ->with('documento')
            ->orderBy('id')
            ->get();

        foreach ($versiones as $version) {
            $nombreAntes = (string) $version->nombre_archivo;
            $rutaAntes = (string) $version->ruta_archivo;
            $nombreDespues = $this->reparador->reparar($nombreAntes);
            $rutaDespues = $this->reparador->repararRuta($rutaAntes);

            if ($nombreDespues === $nombreAntes && $rutaDespues === $rutaAntes) {
                continue;
            }

            $resultados[] = [
                'documento_id' => $version->documento_id,
                'version_id' => $version->id,
                'nombre_antes' => $nombreAntes,
                'nombre_despues' => $nombreDespues,
                'ruta_antes' => $rutaAntes,
                'ruta_despues' => $rutaDespues,
                'resultado' => $simular
                    ? self::RESULTADO_SIMULADO
                    : $this->repararVersion($version, $nombreDespues, $rutaDespues),
            ];
        }

        return $resultados;
    }

    /**
     * Documentos SGF cuyo nombre sigue luciendo corrupto después de la
     * heurística (bytes ya degradados, p. ej. "AÃ¿REOS"): requieren
     * corrección humana, no se adivinan.
     *
     * @return list<array{documento_id: int, nombre: string}>
     */
    public function irreparables(): array
    {
        $irreparables = [];

        $versiones = VersionDocumento::query()
            ->where('ruta_archivo', 'like', 'sgf-documentos/%')
            ->orderBy('id')
            ->get(['id', 'documento_id', 'nombre_archivo']);

        foreach ($versiones as $version) {
            $nombre = (string) $version->nombre_archivo;

            if ($this->reparador->reparar($nombre) === $nombre && preg_match('/[ÃÂ]/u', $nombre) === 1) {
                $irreparables[] = ['documento_id' => $version->documento_id, 'nombre' => $nombre];
            }
        }

        return $irreparables;
    }

    private function repararVersion(VersionDocumento $version, string $nombreDespues, string $rutaDespues): string
    {
        $disco = Storage::disk('local');
        $rutaAntes = (string) $version->ruta_archivo;
        $hayArchivo = $disco->exists($rutaAntes);

        if ($hayArchivo && $rutaDespues !== $rutaAntes && $disco->exists($rutaDespues)) {
            return self::RESULTADO_CONFLICTO;
        }

        DB::transaction(function () use ($version, $nombreDespues, $rutaDespues, $rutaAntes, $hayArchivo, $disco): void {
            $documento = $version->documento;
            $antes = [
                'titulo' => $documento->titulo,
                'nombre_archivo' => $version->nombre_archivo,
                'ruta_archivo' => $rutaAntes,
            ];

            $documento->update(['titulo' => $this->reparador->reparar((string) $documento->titulo)]);
            $version->update(['nombre_archivo' => $nombreDespues, 'ruta_archivo' => $rutaDespues]);

            $this->auditoria->log(
                'documento.nombre_sgf_reparado',
                $documento,
                $antes,
                [
                    'titulo' => $documento->titulo,
                    'nombre_archivo' => $nombreDespues,
                    'ruta_archivo' => $rutaDespues,
                ],
                ['version_documento_id' => $version->id],
            );

            if ($hayArchivo && $rutaDespues !== $rutaAntes) {
                $disco->move($rutaAntes, $rutaDespues);
            }
        });

        return $hayArchivo ? self::RESULTADO_REPARADO : self::RESULTADO_REPARADO_SIN_ARCHIVO;
    }
}
