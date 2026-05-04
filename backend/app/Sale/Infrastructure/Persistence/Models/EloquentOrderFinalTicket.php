<?php

declare(strict_types=1);

namespace App\Sale\Infrastructure\Persistence\Models;

use App\Order\Infrastructure\Persistence\Models\EloquentOrder;
use App\Restaurant\Infrastructure\Persistence\Models\EloquentRestaurant;
use App\Shared\Infrastructure\Persistence\Concerns\HasTenantScope;
use App\User\Infrastructure\Persistence\Models\EloquentUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class EloquentOrderFinalTicket extends Model
{
    use HasTenantScope;
    use SoftDeletes;

    protected $table = 'order_final_tickets';

    protected $fillable = [
        'uuid',
        'restaurant_id',
        'order_id',
        'closed_by_user_id',
        'ticket_number',
        'total_consumed_cents',
        'total_paid_cents',
        'payments_snapshot',
    ];

    protected $casts = [
        'payments_snapshot' => 'array',
    ];

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(EloquentRestaurant::class, 'restaurant_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(EloquentOrder::class, 'order_id');
    }

    public function closedByUser(): BelongsTo
    {
        return $this->belongsTo(EloquentUser::class, 'closed_by_user_id');
    }
}
