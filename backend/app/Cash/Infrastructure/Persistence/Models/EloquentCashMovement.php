<?php

declare(strict_types=1);

namespace App\Cash\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class EloquentCashMovement extends Model
{
    protected $table = 'cash_movements';

    protected $fillable = [
        'uuid',
        'restaurant_id',
        'cash_session_id',
        'type',
        'reason_code',
        'amount_cents',
        'description',
        'user_id',
    ];

    protected $casts = [
        'amount_cents' => 'integer',
    ];

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(\App\Restaurant\Infrastructure\Persistence\Models\EloquentRestaurant::class, 'restaurant_id');
    }

    public function cashSession(): BelongsTo
    {
        return $this->belongsTo(EloquentCashSession::class, 'cash_session_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\User\Infrastructure\Persistence\Models\EloquentUser::class, 'user_id');
    }
}
