<?php

namespace App\Models\Concerns;

use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * Audita en `audit_logs` la creación, edición y eliminación del modelo que lo
 * usa, enganchando los eventos de Eloquent en un solo lugar en vez de repetir
 * la llamada a AuditLogger en cada controlador.
 *
 * Solo audita cuando hay un usuario autenticado: las escrituras de seeders,
 * migraciones y jobs de importación (sin sesión) no representan una acción
 * deliberada de nadie y no deben inundar la auditoría.
 *
 * @mixin Model
 */
trait RegistraAuditoria
{
    protected static function bootRegistraAuditoria(): void
    {
        static::created(static function (Model $modelo): void {
            self::registrarAuditoria('crear', $modelo, after: $modelo->getAttributes());
        });

        static::updated(static function (Model $modelo): void {
            $cambios = $modelo->getChanges();

            if ($cambios === []) {
                return;
            }

            $anterior = [];

            foreach (array_keys($cambios) as $campo) {
                $anterior[$campo] = $modelo->getOriginal($campo);
            }

            self::registrarAuditoria('editar', $modelo, before: $anterior, after: $cambios);
        });

        static::deleted(static function (Model $modelo): void {
            self::registrarAuditoria('eliminar', $modelo, before: $modelo->getOriginal());
        });
    }

    /**
     * Registra una mutación del modelo en `audit_logs`, con la convención
     * `<verbo>_<entidad>` (p. ej. `crear_cfinanciero`). No hace nada si no hay
     * usuario autenticado.
     *
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     */
    protected static function registrarAuditoria(string $verbo, Model $modelo, array $before = [], array $after = []): void
    {
        if (! Auth::check()) {
            return;
        }

        $entidad = Str::snake(class_basename($modelo));

        app(AuditLogger::class)->log($verbo.'_'.$entidad, $modelo, $before, $after);
    }
}
