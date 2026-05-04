<?php

declare(strict_types=1);

namespace App\Sale\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChargeSessionModel extends Model
{
    use SoftDeletes;

    protected $table = 'charge_sessions';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'restaurant_id',
        'order_id',
        'opened_by_user_id',
        'diners_count',
        'total_cents',
        'status',
        'cancelled_by_user_id',
        'cancellation_reason',
        'cancelled_at',
    ];

    protected $casts = [
        'diners_count' => 'integer',
        'total_cents' => 'integer',
        'cancelled_at' => 'datetime',
    ];
}
