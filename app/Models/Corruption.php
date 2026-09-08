<?php

namespace App\Models;

use App\Models\Concerns\Uploaded;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * A reported corruption case in the MBG programme.
 */
#[Fillable([
    'uploaded_by', 'title', 'province', 'region_id', 'regency', 'amount',
    'agency', 'status', 'reported_on', 'reported_raw', 'source_url',
])]
class Corruption extends Model
{
    use Uploaded;

    protected $table = 'corruption';

    /**
     * The institutions handling this case. The `agency` column keeps the source's
     * own wording; this is that wording resolved into the bodies it names.
     *
     * @return BelongsToMany<Agency, $this>
     */
    public function agencies(): BelongsToMany
    {
        return $this->belongsToMany(Agency::class)
            ->withPivot('position')
            ->withTimestamps()
            ->orderByPivot('position');
    }

    /** Cases with no published state loss cannot be summed, only counted. */
    public function hasAmount(): bool
    {
        return $this->amount !== null;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'reported_on' => 'date',
            'amount' => 'integer',
        ];
    }
}
