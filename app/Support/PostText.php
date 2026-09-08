<?php

namespace App\Support;

use App\Models\ForumThread;
use Illuminate\Support\HtmlString;

/**
 * Board markup: the three things a post can say in its text.
 *
 * A line opening with ">" is a quotation of something said elsewhere; ">>12" points
 * at post 12 in the same thread; a bare url is a link. Everything is escaped before
 * any of it is applied, so nothing a user types can become markup of its own.
 */
class PostText
{
    /**
     * @param  array<int, int>  $posts  Post numbers in this thread, so ">>12" only
     *                                  links when there is a 12 to link to.
     */
    public static function render(string $body, array $posts = []): HtmlString
    {
        $known = array_flip($posts);

        $lines = array_map(
            fn (string $line) => self::line($line, $known),
            preg_split('/\R/', trim($body)) ?: [],
        );

        return new HtmlString(implode('', $lines));
    }

    /**
     * The post numbers a body quotes, whether or not they exist.
     *
     * @return array<int, int>
     */
    public static function quoted(string $body): array
    {
        preg_match_all('/>>(\d+)/', $body, $matches);

        return array_map(intval(...), array_unique($matches[1]));
    }

    /**
     * @param  array<int, int>  $known
     */
    private static function line(string $line, array $known): string
    {
        $html = self::links(self::quotes(e($line), $known));

        // An empty line is still a line: it is the paragraph break the poster typed.
        if (trim($line) === '') {
            return '<span class="block h-3"></span>';
        }

        // A quoted line is greentext on a colour board; here it is set apart by weight.
        return str_starts_with(ltrim($line), '>') && ! str_starts_with(ltrim($line), '>>')
            ? '<span class="block whitespace-pre-wrap text-neutral-500 italic">'.$html.'</span>'
            : '<span class="block whitespace-pre-wrap">'.$html.'</span>';
    }

    /**
     * @param  array<int, int>  $known
     */
    private static function quotes(string $escaped, array $known): string
    {
        // ">>OP" answers the post that opened the thread, which has no number.
        $escaped = str_replace(
            '&gt;&gt;OP',
            '<a href="#'.ForumThread::ANCHOR.'" class="underline underline-offset-2 hover:no-underline">&gt;&gt;OP</a>',
            $escaped,
        );

        return preg_replace_callback(
            '/&gt;&gt;(\d+)/',
            function (array $match) use ($known): string {
                $number = (int) $match[1];

                if (isset($known[$number])) {
                    return '<a href="#p'.$number.'" class="underline underline-offset-2 hover:no-underline">&gt;&gt;'.$number.'</a>';
                }

                // Listings render a post away from its thread, so nothing is known to
                // be missing there; only a thread can say a post is gone.
                return $known === []
                    ? '<span class="text-neutral-500">&gt;&gt;'.$number.'</span>'
                    : '<span class="text-neutral-400 line-through">&gt;&gt;'.$number.'</span>';
            },
            $escaped,
        ) ?? $escaped;
    }

    private static function links(string $escaped): string
    {
        return preg_replace(
            '~(?<!")(https?://[^\s<]+)~',
            '<a href="$1" target="_blank" rel="noopener noreferrer nofollow" class="underline underline-offset-2 hover:no-underline">$1</a>',
            $escaped,
        ) ?? $escaped;
    }
}
