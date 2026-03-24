<?php

namespace App\Order\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

final class EloquentOrderLine extends Model
{
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
}
