<?php

use App\Models\AuditLog;
use App\Models\User;
use App\Services\AuditLogger;

test('AuditLogger::log crea un registro con los datos esperados', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $auditable = User::factory()->create();

    app(AuditLogger::class)->log(
        action: 'usuario.actualizado',
        auditable: $auditable,
        before: ['name' => 'Antes'],
        after: ['name' => 'Despues'],
        metadata: ['origen' => 'test'],
    );

    expect(AuditLog::count())->toBe(1);

    $log = AuditLog::first();
    expect($log->user_id)->toBe($user->id);
    expect($log->action)->toBe('usuario.actualizado');
    expect($log->auditable_type)->toBe($auditable->getMorphClass());
    expect($log->auditable_id)->toBe($auditable->id);
    expect($log->before)->toBe(['name' => 'Antes']);
    expect($log->after)->toBe(['name' => 'Despues']);
    expect($log->metadata)->toBe(['origen' => 'test']);
});
