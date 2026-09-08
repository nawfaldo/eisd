<?php

namespace App\Models\Concerns;

use App\Enums\MediaKind;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * Behaviour shared by the two things a user writes on the board: the post that
 * opens a thread, and every reply under it.
 */
trait Posted
{
    /**
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function hasMedia(): bool
    {
        return $this->media_path !== null;
    }

    public function isImage(): bool
    {
        return $this->media_kind === MediaKind::Image;
    }

    public function isVideo(): bool
    {
        return $this->media_kind === MediaKind::Video;
    }

    /**
     * Where the attachment is served from, or null when there is none.
     *
     * Root-relative on purpose: the page is served on whatever host the server is
     * bound to, and an absolute APP_URL would send the browser somewhere else — a
     * page opened on 127.0.0.1 would ask localhost for its images.
     */
    public function mediaUrl(): ?string
    {
        if (! $this->media_path) {
            return null;
        }

        $url = Storage::disk('public')->url($this->media_path);

        return parse_url($url, PHP_URL_PATH) ?: $url;
    }

    /** The attachment's own name, as it is labelled above the file. */
    public function mediaName(): ?string
    {
        return $this->media_path ? basename($this->media_path) : null;
    }
}
