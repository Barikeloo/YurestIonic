<?php

declare(strict_types=1);

namespace App\Menu\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EloquentMenuSection extends Model
{
    protected $table = 'menu_sections';

    protected $fillable = [
        'menu_id',
        'uuid',
        'name',
        'position',
        'min_choices',
        'max_choices',
    ];

    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'min_choices' => 'integer',
            'max_choices' => 'integer',
        ];
    }

    public function menu(): BelongsTo
    {
        return $this->belongsTo(EloquentMenu::class, 'menu_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(EloquentMenuItem::class, 'section_id')->orderBy('position');
    }
}
