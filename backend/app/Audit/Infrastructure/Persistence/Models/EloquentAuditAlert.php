<?php

declare(strict_types=1);

namespace App\Audit\Infrastructure\Persistence\Models;

use App\Shared\Infrastructure\Persistence\Concerns\HasTenantScope;
use Illuminate\Database\Eloquent\Model;

final class EloquentAuditAlert extends Model
{
    use HasTenantScope;

    protected $table = 'audit_alerts';

    public $timestamps = false;

    protected $casts = [
        'metadata' => 'array',
        'read_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    protected $fillable = [
        'uuid',
        'restaurant_id',
        'action',
        'anomaly_kind',
        'entity_type',
        'entity_id',
        'summary',
        'metadata',
        'user_id',
        'device_id',
        'read_at',
        'created_at',
    ];
}
