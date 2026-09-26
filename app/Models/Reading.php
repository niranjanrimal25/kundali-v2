<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reading extends Model
{
    protected $fillable = ['kundali_id', 'locale', 'sections'];

    protected function casts(): array
    {
        return ['sections' => 'array'];
    }

    public function kundali(): BelongsTo
    {
        return $this->belongsTo(Kundali::class);
    }
}
