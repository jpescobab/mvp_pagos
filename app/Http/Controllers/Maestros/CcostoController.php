<?php

namespace App\Http\Controllers\Maestros;

use App\Http\Controllers\Controller;
use App\Http\Resources\Maestros\CcostoResource;
use App\Models\Ccosto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class CcostoController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Ccosto::class);

        $q = $request->string('q')->toString();

        $ccostos = Ccosto::query()
            ->with('cfinanciero')
            ->when($q !== '', fn ($query) => $query->where(
                fn ($sub) => $sub
                    ->where('codigo', 'like', "%{$q}%")
                    ->orWhere('nombre', 'like', "%{$q}%"),
            ))
            ->orderBy('codigo')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('maestros/ccostos/index', [
            'ccostos' => CcostoResource::collection($ccostos),
            'q' => $q !== '' ? $q : null,
        ]);
    }
}
