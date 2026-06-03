<?php

declare(strict_types=1);

namespace App\Audit\Infrastructure\Persistence\Models;

use App\Restaurant\Infrastructure\Persistence\Models\EloquentRestaurant;
use App\Shared\Infrastructure\Persistence\Concerns\HasTenantScope;
use App\User\Infrastructure\Persistence\Models\EloquentUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class EloquentAuditLog extends Model
{
    use HasTenantScope;

    protected $table = 'audit_logs';

    public $timestamps = false;

    protected $fillable = [
        'uuid',
        'restaurant_id',
        'entity_type',
        'entity_id',
        'action',
        'category',
        'severity',
        'summary',
        'reason',
        'session_id',
        'anomaly_kind',
        'integrity_hash',
        'prev_hash',
        'metadata',
        'user_id',
        'before',
        'after',
        'ip_address',
        'device_id',
        'created_at',
        'archived_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'before' => 'array',
        'after' => 'array',
        'created_at' => 'datetime',
        'archived_at' => 'datetime',
    ];

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(EloquentRestaurant::class, 'restaurant_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(EloquentUser::class, 'user_id');
    }
}
