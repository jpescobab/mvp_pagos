<?php

namespace App\Http\Controllers\Maestros;

use App\Http\Controllers\Controller;
use App\Http\Requests\Maestros\StoreClienteMedidorRequest;
use App\Http\Requests\Maestros\UpdateClienteMedidorRequest;
use App\Http\Resources\Maestros\ClienteMedidorResource;
use App\Models\Ccosto;
use App\Models\ClienteMedidor;
use App\Models\Proveedor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ClienteMedidorController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', ClienteMedidor::class);

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

    public function create(): Response
    {
        Gate::authorize('create', ClienteMedidor::class);

        return Inertia::render('maestros/clientes-medidores/create', $this->catalogos());
    }

    public function store(StoreClienteMedidorRequest $request): RedirectResponse
    {
        Gate::authorize('create', ClienteMedidor::class);

        $clienteMedidor = ClienteMedidor::create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => "Cliente medidor \"{$clienteMedidor->numero_cliente}\" registrado."]);

        return to_route('maestros.clientes-medidores.index');
    }

    public function show(ClienteMedidor $clienteMedidor): Response
    {
        Gate::authorize('view', $clienteMedidor);

        $clienteMedidor->load(['proveedor', 'ccosto']);

        return Inertia::render('maestros/clientes-medidores/show', [
            'clienteMedidor' => new ClienteMedidorResource($clienteMedidor),
        ]);
    }

    public function edit(ClienteMedidor $clienteMedidor): Response
    {
        Gate::authorize('update', $clienteMedidor);

        $clienteMedidor->load(['proveedor', 'ccosto']);

        return Inertia::render('maestros/clientes-medidores/edit', [
            'clienteMedidor' => new ClienteMedidorResource($clienteMedidor),
            ...$this->catalogos(),
        ]);
    }

    public function update(UpdateClienteMedidorRequest $request, ClienteMedidor $clienteMedidor): RedirectResponse
    {
        Gate::authorize('update', $clienteMedidor);

        $clienteMedidor->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => "Cliente medidor \"{$clienteMedidor->numero_cliente}\" actualizado."]);

        return to_route('maestros.clientes-medidores.show', $clienteMedidor);
    }

    public function destroy(ClienteMedidor $clienteMedidor): RedirectResponse
    {
        Gate::authorize('delete', $clienteMedidor);

        $clienteMedidor->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => "Cliente medidor \"{$clienteMedidor->numero_cliente}\" eliminado."]);

        return to_route('maestros.clientes-medidores.index');
    }

    /**
     * @return array<string, mixed>
     */
    private function catalogos(): array
    {
        return [
            'ccostos' => Ccosto::all()->map(fn (Ccosto $ccosto) => [
                'id' => $ccosto->id,
                'codigo' => $ccosto->codigo,
                'nombre' => $ccosto->nombre,
            ]),
            'proveedores' => Proveedor::activos()->get()->map(fn (Proveedor $proveedor) => [
                'id' => $proveedor->id,
                'nombre' => $proveedor->nombre,
                'rutproveedor' => $proveedor->rutproveedor,
            ]),
        ];
    }
}
