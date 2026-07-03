<?php

namespace App\Http\Controllers\Maestros;

use App\Http\Controllers\Controller;
use App\Http\Resources\Maestros\ClienteMedidorResource;
use App\Models\ClienteMedidor;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ClienteMedidorController extends Controller
{
    public function index(Request $request): Response
    {
        $q = $request->string('q')->toString();

        $clientes = ClienteMedidor::query()
            ->with(['proveedor', 'ccosto'])
            ->when($q !== '', fn ($query) => $query->where(
                fn ($sub) => $sub
                    ->where('numero_cliente', 'like', "%{$q}%")
                    ->orWhereHas('proveedor', fn ($proveedor) => $proveedor->where('nombre', 'like', "%{$q}%"))
                    ->orWhereHas('ccosto', fn ($ccosto) => $ccosto
                        ->where('codigo', 'like', "%{$q}%")
                        ->orWhere('nombre', 'like', "%{$q}%")),
            ))
            ->orderBy('numero_cliente')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('maestros/clientes-medidores/index', [
            'clientes' => ClienteMedidorResource::collection($clientes),
            'q' => $q !== '' ? $q : null,
        ]);
    }
}
