<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    protected $fillable = [
        'geoname_id', 'name', 'ascii_name', 'country_code',
        'admin1', 'latitude', 'longitude', 'timezone', 'population',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'population' => 'integer',
        ];
    }

    /**
     * Prefix search ordered so that the most populous match wins, with
     * Nepal boosted to the top since that is the primary use case.
     */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        $term = trim($term);

        return $query
            ->where(function (Builder $q) use ($term) {
                $q->where('ascii_name', 'like', $term.'%')
                    ->orWhere('name', 'like', $term.'%');
            })
            ->orderByRaw("CASE WHEN country_code = 'NP' THEN 0 ELSE 1 END")
            ->orderByRaw('CASE WHEN ascii_name = ? THEN 0 ELSE 1 END', [$term])
            ->orderByDesc('population');
    }

    public function getLabelAttribute(): string
    {
        $parts = array_filter([$this->name, $this->country_code]);

        return implode(', ', $parts);
    }
}
