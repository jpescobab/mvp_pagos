<?php

namespace App\Services\ConsumoBasico;

use App\Exceptions\ConsumoBasicoException;
use App\Models\CasoPagoProveedor;
use App\Models\ClienteMedidor;
use App\Models\ConsumoBasico;
use App\Models\Documento;
use App\Models\TipoDocumento;
use App\Models\User;
use App\Models\VinculoDocumento;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class ConsumoBasicoService
{
    /**
     * @param  array<string, mixed>  $datos
     * @param  array<string, mixed>|null  $datosClienteMedidor  Datos para resolver/crear el ClienteMedidor por numero_cliente, cuando no se indica cliente_medidor_id.
     */
    public function registrar(CasoPagoProveedor $caso, array $datos, ?array $datosClienteMedidor = null): ConsumoBasico
    {
        // Consulta fresca (no la relación potencialmente ya cacheada en
        // $caso) — necesaria para que dos llamadas seguidas sobre la misma
        // instancia detecten correctamente la unicidad 1:1.
        if ($caso->consumoBasico()->exists()) {
            throw ConsumoBasicoException::casoYaTieneConsumo();
        }

        return DB::transaction(function () use ($caso, $datos, $datosClienteMedidor) {
            $clienteMedidor = $this->resolverClienteMedidor($caso, $datos['cliente_medidor_id'] ?? null, $datosClienteMedidor);

            return ConsumoBasico::create([
                ...$datos,
                'cliente_medidor_id' => $clienteMedidor->id,
                'caso_pago_proveedor_id' => $caso->id,
            ]);
        });
    }

    /**
     * @param  array<string, mixed>  $datos
     */
    public function actualizar(ConsumoBasico $consumoBasico, array $datos): ConsumoBasico
    {
        $consumoBasico->update($datos);

        return $consumoBasico->refresh();
    }

    /**
     * Adjunta el PDF de la boleta/factura como evidencia del ConsumoBasico,
     * reutilizando Documento/VersionDocumento (hash, metadata, trazabilidad)
     * fuera del árbol de Proceso — ver Decision 3 de design.md: controlador y
     * flujo propios en vez de extender GestorDocumentoProceso (tipado a
     * Proceso).
     */
    public function adjuntarDocumento(ConsumoBasico $consumoBasico, UploadedFile $archivo, User $usuario): VinculoDocumento
    {
        return DB::transaction(function () use ($consumoBasico, $archivo, $usuario) {
            // Sembrado en TiposDocumentoSeeder (igual que el resto del
            // catálogo de tipos_documento) — no se crea al vuelo acá para
            // que el id sea estable entre entornos.
            $tipoDocumento = TipoDocumento::where('codigo', 'BOLETA_CONSUMO_BASICO')->firstOrFail();

            $documento = Documento::create([
                'tipo_documento_id' => $tipoDocumento->id,
                'titulo' => $archivo->getClientOriginalName(),
                'subido_por' => $usuario->id,
            ]);

            $rutaArchivo = $archivo->store('documentos', 'local');

            $documento->versiones()->create([
                'numero_version' => 1,
                'ruta_archivo' => $rutaArchivo,
                'nombre_archivo' => $archivo->getClientOriginalName(),
                'tipo_mime' => $archivo->getClientMimeType(),
                'tamano_bytes' => $archivo->getSize(),
                'hash' => hash_file('sha256', $archivo->getRealPath()),
                'subido_por' => $usuario->id,
            ]);

            return $consumoBasico->vinculosDocumento()->create([
                'documento_id' => $documento->id,
                'activo' => true,
            ]);
        });
    }

    /**
     * Determina si un CasoPagoProveedor es candidato a completar su detalle
     * de consumo: su proveedor tiene al menos un ClienteMedidor asociado.
     * Consulta simple contra el catálogo existente — nunca lee el PDF.
     */
    public function esCandidatoServicioBasico(CasoPagoProveedor $caso): bool
    {
        if ($caso->proveedor_id === null) {
            return false;
        }

        return ClienteMedidor::where('proveedor_id', $caso->proveedor_id)->exists();
    }

    /**
     * Resuelve el ClienteMedidor por id ya existente, o por numero_cliente
     * (crea uno nuevo al vuelo si ese numero_cliente no está en el
     * catálogo) — mismo espíritu "resolver o crear" que
     * ContratoService::resolverProveedor().
     *
     * @param  array<string, mixed>|null  $datosClienteMedidor
     */
    private function resolverClienteMedidor(CasoPagoProveedor $caso, ?int $clienteMedidorId, ?array $datosClienteMedidor): ClienteMedidor
    {
        if ($clienteMedidorId !== null) {
            return ClienteMedidor::findOrFail($clienteMedidorId);
        }

        $numeroCliente = (string) ($datosClienteMedidor['numero_cliente'] ?? '');

        $clienteMedidor = ClienteMedidor::where('numero_cliente', $numeroCliente)->first();

        if ($clienteMedidor !== null) {
            return $clienteMedidor;
        }

        return ClienteMedidor::create([
            'numero_cliente' => $numeroCliente,
            'proveedor_id' => $caso->proveedor_id,
            'ccosto_id' => $datosClienteMedidor['ccosto_id'] ?? null,
            'tipo_suministro' => $datosClienteMedidor['tipo_suministro'] ?? '',
            'direccion_suministro' => $datosClienteMedidor['direccion_suministro'] ?? null,
            'activo' => true,
        ]);
    }
}
