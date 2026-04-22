<?php

declare(strict_types=1);

namespace App\Cash\Infrastructure\Persistence\Models;

use App\Restaurant\Infrastructure\Persistence\Models\EloquentRestaurant;
use App\Sale\Infrastructure\Persistence\Models\EloquentSale;
use App\Shared\Infrastructure\Persistence\Concerns\HasTenantScope;
use App\User\Infrastructure\Persistence\Models\EloquentUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class EloquentTip extends Model
{
    use HasTenantScope;
    use SoftDeletes;
    protected $table = 'tips';

    protected $fillable = [
        'uuid',
        'restaurant_id',
        'sale_id',
        'cash_session_id',
        'amount_cents',
        'source',
        'beneficiary_user_id',
    ];

    protected $casts = [
        'amount_cents' => 'integer',
    ];

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(EloquentRestaurant::class, 'restaurant_id');
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(EloquentSale::class, 'sale_id');
    }

    public function cashSession(): BelongsTo
    {
        return $this->belongsTo(EloquentCashSession::class, 'cash_session_id');
    }

    public function beneficiaryUser(): BelongsTo
    {
        return $this->belongsTo(EloquentUser::class, 'beneficiary_user_id');
    }
}
