<?php

namespace App\Http\Controllers\Maestros;

use App\Http\Controllers\Controller;
use App\Http\Resources\Maestros\ClienteMedidorResource;
use App\Models\ClienteMedidor;
use Inertia\Inertia;
use Inertia\Response;

class ClienteMedidorController extends Controller
{
    public function index(): Response
    {
        $clientes = ClienteMedidor::with(['proveedor', 'ccosto'])
            ->orderBy('numero_cliente')
            ->get();

        return Inertia::render('maestros/clientes-medidores/index', [
            'clientes' => ClienteMedidorResource::collection($clientes),
        ]);
    }
}
