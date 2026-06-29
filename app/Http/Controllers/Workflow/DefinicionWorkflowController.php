<?php

namespace App\Http\Controllers\Workflow;

use App\Http\Controllers\Controller;
use App\Http\Resources\Workflow\DefinicionWorkflowResource;
use App\Models\DefinicionWorkflow;
use Inertia\Inertia;
use Inertia\Response;

class DefinicionWorkflowController extends Controller
{
    public function index(): Response
    {
        $definiciones = DefinicionWorkflow::withCount(['estados', 'transiciones'])
            ->orderBy('codigo')
            ->get();

        return Inertia::render('workflow/definiciones/index', [
            'definiciones' => DefinicionWorkflowResource::collection($definiciones),
        ]);
    }

    public function show(DefinicionWorkflow $definicionWorkflow): Response
    {
        $definicionWorkflow->load([
            'estados',
            'transiciones.estadoOrigen',
            'transiciones.estadoDestino',
        ]);

        return Inertia::render('workflow/definiciones/show', [
            'definicion' => new DefinicionWorkflowResource($definicionWorkflow),
        ]);
    }
}
