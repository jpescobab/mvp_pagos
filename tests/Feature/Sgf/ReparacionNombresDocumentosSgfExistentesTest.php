<?php

use App\Models\AuditLog;
use App\Models\Documento;
use App\Models\TipoDocumento;
use App\Models\VersionDocumento;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
});

function crearDocumentoSgfConRuta(string $nombre, string $ruta, string $hash = 'hash-original'): VersionDocumento
{
    $tipo = TipoDocumento::firstOrCreate(['codigo' => 'FACTURA'], ['nombre' => 'Factura']);
    $documento = Documento::create(['tipo_documento_id' => $tipo->id, 'titulo' => $nombre]);

    return $documento->versiones()->create([
        'numero_version' => 1,
        'ruta_archivo' => $ruta,
        'nombre_archivo' => $nombre,
        'hash' => $hash,
    ]);
}

test('--dry-run lista los documentos a reparar sin modificar base de datos ni archivos', function () {
    Storage::disk('local')->put('sgf-documentos/1032/CT NÂ°957.pdf', 'x');
    $version = crearDocumentoSgfConRuta('CT NÂ°957.pdf', 'sgf-documentos/1032/CT NÂ°957.pdf');

    $this->artisan('sgf:reparar-nombres-documentos', ['--dry-run' => true])->assertSuccessful();

    expect($version->fresh()->ruta_archivo)->toBe('sgf-documentos/1032/CT NÂ°957.pdf')
        ->and($version->documento->fresh()->titulo)->toBe('CT NÂ°957.pdf');
    Storage::disk('local')->assertExists('sgf-documentos/1032/CT NÂ°957.pdf');
    expect(AuditLog::count())->toBe(0);
});

test('repara título, nombre y ruta, renombra el archivo y deja vínculos y hash intactos', function () {
    Storage::disk('local')->put('sgf-documentos/1032/CT NÂ°957.pdf', 'contenido');
    $version = crearDocumentoSgfConRuta('CT NÂ°957.pdf', 'sgf-documentos/1032/CT NÂ°957.pdf', 'hash-fijo');
    $documentoId = $version->documento_id;

    $this->artisan('sgf:reparar-nombres-documentos')->assertSuccessful();

    $version = $version->fresh();

    expect($version->nombre_archivo)->toBe('CT N°957.pdf')
        ->and($version->ruta_archivo)->toBe('sgf-documentos/1032/CT N°957.pdf')
        ->and($version->hash)->toBe('hash-fijo')
        ->and($version->documento_id)->toBe($documentoId)
        ->and(Documento::find($documentoId)->titulo)->toBe('CT N°957.pdf');

    Storage::disk('local')->assertExists('sgf-documentos/1032/CT N°957.pdf');
    Storage::disk('local')->assertMissing('sgf-documentos/1032/CT NÂ°957.pdf');
    expect(AuditLog::where('action', 'documento.nombre_sgf_reparado')->count())->toBe(1);
});

test('omite y reporta el documento cuando el archivo reparado ya existe en el destino', function () {
    Storage::disk('local')->put('sgf-documentos/1032/CT NÂ°957.pdf', 'corrupto');
    Storage::disk('local')->put('sgf-documentos/1032/CT N°957.pdf', 'ya existente');
    $version = crearDocumentoSgfConRuta('CT NÂ°957.pdf', 'sgf-documentos/1032/CT NÂ°957.pdf');

    $this->artisan('sgf:reparar-nombres-documentos')->assertSuccessful();

    expect($version->fresh()->ruta_archivo)->toBe('sgf-documentos/1032/CT NÂ°957.pdf')
        ->and(Storage::disk('local')->get('sgf-documentos/1032/CT N°957.pdf'))->toBe('ya existente');
    Storage::disk('local')->assertExists('sgf-documentos/1032/CT NÂ°957.pdf');
});

test('repara los nombres en la base aunque el archivo físico no exista', function () {
    $version = crearDocumentoSgfConRuta('pasaje_aÃ©reo.pdf', 'sgf-documentos/1048/pasaje_aÃ©reo.pdf');

    $this->artisan('sgf:reparar-nombres-documentos')->assertSuccessful();

    expect($version->fresh()->ruta_archivo)->toBe('sgf-documentos/1048/pasaje_aéreo.pdf');
});

test('una segunda ejecución no encuentra nada que reparar', function () {
    Storage::disk('local')->put('sgf-documentos/1032/CT NÂ°957.pdf', 'x');
    crearDocumentoSgfConRuta('CT NÂ°957.pdf', 'sgf-documentos/1032/CT NÂ°957.pdf');

    $this->artisan('sgf:reparar-nombres-documentos')->assertSuccessful();
    $this->artisan('sgf:reparar-nombres-documentos')
        ->expectsOutputToContain('No hay nombres de documentos SGF para reparar')
        ->assertSuccessful();

    expect(AuditLog::where('action', 'documento.nombre_sgf_reparado')->count())->toBe(1);
});

test('no toca documentos que no son de SGF ni nombres sin mojibake', function () {
    $ajeno = crearDocumentoSgfConRuta('CT NÂ°957.pdf', 'documentos/abc123.pdf');
    $limpio = crearDocumentoSgfConRuta('Garantía.pdf', 'sgf-documentos/1032/Garantía.pdf');

    $this->artisan('sgf:reparar-nombres-documentos')->assertSuccessful();

    expect($ajeno->fresh()->nombre_archivo)->toBe('CT NÂ°957.pdf')
        ->and($limpio->fresh()->nombre_archivo)->toBe('Garantía.pdf');
});

test('un nombre con bytes degradados no se repara y se reporta como irreparable', function () {
    $version = crearDocumentoSgfConRuta('PASAJES_AÃ¿REOS.pdf', 'sgf-documentos/1043/PASAJES_AÃ¿REOS.pdf');

    $this->artisan('sgf:reparar-nombres-documentos')
        ->expectsOutputToContain('no se pueden reparar automáticamente')
        ->assertSuccessful();

    expect($version->fresh()->nombre_archivo)->toBe('PASAJES_AÃ¿REOS.pdf');
});
