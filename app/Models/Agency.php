<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * An institution that handles corruption cases: a police force, a prosecutor,
 * or an auditor. One body works many cases, and one case draws several bodies.
 */
#[Fillable(['name', 'kind'])]
class Agency extends Model
{
    protected $table = 'agencies';

    /**
     * @return BelongsToMany<Corruption, $this>
     */
    public function cases(): BelongsToMany
    {
        return $this->belongsToMany(Corruption::class)
            ->withPivot('position')
            ->withTimestamps();
    }

    /**
     * The bodies named in a source's own wording, created if they are new.
     * "BPKP / Kejaksaan Agung" is two institutions, not one.
     *
     * @return array<int, array{position: int}> keyed by agency id, ready for sync()
     */
    public static function fromRaw(?string $raw): array
    {
        if (blank($raw)) {
            return [];
        }

        return collect(preg_split('#\s*(?:/|,|\band\b|&)\s*#i', $raw))
            ->map(fn (string $name) => trim($name))
            ->filter()
            ->unique()
            ->values()
            ->mapWithKeys(fn (string $name, int $i) => [
                // sync() reads a bare value as an id, so the position must be a payload.
                static::firstOrCreate(['name' => $name], ['kind' => static::kindOf($name)])->id => ['position' => $i + 1],
            ])
            ->all();
    }

    /** What sort of body it is, read off the name Indonesian institutions use. */
    public static function kindOf(string $name): string
    {
        return match (true) {
            (bool) preg_match('/^(Pol(da|res|resta|sek|ri)|Bareskrim)\b/i', $name) => 'police',
            (bool) preg_match('/^(Kejaksaan|Kejari|Kejati|KPK)\b/i', $name) => 'prosecutor',
            (bool) preg_match('/^(BPK|BPKP|Inspektorat)\b/i', $name) => 'auditor',
            default => 'other',
        };
    }
}
