<?php

namespace App\Services\Documentos;

use App\Models\Documento;
use App\Models\Proceso;
use App\Models\TipoDocumento;
use App\Models\User;
use App\Models\VersionDocumento;
use App\Models\VinculoDocumento;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class GestorDocumentoProceso
{
    public function subirYVincular(Proceso $vinculable, UploadedFile $archivo, TipoDocumento $tipoDocumento, User $usuario): VinculoDocumento
    {
        return DB::transaction(function () use ($vinculable, $archivo, $tipoDocumento, $usuario) {
            $documento = Documento::create([
                'tipo_documento_id' => $tipoDocumento->id,
                'titulo' => $archivo->getClientOriginalName(),
                'subido_por' => $usuario->id,
            ]);

            $rutaArchivo = $archivo->store('documentos', 'local');

            $documento->versiones()->create([
                'numero_version' => 1,
                'ruta_archivo' => $rutaArchivo,
                'nombre_archivo' => $archivo->getClientOriginalName(),
                'tipo_mime' => $archivo->getClientMimeType(),
                'tamano_bytes' => $archivo->getSize(),
                'hash' => hash_file('sha256', $archivo->getRealPath()),
                'subido_por' => $usuario->id,
            ]);

            return $vinculable->vinculosDocumento()->create([
                'documento_id' => $documento->id,
                'activo' => true,
            ]);
        });
    }

    public function subirNuevaVersion(Documento $documento, UploadedFile $archivo, User $usuario): VersionDocumento
    {
        return DB::transaction(function () use ($documento, $archivo, $usuario) {
            $siguienteNumeroVersion = $documento->versiones()->max('numero_version') + 1;
            $rutaArchivo = $archivo->store('documentos', 'local');

            return $documento->versiones()->create([
                'numero_version' => $siguienteNumeroVersion,
                'ruta_archivo' => $rutaArchivo,
                'nombre_archivo' => $archivo->getClientOriginalName(),
                'tipo_mime' => $archivo->getClientMimeType(),
                'tamano_bytes' => $archivo->getSize(),
                'hash' => hash_file('sha256', $archivo->getRealPath()),
                'subido_por' => $usuario->id,
            ]);
        });
    }

    public function desvincular(VinculoDocumento $vinculo): void
    {
        $vinculo->update(['activo' => false]);
    }

    public function reclasificar(Documento $documento, TipoDocumento $tipoDocumento): void
    {
        $documento->update(['tipo_documento_id' => $tipoDocumento->id]);
    }

    /**
     * Reactiva el vínculo inactivo de un documento previamente desvinculado del
     * proceso y lo reclasifica al tipo elegido — permite re-vincular el MISMO
     * documento sin volver a subirlo. `desvincular()` deja el vínculo en
     * `activo=false` (soft-unlink); acá se lo revierte a `activo=true`.
     */
    public function reactivarYReclasificar(Proceso $proceso, Documento $documento, TipoDocumento $tipoDocumento): void
    {
        DB::transaction(function () use ($proceso, $documento, $tipoDocumento): void {
            $proceso->vinculosDocumento()
                ->where('documento_id', $documento->id)
                ->where('activo', false)
                ->latest('id')
                ->first()
                ?->update(['activo' => true]);

            $documento->update(['tipo_documento_id' => $tipoDocumento->id]);
        });
    }

    public function descargarRutaArchivo(Documento $documento): string
    {
        return Storage::disk('local')->path(
            $documento->versiones()->latest('numero_version')->first()->ruta_archivo,
        );
    }
}
