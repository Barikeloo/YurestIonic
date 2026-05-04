<?php

declare(strict_types=1);

namespace App\Cash\Infrastructure\Persistence\Models;

use App\Restaurant\Infrastructure\Persistence\Models\EloquentRestaurant;
use App\Sale\Infrastructure\Persistence\Models\EloquentSale;
use App\Shared\Infrastructure\Persistence\Concerns\HasTenantScope;
use App\User\Infrastructure\Persistence\Models\EloquentUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class EloquentSalePayment extends Model
{
    use HasTenantScope;
    use SoftDeletes;

    protected $table = 'sale_payments';

    protected $fillable = [
        'uuid',
        'restaurant_id',
        'sale_id',
        'cash_session_id',
        'charge_session_id',
        'diner_number',
        'method',
        'amount_cents',
        'snapshot_total_cents',
        'snapshot_paid_cents',
        'snapshot_remaining_cents',
        'metadata',
        'user_id',
    ];

    protected $casts = [
        'amount_cents' => 'integer',
        'diner_number' => 'integer',
        'snapshot_total_cents' => 'integer',
        'snapshot_paid_cents' => 'integer',
        'snapshot_remaining_cents' => 'integer',
        'metadata' => 'array',
    ];

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(EloquentRestaurant::class, 'restaurant_id');
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(EloquentSale::class, 'sale_id');
    }

    public function cashSession(): BelongsTo
    {
        return $this->belongsTo(EloquentCashSession::class, 'cash_session_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(EloquentUser::class, 'user_id');
    }
}
