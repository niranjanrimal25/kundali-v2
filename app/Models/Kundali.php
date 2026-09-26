<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Kundali extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'name', 'gender', 'birth_date', 'birth_time',
        'birth_place', 'latitude', 'longitude', 'timezone',
        'utc_offset_hours', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'latitude' => 'float',
            'longitude' => 'float',
            'utc_offset_hours' => 'float',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function chartData(): HasOne
    {
        return $this->hasOne(ChartData::class);
    }

    public function readings(): HasMany
    {
        return $this->hasMany(Reading::class);
    }

    public function reading(string $locale = 'en'): ?Reading
    {
        return $this->readings()->where('locale', $locale)->first();
    }

    /** Birth time formatted as HH:MM regardless of storage precision. */
    public function getBirthTimeShortAttribute(): string
    {
        return substr((string) $this->birth_time, 0, 5);
    }

    /**
     * Any change to these fields invalidates the cached chart and reading.
     */
    public function invalidateDerivedData(): void
    {
        $this->chartData()->delete();
        $this->readings()->delete();
    }
}
