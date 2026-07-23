<?php

use App\Models\LicitacionMercadoPublico;
use App\Models\SnapshotDatosExterno;
use App\Models\SolicitudApiExterna;
use App\Models\TrabajoIntegracion;
use App\Models\User;
use App\Services\Adquisiciones\DescargaPdfLicitacionMercadoPublicoService;
use Database\Seeders\IntegracionesSeeder;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

const CONTENIDO_PDF_LICITACION = '%PDF-1.4 contenido de prueba';

/**
 * HTML mínimo de la ficha pública con lo que el postback necesita: el control
 * de descarga y el estado del formulario WebForms.
 */
function fichaLicitacionConDescarga(string $viewState = 'VIEWSTATE-FALSO'): string
{
    return <<<HTML
        <html><body><form action="./DetailsAcquisition.aspx?qs=TOKEN">
        <input type="hidden" name="__VIEWSTATE" id="__VIEWSTATE" value="{$viewState}" />
        <input type="hidden" name="__VIEWSTATEGENERATOR" id="__VIEWSTATEGENERATOR" value="ABC123" />
        <a id="descargar_pdf" href="javascript:__doPostBack('descargar_pdf','')">Descargar ficha</a>
        </form></body></html>
        HTML;
}

function fichaLicitacionSinDescarga(): string
{
    return '<html><body><form><input type="hidden" id="__VIEWSTATE" value="X" /></form></body></html>';
}

/**
 * El flujo real distingue las dos peticiones por la URL (la segunda va contra
 * el `?qs=<token>` al que redirige la primera), pero bajo `Http::fake` no hay
 * redirect: ambas caen en la misma URL. Se distinguen por método, que es lo que
 * de verdad las separa — GET cosecha el formulario, POST dispara la descarga.
 */
function fakeFichaLicitacionYPdf(?string $html = null): void
{
    Http::fake(fn ($request) => $request->method() === 'GET'
        ? Http::response($html ?? fichaLicitacionConDescarga(), 200)
        : Http::response(CONTENIDO_PDF_LICITACION, 200, ['Content-Type' => 'application/pdf']));
}

beforeEach(function () {
    $this->seed(IntegracionesSeeder::class);
    Storage::fake('local');
    $this->servicio = app(DescargaPdfLicitacionMercadoPublicoService::class);
});

test('obtiene el PDF, lo persiste y registra el snapshot con el hash del archivo', function () {
    fakeFichaLicitacionYPdf();

    $usuario = User::factory()->create();
    $this->actingAs($usuario);

    $pdf = $this->servicio->obtener('1057490-49-LP26');

    expect($pdf)->not->toBeNull();
    expect($pdf['contenido'])->toBe(CONTENIDO_PDF_LICITACION);
    expect($pdf['nombre_archivo'])->toBe('PDF1057490-49-LP26.pdf');

    Storage::disk('local')->assertExists('mercado-publico-pdf/PDF1057490-49-LP26.pdf');

    $snapshot = SnapshotDatosExterno::latest('id')->first();
    expect($snapshot->metodo_captura)->toBe('scraping_pdf');
    expect($snapshot->referencia_externa)->toBe('1057490-49-LP26');
    expect($snapshot->capturado_por)->toBe($usuario->id);
    expect($snapshot->payload_crudo['hash_pdf'])->toBe(hash('sha256', CONTENIDO_PDF_LICITACION));
    expect($snapshot->payload_crudo['tamano_bytes'])->toBe(strlen(CONTENIDO_PDF_LICITACION));
    expect($snapshot->payload_crudo['ruta_archivo'])->toBe('mercado-publico-pdf/PDF1057490-49-LP26.pdf');

    expect(TrabajoIntegracion::latest('id')->first()->estado)->toBe('completado');
    expect(SolicitudApiExterna::count())->toBe(2);
});

test('el snapshot queda vinculado a la licitación cuando existe localmente', function () {
    fakeFichaLicitacionYPdf();

    $licitacion = LicitacionMercadoPublico::factory()->create(['codigo' => 'LIC-VINCULADA']);

    $this->servicio->obtener('LIC-VINCULADA');

    $snapshot = SnapshotDatosExterno::where('metodo_captura', 'scraping_pdf')->latest('id')->first();
    expect($snapshot->vinculable_id)->toBe($licitacion->id);
    expect($snapshot->vinculable_type)->toBe($licitacion->getMorphClass());
});

test('entrega el PDF sin crear la licitación cuando no existe localmente', function () {
    fakeFichaLicitacionYPdf();

    $pdf = $this->servicio->obtener('LIC-NO-GUARDADA');

    expect($pdf)->not->toBeNull();
    expect(LicitacionMercadoPublico::where('codigo', 'LIC-NO-GUARDADA')->exists())->toBeFalse();

    $snapshot = SnapshotDatosExterno::where('metodo_captura', 'scraping_pdf')->latest('id')->first();
    expect($snapshot->referencia_externa)->toBe('LIC-NO-GUARDADA');
    expect($snapshot->vinculable_id)->toBeNull();
});

