<?php

namespace App\Sale\Infrastructure\Persistence\Models;

use App\Order\Infrastructure\Persistence\Models\EloquentOrder;
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
        'cancelled_by_user_id',
        'cancel_reason',
        'status',
    ];

    protected $casts = [
        'value_date' => 'datetime',
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

    public function lines(): HasMany
    {
        return $this->hasMany(EloquentSaleLine::class, 'sale_id');
    }
}
