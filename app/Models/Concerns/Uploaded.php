<?php

namespace App\Models\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A dataset row that was uploaded by a user, and remembers who.
 */
trait Uploaded
{
    /**
     * The user who uploaded the row; null for rows that predate attribution.
     *
     * @return BelongsTo<User, $this>
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
