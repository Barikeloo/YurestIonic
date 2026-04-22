<?php

declare(strict_types=1);

namespace App\Cash\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class EloquentSalePayment extends Model
{
    protected $table = 'sale_payments';

    protected $fillable = [
        'uuid',
        'restaurant_id',
        'sale_id',
        'cash_session_id',
        'method',
        'amount_cents',
        'metadata',
        'user_id',
    ];

    protected $casts = [
        'amount_cents' => 'integer',
        'metadata' => 'array',
    ];

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(\App\Restaurant\Infrastructure\Persistence\Models\EloquentRestaurant::class, 'restaurant_id');
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(\App\Sale\Infrastructure\Persistence\Models\EloquentSale::class, 'sale_id');
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
