<?php

namespace App\Http\Controllers\Maestros;

use App\Enums\Maestros\CondicionPago;
use App\Enums\Maestros\Moneda;
use App\Enums\Maestros\RubroProveedor;
use App\Enums\Maestros\TipoContribuyente;
use App\Enums\Maestros\TipoCuentaBancaria;
use App\Http\Controllers\Controller;
use App\Http\Requests\Maestros\StoreProveedorRequest;
use App\Http\Requests\Maestros\UpdateProveedorRequest;
use App\Http\Resources\Maestros\ProveedorResource;
use App\Models\CasoPagoProveedor;
use App\Models\ClienteMedidor;
use App\Models\Factura;
use App\Models\ProcesoAdquisicion;
use App\Models\Proveedor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

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
        Gate::authorize('viewAny', Proveedor::class);

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

    public function create(Request $request): Response
    {
        Gate::authorize('create', Proveedor::class);

        $rutproveedor = $request->string('rutproveedor')->toString();
        $nombre = $request->string('nombre')->toString();

        return Inertia::render('maestros/proveedores/create', [
            'catalogos' => $this->catalogos(),
            'valoresIniciales' => ($rutproveedor !== '' || $nombre !== '')
                ? ['rutproveedor' => $rutproveedor ?: null, 'nombre' => $nombre ?: null]
                : null,
        ]);
    }

    public function store(StoreProveedorRequest $request): RedirectResponse
    {
        Gate::authorize('create', Proveedor::class);

        $datos = $request->safe()->except('documento_respaldo');

        $proveedor = DB::transaction(function () use ($request, $datos) {
            $proveedor = Proveedor::create($datos);

            if ($request->hasFile('documento_respaldo')) {
                $proveedor->update([
                    'documento_respaldo_path' => $this->guardarDocumentoRespaldo($request, $proveedor),
                ]);
            }

            return $proveedor;
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => "Proveedor \"{$proveedor->nombre}\" registrado."]);

        return to_route('maestros.proveedores.index');
    }

    public function show(Proveedor $proveedor): Response
    {
        Gate::authorize('view', $proveedor);

        return Inertia::render('maestros/proveedores/show', [
            'proveedor' => new ProveedorResource($proveedor),
            'catalogos' => $this->catalogos(),
            'tieneDocumentoRespaldo' => $proveedor->documento_respaldo_path !== null,
        ]);
    }

    public function edit(Proveedor $proveedor): Response
    {
        Gate::authorize('update', $proveedor);

        return Inertia::render('maestros/proveedores/edit', [
            'proveedor' => new ProveedorResource($proveedor),
            'catalogos' => $this->catalogos(),
            'tieneDocumentoRespaldo' => $proveedor->documento_respaldo_path !== null,
        ]);
    }

    public function update(UpdateProveedorRequest $request, Proveedor $proveedor): RedirectResponse
    {
        Gate::authorize('update', $proveedor);

        $datos = $request->safe()->except('documento_respaldo');

        DB::transaction(function () use ($request, $proveedor, $datos) {
            if ($request->hasFile('documento_respaldo')) {
                if ($proveedor->documento_respaldo_path !== null) {
                    Storage::disk('local')->delete($proveedor->documento_respaldo_path);
                }

                $datos['documento_respaldo_path'] = $this->guardarDocumentoRespaldo($request, $proveedor);
            }

            $proveedor->update($datos);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => "Proveedor \"{$proveedor->nombre}\" actualizado."]);

        return to_route('maestros.proveedores.show', $proveedor);
    }

    public function destroy(Proveedor $proveedor): RedirectResponse
    {
        Gate::authorize('delete', $proveedor);

        $bloqueo = $this->relacionQueImpideEliminar($proveedor);

        if ($bloqueo !== null) {
            Inertia::flash('toast', ['type' => 'error', 'message' => "No se puede eliminar: tiene {$bloqueo} asociados."]);

            return back();
        }

        $proveedor->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => "Proveedor \"{$proveedor->nombre}\" eliminado."]);

        return to_route('maestros.proveedores.index');
    }

    private function relacionQueImpideEliminar(Proveedor $proveedor): ?string
    {
        if (ClienteMedidor::where('proveedor_id', $proveedor->id)->exists()) {
            return 'clientes medidores';
        }

        if (CasoPagoProveedor::where('proveedor_id', $proveedor->id)->exists()) {
            return 'casos de pago';
        }

        if (Factura::where('proveedor_id', $proveedor->id)->exists()) {
            return 'facturas';
        }

        if (ProcesoAdquisicion::where('proveedor_id', $proveedor->id)->exists()) {
            return 'procesos de adquisición';
        }

        return null;
    }

    private function guardarDocumentoRespaldo(Request $request, Proveedor $proveedor): string
    {
        $extension = $request->file('documento_respaldo')->getClientOriginalExtension();

        $path = $request->file('documento_respaldo')->storeAs(
            "proveedores/{$proveedor->id}",
            "documento-respaldo.{$extension}",
            'local',
        );

        if ($path === false) {
            throw new RuntimeException('No se pudo guardar el documento de respaldo.');
        }

        return $path;
    }

    /**
     * @return array{tiposContribuyente: list<array{value: string, label: string}>, rubros: list<array{value: string, label: string}>, tiposCuenta: list<array{value: string, label: string}>, condicionesPago: list<array{value: string, label: string}>, monedas: list<array{value: string, label: string}>, bancos: list<string>}
     */
    private function catalogos(): array
    {
        return [
            'tiposContribuyente' => $this->opciones(TipoContribuyente::cases()),
            'rubros' => $this->opciones(RubroProveedor::cases()),
            'tiposCuenta' => $this->opciones(TipoCuentaBancaria::cases()),
            'condicionesPago' => $this->opciones(CondicionPago::cases()),
            'monedas' => $this->opciones(Moneda::cases()),
            'bancos' => self::BANCOS_FRECUENTES,
        ];
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
