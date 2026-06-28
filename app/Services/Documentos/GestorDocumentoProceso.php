<?php

namespace App\Services\Documentos;

use App\Models\Documento;
use App\Models\Proceso;
use App\Models\TipoDocumento;
use App\Models\User;
use App\Models\VinculoDocumento;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class GestorDocumentoProceso
{
    public function subirYVincular(Proceso $proceso, UploadedFile $archivo, TipoDocumento $tipoDocumento, User $usuario): VinculoDocumento
    {
        return DB::transaction(function () use ($proceso, $archivo, $tipoDocumento, $usuario) {
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

            return $proceso->vinculosDocumento()->create([
                'documento_id' => $documento->id,
                'activo' => true,
            ]);
        });
    }

    public function desvincular(VinculoDocumento $vinculo): void
    {
        $vinculo->update(['activo' => false]);
    }

    public function descargarRutaArchivo(Documento $documento): string
    {
        return Storage::disk('local')->path(
            $documento->versiones()->latest('numero_version')->first()->ruta_archivo,
        );
    }
}
