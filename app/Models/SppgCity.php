<?php

namespace App\Models;

use App\Models\Concerns\Uploaded;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * Count of operational MBG kitchens (SPPG) in a kabupaten/kota.
 */
#[Fillable(['uploaded_by', 'city', 'province', 'region_id', 'outlets', 'as_of', 'source_url', 'level', 'rwi', 'population'])]
class SppgCity extends Model
{
    use Uploaded;

    protected $table = 'sppg_city';

    /** Outlets per 100,000 residents, the map's shading metric. */
    public function perCapita(): ?float
    {
        return $this->population > 0
            ? $this->outlets / $this->population * 100_000
            : null;
    }

    public function isProvinceLevel(): bool
    {
        return $this->level === 'province';
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'as_of' => 'date',
            'outlets' => 'integer',
            'population' => 'integer',
            'rwi' => 'float',
        ];
    }
}
