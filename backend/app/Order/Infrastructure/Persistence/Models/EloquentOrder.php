<?php

namespace App\Order\Infrastructure\Persistence\Models;

use App\Shared\Infrastructure\Persistence\Concerns\HasTenantScope;
use App\Sale\Infrastructure\Persistence\Models\EloquentSale;
use App\Tables\Infrastructure\Persistence\Models\EloquentTable;
use App\User\Infrastructure\Persistence\Models\EloquentUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

final class EloquentOrder extends Model
{
    use HasTenantScope;
    use SoftDeletes;

    protected $table = 'orders';

    protected $fillable = [
        'restaurant_id',
        'uuid',
        'status',
        'table_id',
        'opened_by_user_id',
        'closed_by_user_id',
        'diners',
        'opened_at',
        'closed_at',
    ];

    protected $casts = [
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

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

    public function lines(): HasMany
    {
        return $this->hasMany(EloquentOrderLine::class, 'order_id');
    }

    public function sale(): HasOne
    {
        return $this->hasOne(EloquentSale::class, 'order_id');
    }
}
