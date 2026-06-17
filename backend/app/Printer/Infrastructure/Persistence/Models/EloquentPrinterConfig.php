<?php

declare(strict_types=1);

namespace App\Printer\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

final class EloquentPrinterConfig extends Model
{
    use SoftDeletes;

    protected $table = 'printer_configs';

    protected $fillable = [
        'uuid',
        'restaurant_id',
        'name',
        'ip',
        'port',
        'paper_width',
        'enabled',
        'is_default',
    ];

    protected $casts = [
        'port'       => 'integer',
        'paper_width' => 'integer',
        'enabled'    => 'boolean',
        'is_default' => 'boolean',
    ];
}
