<?php

namespace App\Models\Presupuesto;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class MovimientoPresupuestario extends Model
{
    protected $table = 'movimientos_presupuestarios';

    protected $fillable = [
        'presupuesto_id',
        'tipo',
        'monto',
        'origen_type',
        'origen_id',
        'user_id',
        'observacion',
    ];

    protected function casts(): array
    {
        return [
            'monto' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<Presupuesto, $this>
     */
    public function presupuesto(): BelongsTo
    {
        return $this->belongsTo(Presupuesto::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function origen(): MorphTo
    {
        return $this->morphTo();
    }
}
