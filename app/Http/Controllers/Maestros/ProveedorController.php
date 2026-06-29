<?php

namespace App\Http\Controllers\Maestros;

use App\Http\Controllers\Controller;
use App\Http\Resources\Maestros\ProveedorResource;
use App\Models\Proveedor;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProveedorController extends Controller
{
    public function index(Request $request): Response
    {
        $q = $request->string('q')->toString();

        $proveedores = Proveedor::query()
            ->when($q !== '', fn ($query) => $query->where(
                fn ($sub) => $sub
                    ->where('rutproveedor', 'like', "%{$q}%")
                    ->orWhere('nombre', 'like', "%{$q}%"),
            ))
            ->orderBy('nombre')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('maestros/proveedores/index', [
            'proveedores' => ProveedorResource::collection($proveedores),
            'q' => $q !== '' ? $q : null,
        ]);
    }
}
