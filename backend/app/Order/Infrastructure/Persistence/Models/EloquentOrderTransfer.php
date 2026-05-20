<?php

declare(strict_types=1);

namespace App\Order\Infrastructure\Persistence\Models;

use App\Tables\Infrastructure\Persistence\Models\EloquentTable;
use App\User\Infrastructure\Persistence\Models\EloquentUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class EloquentOrderTransfer extends Model
{
    protected $table = 'order_transfers';

    protected $fillable = [
        'uuid',
        'order_id',
        'from_table_id',
        'to_table_id',
        'transferred_by_user_id',
        'transferred_at',
    ];

    protected $casts = [
        'transferred_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(EloquentOrder::class, 'order_id');
    }

    public function fromTable(): BelongsTo
    {
        return $this->belongsTo(EloquentTable::class, 'from_table_id');
    }

    public function toTable(): BelongsTo
    {
        return $this->belongsTo(EloquentTable::class, 'to_table_id');
    }

    public function transferredByUser(): BelongsTo
    {
        return $this->belongsTo(EloquentUser::class, 'transferred_by_user_id');
    }
}
