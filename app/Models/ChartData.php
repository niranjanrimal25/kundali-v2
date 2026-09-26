<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChartData extends Model
{
    protected $table = 'chart_data';

    protected $fillable = [
        'kundali_id', 'facts', 'julian_day', 'ayanamsa',
        'lagna_sign', 'moon_sign', 'moon_nakshatra', 'engine_version',
    ];

    protected function casts(): array
    {
        return [
            'facts' => 'array',
            'julian_day' => 'float',
            'ayanamsa' => 'float',
        ];
    }

    public function kundali(): BelongsTo
    {
        return $this->belongsTo(Kundali::class);
    }
}