test('la segunda solicitud del mismo código reutiliza el archivo sin salir a la red', function () {
    fakeFichaLicitacionYPdf();

    $this->servicio->obtener('LIC-CACHE');
    Http::assertSentCount(2);

    $segunda = $this->servicio->obtener('LIC-CACHE');

    expect($segunda['contenido'])->toBe(CONTENIDO_PDF_LICITACION);
    Http::assertSentCount(2);
    expect(SnapshotDatosExterno::where('metodo_captura', 'scraping_pdf')->count())->toBe(1);
});

test('vuelve a capturar si el archivo de la captura previa ya no está en disco', function () {
    fakeFichaLicitacionYPdf();

    $this->servicio->obtener('LIC-BORRADO');
    Storage::disk('local')->delete('mercado-publico-pdf/PDFLIC-BORRADO.pdf');

    $segunda = $this->servicio->obtener('LIC-BORRADO');

    expect($segunda)->not->toBeNull();
    Http::assertSentCount(4);
    expect(SnapshotDatosExterno::where('metodo_captura', 'scraping_pdf')->count())->toBe(2);
});

test('falla sin persistir nada cuando la ficha no ofrece la descarga', function () {
    fakeFichaLicitacionYPdf(fichaLicitacionSinDescarga());

    expect($this->servicio->obtener('LIC-SIN-BOTON'))->toBeNull();

    expect(SnapshotDatosExterno::where('metodo_captura', 'scraping_pdf')->count())->toBe(0);
    expect(Storage::disk('local')->allFiles())->toBe([]);
    expect(SolicitudApiExterna::latest('id')->first()->estado)->toBe('no_encontrada');
    expect(TrabajoIntegracion::latest('id')->first()->estado)->toBe('error');
});

test('descarta la respuesta cuando el postback no devuelve un PDF', function () {
    Http::fake(fn ($request) => $request->method() === 'GET'
        ? Http::response(fichaLicitacionConDescarga(), 200)
        : Http::response('<html>Sesión expirada</html>', 200, ['Content-Type' => 'text/html']));

    expect($this->servicio->obtener('LIC-NO-PDF'))->toBeNull();

    expect(SnapshotDatosExterno::where('metodo_captura', 'scraping_pdf')->count())->toBe(0);
    expect(Storage::disk('local')->allFiles())->toBe([]);
    expect(SolicitudApiExterna::latest('id')->first()->estado)->toBe('no_encontrada');
    expect(TrabajoIntegracion::latest('id')->first()->estado)->toBe('error');
});

test('registra el error y cierra el trabajo cuando falla la red', function () {
    Http::fake(fn () => throw new ConnectionException('Sin conexión'));

    expect($this->servicio->obtener('LIC-SIN-RED'))->toBeNull();

    expect(SnapshotDatosExterno::where('metodo_captura', 'scraping_pdf')->count())->toBe(0);
    expect(Storage::disk('local')->allFiles())->toBe([]);

    $solicitud = SolicitudApiExterna::latest('id')->first();
    expect($solicitud->estado)->toBe('error');
    expect($solicitud->error)->toContain('Sin conexión');

    $trabajo = TrabajoIntegracion::latest('id')->first();
    expect($trabajo->estado)->toBe('error');
    expect($trabajo->estado)->not->toBe('en_progreso');
});

test('la ruta entrega el PDF a un usuario con permiso de consulta', function () {
    fakeFichaLicitacionYPdf();

    $usuario = User::factory()->create();
    $usuario->givePermissionTo('adquisiciones.consultar_licitacion_mp');

    $response = $this->actingAs($usuario)->get(route('adquisiciones.licitaciones_mp.pdf', ['codigo' => 'LIC-RUTA']));

    $response->assertOk();
    $response->assertHeader('content-type', 'application/pdf');
    expect($response->streamedContent())->toBe(CONTENIDO_PDF_LICITACION);
});

test('la ruta bloquea a un usuario sin permiso de consulta', function () {
    $usuario = User::factory()->create();

    $this->actingAs($usuario)
        ->get(route('adquisiciones.licitaciones_mp.pdf', ['codigo' => 'LIC-SIN-PERMISO']))
        ->assertForbidden();
});

test('la ruta de PDF no es capturada por la ruta de detalle con parámetro', function () {
    $ruta = app('router')->getRoutes()->match(
        Request::create(route('adquisiciones.licitaciones_mp.pdf', ['codigo' => 'X']), 'GET'),
    );

    expect($ruta->getName())->toBe('adquisiciones.licitaciones_mp.pdf');
});
