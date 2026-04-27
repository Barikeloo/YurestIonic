<?php

declare(strict_types=1);

namespace App\Sale\Infrastructure\Persistence\Models;

use App\Cash\Infrastructure\Persistence\Models\EloquentCashSession;
use App\Order\Infrastructure\Persistence\Models\EloquentOrder;
use App\Restaurant\Infrastructure\Persistence\Models\EloquentRestaurant;
use App\Shared\Infrastructure\Persistence\Concerns\HasTenantScope;
use App\Tables\Infrastructure\Persistence\Models\EloquentTable;
use App\User\Infrastructure\Persistence\Models\EloquentUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class EloquentSale extends Model
{
    use HasTenantScope;
    use SoftDeletes;

    protected $table = 'sales';

    protected $fillable = [
        'restaurant_id',
        'table_id',
        'opened_by_user_id',
        'closed_by_user_id',
        'uuid',
        'order_id',
        'user_id',
        'ticket_number',
        'value_date',
        'total',
        'cash_session_id',
        'status',
        'cancelled_at',
        'cancelled_by_user_id',
        'cancel_reason',
        'parent_sale_id',
        'document_type',
        'customer_fiscal_data',
    ];

    protected $casts = [
        'value_date' => 'datetime',
        'cancelled_at' => 'datetime',
        'customer_fiscal_data' => 'array',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(EloquentOrder::class, 'order_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(EloquentUser::class, 'user_id');
    }

    public function table(): BelongsTo
    {
        return $this->belongsTo(EloquentTable::class, 'table_id');
    }

    public function openedByUser(): BelongsTo
    {
        return $this->belongsTo(EloquentUser::class, 'opened_by_user_id');
    }

    public function closedByUser(): BelongsTo
    {
        return $this->belongsTo(EloquentUser::class, 'closed_by_user_id');
    }

    public function cancelledByUser(): BelongsTo
    {
        return $this->belongsTo(EloquentUser::class, 'cancelled_by_user_id');
    }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(EloquentRestaurant::class, 'restaurant_id');
    }

    public function cashSession(): BelongsTo
    {
        return $this->belongsTo(EloquentCashSession::class, 'cash_session_id');
    }

    public function parentSale(): BelongsTo
    {
        return $this->belongsTo(EloquentSale::class, 'parent_sale_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(EloquentSaleLine::class, 'sale_id');
    }
}
