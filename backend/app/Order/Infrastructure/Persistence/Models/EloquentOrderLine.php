<?php

namespace App\Order\Infrastructure\Persistence\Models;

use App\Product\Infrastructure\Persistence\Models\EloquentProduct;
use App\ProductVariant\Infrastructure\Persistence\Models\EloquentProductVariant;
use App\Sale\Infrastructure\Persistence\Models\EloquentSaleLine;
use App\Shared\Infrastructure\Persistence\Concerns\HasTenantScope;
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
        'variant_id',
        'variant_name',
        'modifiers',
        'menu_id',
        'menu_name',
        'menu_selections',
        'user_id',
        'quantity',
        'price',
        'tax_percentage',
        'diner_number',
        'discount_percent',
        'discount_amount_cents',
        'discount_reason',
        'is_invitation',
        'price_override_cents',
        'notes',
        'origin',
        'send_status',
        'guest_session_id',
        'guest_name',
        'guest_round_id',
    ];

    protected function casts(): array
    {
        return [
            'modifiers' => 'array',
            'menu_selections' => 'array',
            'quantity' => 'integer',
            'price' => 'integer',
            'tax_percentage' => 'integer',
            'is_invitation' => 'boolean',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(EloquentOrder::class, 'order_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(EloquentProduct::class, 'product_id');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(EloquentProductVariant::class, 'variant_id', 'uuid');
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
