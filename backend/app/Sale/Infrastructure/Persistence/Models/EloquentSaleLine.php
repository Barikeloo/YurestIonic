<?php

namespace App\Sale\Infrastructure\Persistence\Models;

use App\Order\Infrastructure\Persistence\Models\EloquentOrderLine;
use App\Product\Infrastructure\Persistence\Models\EloquentProduct;
use App\Shared\Infrastructure\Persistence\Concerns\HasTenantScope;
use App\User\Infrastructure\Persistence\Models\EloquentUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class EloquentSaleLine extends Model
{
    use HasTenantScope;
    use SoftDeletes;

    protected $table = 'sales_lines';

    protected $fillable = [
        'restaurant_id',
        'uuid',
        'sale_id',
        'order_line_id',
        'product_id',
        'user_id',
        'quantity',
        'price',
        'tax_percentage',
    ];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(EloquentSale::class, 'sale_id');
    }

    public function orderLine(): BelongsTo
    {
        return $this->belongsTo(EloquentOrderLine::class, 'order_line_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(EloquentUser::class, 'user_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(EloquentProduct::class, 'product_id');
    }
}
