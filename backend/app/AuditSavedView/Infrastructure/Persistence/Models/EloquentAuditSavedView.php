<?php

declare(strict_types=1);

namespace App\AuditSavedView\Infrastructure\Persistence\Models;

use App\Restaurant\Infrastructure\Persistence\Models\EloquentRestaurant;
use App\Shared\Infrastructure\Persistence\Concerns\HasTenantScope;
use App\User\Infrastructure\Persistence\Models\EloquentUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class EloquentAuditSavedView extends Model
{
    use HasTenantScope;

    protected $table = 'audit_saved_views';

    protected $fillable = [
        'uuid',
        'restaurant_id',
        'user_id',
        'name',
        'icon',
        'filters',
    ];

    protected $casts = [
        'filters' => 'array',
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
