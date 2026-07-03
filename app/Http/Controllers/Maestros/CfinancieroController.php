<?php

namespace App\Http\Controllers\Maestros;

use App\Http\Controllers\Controller;
use App\Http\Resources\Maestros\CfinancieroResource;
use App\Models\Cfinanciero;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class CfinancieroController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Cfinanciero::class);

        $q = $request->string('q')->toString();

        $cfinancieros = Cfinanciero::query()
            ->with('jurisdiccion')
            ->when($q !== '', fn ($query) => $query->where(
                fn ($sub) => $sub
                    ->where('codigo', 'like', "%{$q}%")
                    ->orWhere('nombre', 'like', "%{$q}%"),
            ))
            ->orderBy('codigo')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('maestros/cfinancieros/index', [
            'cfinancieros' => CfinancieroResource::collection($cfinancieros),
            'q' => $q !== '' ? $q : null,
        ]);
    }
}
