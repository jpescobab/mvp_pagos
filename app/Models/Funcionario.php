<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Funcionario extends Model
{
    use SoftDeletes;

    protected $table = 'funcionarios';

    protected $fillable = ['rut', 'nombre', 'user_id', 'ccosto_id', 'cfinanciero_id', 'activo'];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Ccosto, $this>
     */
    public function ccosto(): BelongsTo
    {
        return $this->belongsTo(Ccosto::class);
    }

    /**
     * @return BelongsTo<Cfinanciero, $this>
     */
    public function cfinanciero(): BelongsTo
    {
        return $this->belongsTo(Cfinanciero::class);
    }
}
