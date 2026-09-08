<?php

namespace App\Http\Controllers;

use App\Enums\MediaKind;
use App\Models\ForumPost;
use App\Models\ForumThread;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ForumController extends Controller
{
    /** Threads shown per page. */
    private const PER_PAGE = 20;

    /** How large an upload may be, in kilobytes, before PHP's own limits are applied. */
    private const MAX_UPLOAD = 20480;

    /**
     * The board: every thread, the one bumped last at the top, each shown as its
     * opening post and how many replies it has drawn.
     */
    public function index(Request $request): View
    {
        return view('forum.index', [
            'threads' => ForumThread::query()
                ->with('author')
                ->withCount('posts')
                ->orderByDesc('bumped_at')
                ->paginate(self::PER_PAGE)
                ->withQueryString(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'subject' => ['nullable', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:10000'],
            'media' => ['nullable', 'file', 'max:'.$this->maxUpload(), 'mimes:'.implode(',', MediaKind::extensions())],
        ], attributes: ['media' => 'file']);

        $thread = ForumThread::create([
            'user_id' => $request->user()->id,
            'subject' => $validated['subject'] ?? null,
            'body' => $validated['body'],
            ...$this->media($request->file('media')),
            'bumped_at' => now(),
        ]);

        return redirect()->route('forum.thread', $thread)->with('status', 'Thread posted.');
    }

    public function show(Request $request, ForumThread $thread): View
    {
        $thread->load('author');

        $posts = $thread->posts()->with(['author', 'parent'])->orderBy('number')->get();

        return view('forum.show', [
            'thread' => $thread,
            'posts' => $posts,
            'numbers' => $posts->pluck('number')->all(),
            'replyTo' => $this->replyTo($request, $posts),
        ]);
    }

    /**
     * What the [Reply] link that was clicked is answering: the opening post, or one
     * of the replies by its number. Null when nothing was clicked.
     *
     * @param  Collection<int, ForumPost>  $posts
     * @return array{label: string, anchor: string, quote: string, parent_id: ?int}|null
     */
    private function replyTo(Request $request, Collection $posts): ?array
    {
        $reply = (string) $request->query('reply');

        if ($reply === ForumThread::ANCHOR) {
            return [
                'label' => 'OP',
                'anchor' => ForumThread::ANCHOR,
                'quote' => '>>OP',
                // The opening post is the thread itself, so answering it has no parent row.
                'parent_id' => null,
            ];
        }

        $post = $posts->firstWhere('number', (int) $reply);

        return $post ? [
            'label' => 'No.'.$post->number,
            'anchor' => 'p'.$post->number,
            'quote' => '>>'.$post->number,
            'parent_id' => $post->id,
        ] : null;
    }

    public function reply(Request $request, ForumThread $thread): RedirectResponse
    {
        $validated = $request->validate([
            'body' => ['required', 'string', 'max:10000'],
            'media' => ['nullable', 'file', 'max:'.$this->maxUpload(), 'mimes:'.implode(',', MediaKind::extensions())],
            // A reply answers a post in this thread, or nothing at all.
            'parent_id' => ['nullable', 'integer', 'exists:forum_posts,id'],
        ], attributes: ['media' => 'file', 'parent_id' => 'post']);

        $parent = $validated['parent_id'] ?? null;

        // Numbering and the bump go together: two replies posted at once must not
        // land on the same number.
        $post = DB::transaction(fn (): ForumPost => ForumPost::create([
            'thread_id' => $thread->id,
            'user_id' => $request->user()->id,
            'parent_id' => $thread->posts()->whereKey($parent)->exists() ? $parent : null,
            'number' => ForumPost::nextNumber($thread),
            'body' => $validated['body'],
            ...$this->media($request->file('media')),
        ]));

        $thread->bump();

        return redirect()->route('forum.thread', $thread)
            ->withFragment('p'.$post->number)
            ->with('status', 'Replied as No.'.$post->number.'.');
    }

    /**
     * The largest upload this server will take, in kilobytes. A file over PHP's own
     * limits never reaches the request, so promising more than PHP allows would make
     * an oversized upload look like a form that silently did nothing.
     */
    private function maxUpload(): int
    {
        return (int) collect([
            self::MAX_UPLOAD,
            self::kilobytes((string) ini_get('upload_max_filesize')),
            // The body carries the file plus the rest of the form, so leave some room.
            self::kilobytes((string) ini_get('post_max_size')) - 512,
        ])->filter(fn (int $limit) => $limit > 0)->min();
    }

    /** A php.ini size shorthand such as "8M" in kilobytes; 0 when it is unlimited. */
    private static function kilobytes(string $shorthand): int
    {
        $value = (int) $shorthand;

        return match (strtolower(substr(trim($shorthand), -1))) {
            'g' => $value * 1024 * 1024,
            'm' => $value * 1024,
            'k' => $value,
            default => intdiv($value, 1024),
        };
    }

    /**
     * Store the attachment, if one came with the post. The kind is read off the
     * file itself, so it always matches what will be rendered.
     *
     * @return array{media_path: ?string, media_kind: ?MediaKind}
     */
    private function media(?UploadedFile $file): array
    {
        if (! $file) {
            return ['media_path' => null, 'media_kind' => null];
        }

        return [
            'media_path' => $file->store('forum', 'public'),
            'media_kind' => MediaKind::fromMime((string) $file->getMimeType()),
        ];
    }
}
