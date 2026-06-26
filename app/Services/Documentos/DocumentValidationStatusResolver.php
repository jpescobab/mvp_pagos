<?php

namespace App\Services\Documentos;

use App\Models\Document;
use App\Models\Process;
use Illuminate\Support\Collection;

class DocumentValidationStatusResolver
{
    /**
     * @param  list<string>  $codigos  Códigos de document_type requeridos
     * @return list<string> Códigos sin un documento vinculado y validado
     */
    public function faltantes(Process $process, array $codigos): array
    {
        if ($codigos === []) {
            return [];
        }

        $codigosCumplidos = $this->documentosValidados($process)
            ->map(fn (Document $document) => $document->documentType->codigo)
            ->unique()
            ->all();

        return array_values(array_diff($codigos, $codigosCumplidos));
    }

    /**
     * @return Collection<int, Document>
     */
    private function documentosValidados(Process $process): Collection
    {
        $documentIds = $process->documentLinks()
            ->where('activo', true)
            ->pluck('document_id');

        if ($documentIds->isEmpty()) {
            return collect();
        }

        return Document::whereIn('id', $documentIds)
            ->with(['documentType', 'validations'])
            ->get()
            ->filter(fn (Document $document) => $document->estadoVigente() === 'valido')
            ->values();
    }
}
