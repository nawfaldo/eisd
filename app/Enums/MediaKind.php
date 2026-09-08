<?php

namespace App\Enums;

/**
 * What a post carries as an attachment. The kind decides how it is rendered,
 * and is taken from the uploaded file rather than trusted from the request.
 */
enum MediaKind: string
{
    case Image = 'image';
    case Video = 'video';

    /** Extensions accepted for each kind, in the order the validator states them. */
    public const IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    public const VIDEO_EXTENSIONS = ['mp4', 'webm', 'ogg'];

    /** The kind a mime type belongs to, or null if it is neither. */
    public static function fromMime(string $mime): ?self
    {
        return match (true) {
            str_starts_with($mime, 'image/') => self::Image,
            str_starts_with($mime, 'video/') => self::Video,
            default => null,
        };
    }

    /**
     * @return array<int, string>
     */
    public static function extensions(): array
    {
        return [...self::IMAGE_EXTENSIONS, ...self::VIDEO_EXTENSIONS];
    }
}
