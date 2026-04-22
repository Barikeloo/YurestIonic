<?php

declare(strict_types=1);

namespace App\Cash\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class EloquentCashSession extends Model
{
    protected $table = 'cash_sessions';

    protected $fillable = [
        'uuid',
        'restaurant_id',
        'device_id',
        'opened_by_user_id',
        'closed_by_user_id',
        'opened_at',
        'closed_at',
        'initial_amount_cents',
        'final_amount_cents',
        'expected_amount_cents',
        'discrepancy_cents',
        'discrepancy_reason',
        'z_report_number',
        'z_report_hash',
        'notes',
        'status',
    ];

    protected $casts = [
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
        'initial_amount_cents' => 'integer',
        'final_amount_cents' => 'integer',
        'expected_amount_cents' => 'integer',
        'discrepancy_cents' => 'integer',
        'z_report_number' => 'integer',
    ];

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(\App\Restaurant\Infrastructure\Persistence\Models\EloquentRestaurant::class, 'restaurant_id');
    }

    public function openedByUser(): BelongsTo
    {
        return $this->belongsTo(\App\User\Infrastructure\Persistence\Models\EloquentUser::class, 'opened_by_user_id');
    }

    public function closedByUser(): BelongsTo
    {
        return $this->belongsTo(\App\User\Infrastructure\Persistence\Models\EloquentUser::class, 'closed_by_user_id');
    }
}
