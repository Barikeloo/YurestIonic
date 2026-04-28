<?php

declare(strict_types=1);

namespace App\Sale\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
        'amount_per_diner',
        'paid_diners_count',
        'status',
        'cancelled_by_user_id',
        'cancellation_reason',
        'cancelled_at',
    ];

    protected $casts = [
        'diners_count' => 'integer',
        'total_cents' => 'integer',
        'amount_per_diner' => 'integer',
        'paid_diners_count' => 'integer',
        'cancelled_at' => 'datetime',
    ];

    /**
     * @return HasMany<ChargeSessionPaymentModel>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(ChargeSessionPaymentModel::class, 'charge_session_id', 'id');
    }
}
