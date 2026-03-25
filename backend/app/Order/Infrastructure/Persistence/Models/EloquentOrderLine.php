<?php

namespace App\Order\Infrastructure\Persistence\Models;

use App\Shared\Infrastructure\Persistence\Concerns\HasTenantScope;
use App\Product\Infrastructure\Persistence\Models\EloquentProduct;
use App\Sale\Infrastructure\Persistence\Models\EloquentSaleLine;
use App\User\Infrastructure\Persistence\Models\EloquentUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

final class EloquentOrderLine extends Model
{
    use HasTenantScope;
    use SoftDeletes;

    protected $table = 'order_lines';

    protected $fillable = [
        'restaurant_id',
        'uuid',
        'order_id',
        'product_id',
        'user_id',
        'quantity',
        'price',
        'tax_percentage',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(EloquentOrder::class, 'order_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(EloquentProduct::class, 'product_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(EloquentUser::class, 'user_id');
    }

    public function saleLine(): HasOne
    {
        return $this->hasOne(EloquentSaleLine::class, 'order_line_id');
    }
}
