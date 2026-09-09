# Spec: analisis-estatico-tipos

## Purpose

Establece el análisis estático de tipos (PHPStan/Larastan vía `composer types:check`) como un estándar de calidad verificable del proyecto, y fija la convención para describir con precisión — nunca suprimir — casos donde una clase de terceros expone en runtime una propiedad real que su propio código no declara.

## Requirements

### Requirement: El análisis estático de tipos corre sin errores
El código bajo `app/`, `bootstrap/app.php`, `config/`, `database/`, `routes/` SHALL pasar `composer types:check` (PHPStan/Larastan al nivel configurado en `phpstan.neon`) sin errores. Cuando una clase de terceros expone en runtime una propiedad real (columna de base de datos) que su propio código no declara, el proyecto SHALL describirla mediante un stub file de PHPStan registrado en `phpstan.neon` en vez de suprimir el error o forzar el tipo con anotaciones que no reflejen la realidad.

#### Scenario: Ejecución limpia
- **WHEN** se ejecuta `composer types:check` sobre el estado actual del código
- **THEN** el comando termina sin errores

#### Scenario: Propiedad real de una clase de terceros no declarada en su PHPDoc
- **WHEN** una migración agrega una columna a la tabla de un modelo provisto por un paquete de terceros (por ejemplo `roles.etiqueta`/`roles.descripcion` sobre `Spatie\Permission\Models\Role`), y el código de la aplicación lee o escribe esa columna a través del modelo
- **THEN** el proyecto declara esa propiedad mediante un stub file de PHPStan (`parameters.stubFiles`) que apunta a la migración de origen como comentario, en vez de un `@phpstan-ignore` o una anotación `@var` que oculte el error sin describir el tipo real
