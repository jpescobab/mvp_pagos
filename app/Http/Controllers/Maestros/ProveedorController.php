<?php

namespace App\Http\Controllers\Maestros;

use App\Enums\Maestros\CondicionPago;
use App\Enums\Maestros\Moneda;
use App\Enums\Maestros\RubroProveedor;
use App\Enums\Maestros\TipoContribuyente;
use App\Enums\Maestros\TipoCuentaBancaria;
use App\Http\Controllers\Controller;
use App\Http\Requests\Maestros\StoreProveedorRequest;
use App\Http\Resources\Maestros\ProveedorResource;
use App\Models\Proveedor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ProveedorController extends Controller
{
    /**
     * Bancos chilenos de uso frecuente. No es un catálogo cerrado: el
     * formulario acepta cualquier otro banco como texto libre.
     *
     * @var list<string>
     */
    private const BANCOS_FRECUENTES = [
        'BancoEstado',
        'Banco de Chile',
        'Banco Santander',
        'Banco BCI',
        'Scotiabank',
        'Banco Itaú',
        'Banco Security',
        'Banco Falabella',
        'Banco Ripley',
        'Banco Consorcio',
        'Banco BICE',
        'HSBC Bank',
    ];

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

    public function create(): Response
    {
        Gate::authorize('create', Proveedor::class);

        return Inertia::render('maestros/proveedores/create', [
            'catalogos' => [
                'tiposContribuyente' => $this->opciones(TipoContribuyente::cases()),
                'rubros' => $this->opciones(RubroProveedor::cases()),
                'tiposCuenta' => $this->opciones(TipoCuentaBancaria::cases()),
                'condicionesPago' => $this->opciones(CondicionPago::cases()),
                'monedas' => $this->opciones(Moneda::cases()),
                'bancos' => self::BANCOS_FRECUENTES,
            ],
        ]);
    }

    public function store(StoreProveedorRequest $request): RedirectResponse
    {
        Gate::authorize('create', Proveedor::class);

        $datos = $request->safe()->except('documento_respaldo');

        $proveedor = DB::transaction(function () use ($request, $datos) {
            $proveedor = Proveedor::create($datos);

            if ($request->hasFile('documento_respaldo')) {
                $extension = $request->file('documento_respaldo')->getClientOriginalExtension();
                $path = $request->file('documento_respaldo')->storeAs(
                    "proveedores/{$proveedor->id}",
                    "documento-respaldo.{$extension}",
                    'local',
                );

                $proveedor->update(['documento_respaldo_path' => $path]);
            }

            return $proveedor;
        });

        return to_route('maestros.proveedores.index')
            ->with('success', "Proveedor \"{$proveedor->nombre}\" registrado.");
    }

    /**
     * @param  list<TipoContribuyente|RubroProveedor|TipoCuentaBancaria|CondicionPago|Moneda>  $casos
     * @return list<array{value: string, label: string}>
     */
    private function opciones(array $casos): array
    {
        return array_map(
            fn ($caso) => ['value' => $caso->value, 'label' => $caso->label()],
            $casos,
        );
    }
}
