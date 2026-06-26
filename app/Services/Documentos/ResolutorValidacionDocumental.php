<?php

namespace App\Services\Documentos;

use App\Models\Documento;
use App\Models\Proceso;
use Illuminate\Support\Collection;

class ResolutorValidacionDocumental
{
    /**
     * @param  list<string>  $codigos  Códigos de tipo_documento requeridos
     * @return list<string> Códigos sin un documento vinculado y validado
     */
    public function faltantes(Proceso $proceso, array $codigos): array
    {
        if ($codigos === []) {
            return [];
        }

        $codigosCumplidos = $this->documentosValidados($proceso)
            ->map(fn (Documento $documento) => $documento->tipoDocumento->codigo)
            ->unique()
            ->all();

        return array_values(array_diff($codigos, $codigosCumplidos));
    }

    /**
     * @return Collection<int, Documento>
     */
    private function documentosValidados(Proceso $proceso): Collection
    {
        $documentoIds = $proceso->vinculosDocumento()
            ->where('activo', true)
            ->pluck('documento_id');

        if ($documentoIds->isEmpty()) {
            return collect();
        }

        return Documento::whereIn('id', $documentoIds)
            ->with(['tipoDocumento', 'validaciones'])
            ->get()
            ->filter(fn (Documento $documento) => $documento->estadoVigente() === 'valido')
            ->values();
    }
}
