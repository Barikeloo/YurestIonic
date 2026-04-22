<?php

declare(strict_types=1);

namespace App\Cash\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class EloquentTip extends Model
{
    protected $table = 'tips';

    protected $fillable = [
        'uuid',
        'restaurant_id',
        'sale_id',
        'cash_session_id',
        'amount_cents',
        'source',
        'beneficiary_user_id',
    ];

    protected $casts = [
        'amount_cents' => 'integer',
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

    public function beneficiaryUser(): BelongsTo
    {
        return $this->belongsTo(\App\User\Infrastructure\Persistence\Models\EloquentUser::class, 'beneficiary_user_id');
    }
}
