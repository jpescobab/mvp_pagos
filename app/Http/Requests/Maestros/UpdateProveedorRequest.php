<?php

namespace App\Http\Requests\Maestros;

use App\Enums\Maestros\CondicionPago;
use App\Enums\Maestros\Moneda;
use App\Enums\Maestros\RubroProveedor;
use App\Enums\Maestros\TipoContribuyente;
use App\Enums\Maestros\TipoCuentaBancaria;
use App\Models\Proveedor;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProveedorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('core_institucional.administrar');
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        /** @var Proveedor $proveedor */
        $proveedor = $this->route('proveedor');

        return [
            'rutproveedor' => ['required', 'string', 'max:20', Rule::unique('proveedores', 'rutproveedor')->ignore($proveedor->id)],
            'nombre' => ['required', 'string', 'max:255'],
            'giro' => ['nullable', 'string', 'max:255'],
            'tipo_contribuyente' => ['nullable', Rule::enum(TipoContribuyente::class)],
            'rubros' => ['nullable', 'array'],
            'rubros.*' => [Rule::enum(RubroProveedor::class)],
            'contacto' => ['nullable', 'string', 'max:255'],
            'contacto_cargo' => ['nullable', 'string', 'max:255'],
            'contacto_telefono' => ['nullable', 'string', 'max:50'],
            'correo' => ['nullable', 'email', 'max:255'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'region' => ['nullable', 'string', 'max:255'],
            'comuna' => ['nullable', 'string', 'max:255'],
            'banco' => ['nullable', 'string', 'max:255'],
            'tipo_cuenta' => ['nullable', Rule::enum(TipoCuentaBancaria::class)],
            'numero_cuenta' => ['nullable', 'string', 'max:50'],
            'condicion_pago' => ['nullable', Rule::enum(CondicionPago::class)],
            'moneda' => ['nullable', Rule::enum(Moneda::class)],
            'correo_pago' => ['nullable', 'email', 'max:255'],
            'documento_respaldo' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:8192'],
            'notas_internas' => ['nullable', 'string', 'max:2000'],
            'activo' => ['boolean'],
        ];
    }
}
