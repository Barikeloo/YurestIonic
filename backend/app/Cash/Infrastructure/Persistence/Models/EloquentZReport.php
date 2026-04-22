<?php

declare(strict_types=1);

namespace App\Cash\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class EloquentZReport extends Model
{
    use SoftDeletes;

    protected $table = 'z_reports';

    protected $fillable = [
        'uuid',
        'restaurant_id',
        'cash_session_id',
        'report_number',
        'report_hash',
        'total_sales_cents',
        'total_cash_cents',
        'total_card_cents',
        'total_other_cents',
        'cash_in_cents',
        'cash_out_cents',
        'tips_cents',
        'discrepancy_cents',
        'sales_count',
        'cancelled_sales_count',
        'generated_at',
    ];

    protected $casts = [
        'generated_at' => 'datetime',
    ];

    public function cashSession(): BelongsTo
    {
        return $this->belongsTo(EloquentCashSession::class, 'cash_session_id');
    }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(\App\Restaurant\Infrastructure\Persistence\Models\EloquentRestaurant::class, 'restaurant_id');
    }
}
