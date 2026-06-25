# Spec: integraciones-api-browser-automation

## Requirement: Usar API primero

Toda integración debe preferir API oficial suficiente.

### Scenario: Consultar API externa

Given existe una API oficial configurada
When el sistema consulta información externa
Then registra endpoint, payload enviado, payload recibido, estado, errores y usuario/job
And guarda snapshot si el dato se usa en gestión, cálculo o informe

## Requirement: Playwright solo autorizado

Playwright debe usarse solo cuando no exista API suficiente y exista autorización.

### Scenario: Ejecutar automatización autorizada

Given existe un conector Playwright activo
And el usuario tiene permiso
When se ejecuta una automatización
Then se registra run, pasos, resultado y artifacts
And no se evaden MFA, CAPTCHA ni controles de acceso
