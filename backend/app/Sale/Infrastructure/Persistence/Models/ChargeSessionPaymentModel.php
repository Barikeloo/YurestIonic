<?php

declare(strict_types=1);

namespace App\Sale\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChargeSessionPaymentModel extends Model
{
    protected $table = 'charge_session_payments';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'charge_session_id',
        'diner_number',
        'amount_cents',
        'payment_method',
        'status',
    ];

    protected $casts = [
        'diner_number' => 'integer',
        'amount_cents' => 'integer',
    ];

    /**
     * @return BelongsTo<ChargeSessionModel, $this>
     */
    public function chargeSession(): BelongsTo
    {
        return $this->belongsTo(ChargeSessionModel::class, 'charge_session_id', 'id');
    }
}
