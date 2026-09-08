<?php

namespace Tests\Feature;

use App\Enums\MediaKind;
use App\Models\ForumPost;
use App\Models\ForumThread;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ForumTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_header_links_to_the_forum(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/profile')
            ->assertOk()
            ->assertSee('Forum');
    }

    public function test_a_visitor_can_read_the_board_but_not_write_to_it(): void
    {
        $thread = $this->thread(User::factory()->create(), ['subject' => 'Kitchen counts']);
        ForumPost::create(['thread_id' => $thread->id, 'user_id' => $thread->user_id, 'number' => 1, 'body' => 'A reply']);

        $this->get('/forum')->assertOk()->assertSee('Kitchen counts');
        $this->get("/forum/{$thread->id}")->assertOk()->assertSee('A reply');

        // Nothing to post with, and nothing gets posted.
        $this->get('/forum')->assertDontSee('Start a new thread');
        $this->get("/forum/{$thread->id}")->assertDontSee('[Reply]');

        $this->post('/forum', ['body' => 'Mine'])->assertRedirect('/login');
        $this->post("/forum/{$thread->id}", ['body' => 'Mine'])->assertRedirect('/login');

        $this->assertSame(1, ForumThread::count());
        $this->assertSame(1, ForumPost::count());
    }

    public function test_a_user_can_start_a_thread(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/forum', [
            'subject' => 'Kitchen counts',
            'body' => 'Where do the numbers come from? https://example.com/story',
        ])->assertRedirect();

        $thread = ForumThread::sole();

        $this->assertSame($user->id, $thread->user_id);
        $this->assertSame('Kitchen counts', $thread->subject);
        $this->assertNotNull($thread->bumped_at);

        $this->actingAs($user)->get('/forum')
            ->assertOk()
            ->assertSee('Kitchen counts')
            ->assertSee('Where do the numbers come from?')
            // A url typed into the comment is the link; there is no separate field.
            ->assertSee('href="https://example.com/story"', false)
            // The form is folded away until it is asked for.
            ->assertSee('Start a new thread');
    }

    public function test_a_thread_needs_a_body(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/forum', ['body' => ''])
            ->assertSessionHasErrors('body');

        $this->assertSame(0, ForumThread::count());
    }

    public function test_a_reply_can_answer_another_reply_and_bumps_the_thread(): void
    {
        $user = User::factory()->create();
        $thread = $this->thread($user, ['bumped_at' => now()->subDay()]);

        $this->actingAs($user)->post("/forum/{$thread->id}", ['body' => 'First'])->assertRedirect();

        $first = ForumPost::sole();

        $this->actingAs($user)->post("/forum/{$thread->id}", [
            'body' => ">>{$first->number}\nAnswering that",
            'parent_id' => $first->id,
        ])->assertRedirect();

        $reply = ForumPost::where('parent_id', $first->id)->sole();

        $this->assertSame($thread->id, $reply->thread_id);
        $this->assertTrue($thread->fresh()->bumped_at->isToday());

        $this->actingAs($user)->get("/forum/{$thread->id}")
            ->assertOk()
            ->assertSee('Answering that')
            // The quote resolves to a link because that post is in this thread.
            ->assertSee('href="#p'.$first->number.'"', false);
    }

    public function test_replies_are_numbered_after_the_opening_post(): void
    {
        $user = User::factory()->create();
        $thread = $this->thread($user);
        $other = $this->thread($user);

        foreach (['One', 'Two'] as $body) {
            $this->actingAs($user)->post("/forum/{$thread->id}", ['body' => $body])->assertRedirect();
        }

        // The first reply is No.1, so >>1 names it and not the post that opened
        // the thread, which is quoted as >>OP.
        $this->assertSame([1, 2], ForumPost::where('thread_id', $thread->id)->orderBy('id')->pluck('number')->all());

        // Numbering is the thread's own, so a second thread counts from 1 again.
        $this->actingAs($user)->post("/forum/{$other->id}", ['body' => 'Elsewhere'])->assertRedirect();

        $this->assertSame(1, ForumPost::where('thread_id', $other->id)->sole()->number);
    }

    public function test_the_opening_post_and_the_first_reply_have_separate_anchors(): void
    {
        $user = User::factory()->create();
        $thread = $this->thread($user);

        $this->actingAs($user)->post("/forum/{$thread->id}", ['body' => 'First'])->assertRedirect();
        $this->actingAs($user)->post("/forum/{$thread->id}", ['body' => ">>1 and >>OP\nBoth"])->assertRedirect();

        $response = $this->actingAs($user)->get("/forum/{$thread->id}")->assertOk();

        // The opening post is anchored as the OP, so #p1 belongs to the first reply
        // alone and >>1 can only land there.
        $response->assertSee('id="op"', false)->assertSee('id="p1"', false);
        $this->assertSame(1, substr_count($response->getContent(), 'id="p1"'));
        $response->assertSee('href="#p1"', false)->assertSee('href="#op"', false);
    }

    public function test_a_reply_cannot_be_attached_to_a_post_from_another_thread(): void
    {
        $user = User::factory()->create();
        $thread = $this->thread($user);
        $other = $this->thread($user);
        $stranger = ForumPost::create(['thread_id' => $other->id, 'user_id' => $user->id, 'body' => 'Elsewhere']);

        $this->actingAs($user)->post("/forum/{$thread->id}", [
            'body' => 'Reply',
            'parent_id' => $stranger->id,
        ])->assertRedirect();

        $this->assertNull(ForumPost::where('thread_id', $thread->id)->sole()->parent_id);
    }

    public function test_an_image_is_stored_and_rendered(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();

        $this->actingAs($user)->post('/forum', [
            'body' => 'Look at this',
            'media' => UploadedFile::fake()->image('kitchen.jpg'),
        ])->assertRedirect();

        $thread = ForumThread::sole();

        $this->assertSame(MediaKind::Image, $thread->media_kind);
        Storage::disk('public')->assertExists($thread->media_path);

        $this->actingAs($user)->get("/forum/{$thread->id}")
            ->assertOk()
            ->assertSee('<img', false);
    }

    public function test_a_video_is_stored_as_a_video(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();

        $this->actingAs($user)->post('/forum', [
            'body' => 'Clip',
            'media' => UploadedFile::fake()->create('clip.mp4', 64, 'video/mp4'),
        ])->assertRedirect();

        $this->assertSame(MediaKind::Video, ForumThread::sole()->media_kind);

        $this->actingAs($user)->get('/forum')->assertOk()->assertSee('<video', false);
    }

    public function test_a_file_that_is_neither_image_nor_video_is_rejected(): void
    {
        Storage::fake('public');

        $this->actingAs(User::factory()->create())->post('/forum', [
            'body' => 'Here',
            'media' => UploadedFile::fake()->create('notes.pdf', 16, 'application/pdf'),
        ])->assertSessionHasErrors('media');

        $this->assertSame(0, ForumThread::count());
    }

    public function test_post_markup_is_escaped(): void
    {
        $user = User::factory()->create();
        $thread = $this->thread($user, ['body' => '<script>alert(1)</script>']);

        $this->actingAs($user)->get("/forum/{$thread->id}")
            ->assertOk()
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('&lt;script&gt;', false);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function thread(User $user, array $attributes = []): ForumThread
    {
        return ForumThread::create([
            'user_id' => $user->id,
            'body' => 'Opening post',
            'bumped_at' => now(),
            ...$attributes,
        ]);
    }
}
