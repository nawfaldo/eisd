<?php

namespace App\Models;

use App\Enums\MediaKind;
use App\Models\Concerns\Posted;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A reply in a thread. It answers the thread itself, or another reply in it.
 */
#[Fillable(['thread_id', 'user_id', 'parent_id', 'number', 'body', 'media_path', 'media_kind'])]
class ForumPost extends Model
{
    use Posted;

    /** The number a reply takes next in its thread; the first reply is No.1. */
    public static function nextNumber(ForumThread $thread): int
    {
        return (int) $thread->posts()->max('number') + 1;
    }

    /**
     * @return BelongsTo<ForumThread, $this>
     */
    public function thread(): BelongsTo
    {
        return $this->belongsTo(ForumThread::class, 'thread_id');
    }

    /**
     * The reply this one answers; null when it answers the opening post.
     *
     * @return BelongsTo<ForumPost, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(ForumPost::class, 'parent_id');
    }

    /**
     * @return HasMany<ForumPost, $this>
     */
    public function replies(): HasMany
    {
        return $this->hasMany(ForumPost::class, 'parent_id');
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
            'number' => 'integer',
        ];
    }
}
