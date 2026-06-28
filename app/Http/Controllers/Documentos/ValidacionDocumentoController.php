<?php

namespace App\Http\Controllers\Documentos;

use App\Http\Controllers\Controller;
use App\Http\Requests\Documentos\ValidarDocumentoRequest;
use App\Models\Documento;
use App\Models\Proceso;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class ValidacionDocumentoController extends Controller
{
    public function store(Proceso $proceso, Documento $documento, ValidarDocumentoRequest $request): RedirectResponse
    {
        Gate::authorize('validarDocumentos', $proceso);

        $documento->validaciones()->create([
            'estado' => $request->string('estado')->toString(),
            'observacion' => $request->string('observacion')->toString() ?: null,
            'validado_por' => $request->user()->id,
            'validado_en' => now(),
        ]);

        return back();
    }
}
