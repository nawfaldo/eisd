<?php

namespace App\Models;

use App\Models\Concerns\Uploaded;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * A single reported mass food-poisoning case in the Makan Bergizi Gratis programme.
 */
#[Fillable([
    'uploaded_by', 'province', 'region_id', 'regency', 'place',
    'occurred_on', 'occurred_raw', 'victims', 'deaths', 'source_url',
])]
class Poisoned extends Model
{
    use Uploaded;

    protected $table = 'poisoned';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'occurred_on' => 'date',
            'victims' => 'integer',
            'deaths' => 'integer',
        ];
    }
}
