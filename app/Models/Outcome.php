<?php

namespace App\Models;

use App\Models\Concerns\Uploaded;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * A published national nutrition figure, used to ask whether MBG changed anything.
 */
#[Fillable(['uploaded_by', 'indicator', 'label', 'period', 'value', 'unit', 'source', 'source_url'])]
class Outcome extends Model
{
    use Uploaded;

    /** MBG began serving in January 2025; nothing measured before then can be its result. */
    public const STARTED = '2025';

    /** Whether the figure covers a period the programme could have influenced. */
    public function isAfterMbg(): bool
    {
        return $this->period >= self::STARTED;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['value' => 'float'];
    }
}
