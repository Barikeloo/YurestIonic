<?php

declare(strict_types=1);

namespace App\Audit\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $restaurant_id
 * @property bool $is_valid
 * @property int $total_events
 * @property int $verified_count
 * @property string $broken_events
 * @property int|null $first_broken_index
 * @property string $verified_at
 * @property string $created_at
 * @property string $updated_at
 */
final class EloquentVerifyChainResult extends Model
{
    protected $table = 'audit_chain_verifications';

    protected $fillable = [
        'restaurant_id',
        'is_valid',
        'total_events',
        'verified_count',
        'broken_events',
        'first_broken_index',
        'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'is_valid' => 'boolean',
            'total_events' => 'integer',
            'verified_count' => 'integer',
            'broken_events' => 'array',
            'first_broken_index' => 'integer',
            'verified_at' => 'datetime',
        ];
    }
}
