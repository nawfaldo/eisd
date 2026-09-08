<?php

namespace App\Models;

use App\Enums\MediaKind;
use App\Models\Concerns\Posted;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A thread on the board: the opening post, plus everything replied under it.
 */
#[Fillable(['user_id', 'subject', 'body', 'media_path', 'media_kind', 'bumped_at'])]
class ForumThread extends Model
{
    use Posted;

    /**
     * @return HasMany<ForumPost, $this>
     */
    public function posts(): HasMany
    {
        return $this->hasMany(ForumPost::class, 'thread_id');
    }

    /** What the thread is called in a listing: its subject, else its first words. */
    public function title(): string
    {
        return $this->subject ?: str($this->body)->squish()->limit(80)->value();
    }

    /** What the opening post is quoted as; it is the thread, so it has no number. */
    public const ANCHOR = 'op';

    /** Move the thread to the top of the board, as a new reply does. */
    public function bump(): void
    {
        $this->forceFill(['bumped_at' => now()])->save();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'media_kind' => MediaKind::class,
            'bumped_at' => 'datetime',
        ];
    }
}
