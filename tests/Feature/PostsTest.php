<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PostsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    public function test_guest_sees_the_post_list_newest_first_with_pagination(): void
    {
        $author = User::factory()->create(['name' => 'Post Author']);
        Post::factory()->for($author)->create(['title' => 'Older post', 'created_at' => now()->subDay()]);
        Post::factory()->for($author)->create(['title' => 'Newer post']);
        Post::factory()->count(10)->for($author)->create(['created_at' => now()->subDays(2)]);

        $this->get('/posts')
            ->assertOk()
            ->assertSeeInOrder(['Newer post', 'Older post'])
            ->assertSee('by Post Author')
            ->assertSee('class="pagination"', false)
            ->assertSee('/storage/cover_images/noimage.png', false);
    }

    public function test_empty_post_list(): void
    {
        $this->get('/posts')->assertOk()->assertSee('No posts found');
    }

    public function test_guest_can_read_a_post_but_not_manage_it(): void
    {
        $post = Post::factory()->create(['body' => '<p><strong>Rich</strong> body</p>']);

        $this->get('/posts/'.$post->id)
            ->assertOk()
            ->assertSee($post->title)
            ->assertSee('<strong>Rich</strong> body', false)
            ->assertDontSee('btn btn-warning', false);
    }

    public function test_missing_post_is_404(): void
    {
        $this->get('/posts/999')->assertNotFound();
    }

    public function test_guest_is_sent_to_login_for_every_write_route(): void
    {
        $post = Post::factory()->create();

        $this->get('/posts/create')->assertRedirect('/login');
        $this->post('/posts', ['title' => 'x', 'body' => 'y'])->assertRedirect('/login');
        $this->get('/posts/'.$post->id.'/edit')->assertRedirect('/login');
        $this->put('/posts/'.$post->id, ['title' => 'x', 'body' => 'y'])->assertRedirect('/login');
        $this->delete('/posts/'.$post->id)->assertRedirect('/login');

        $this->assertDatabaseCount('posts', 1);
    }

    public function test_create_form_has_the_editor_textarea_and_upload(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/posts/create')
            ->assertOk()
            ->assertSee('action="'.route('posts.store').'"', false)
            ->assertSee('enctype="multipart/form-data"', false)
            ->assertSee('id="article-ckeditor"', false)
            ->assertSee('name="cover_image"', false)
            ->assertSee('name="_token"', false);
    }

    public function test_user_can_create_a_post_without_an_image(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/posts', ['title' => 'Hello', 'body' => '<p>World</p>'])
            ->assertRedirect('/posts')
            ->assertSessionHas('success', 'Post Created');

        $this->assertDatabaseHas('posts', [
            'title' => 'Hello',
            'body' => '<p>World</p>',
            'user_id' => $user->id,
            'cover_image' => 'noimage.png',
        ]);
    }

    public function test_user_can_create_a_post_with_a_cover_image(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/posts', [
            'title' => 'With image',
            'body' => 'Body',
            'cover_image' => UploadedFile::fake()->image('cover.jpg'),
        ])->assertRedirect('/posts');

        $post = Post::where('title', 'With image')->firstOrFail();
        $this->assertMatchesRegularExpression('/^cover_\d+\.jpg$/', $post->cover_image);
        Storage::disk('public')->assertExists('cover_images/'.$post->cover_image);
    }

    public function test_create_validates_input(): void
    {
        $this->actingAs(User::factory()->create())
            ->from('/posts/create')
            ->post('/posts', [
                'title' => '',
                'body' => '',
                'cover_image' => UploadedFile::fake()->create('shell.php', 1, 'text/x-php'),
            ])
            ->assertRedirect('/posts/create')
            ->assertSessionHasErrors(['title', 'body', 'cover_image']);

        $this->assertDatabaseCount('posts', 0);
    }

    public function test_owner_can_edit_and_update_a_post(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->for($user)->create(['title' => 'Old title']);

        $this->actingAs($user)
            ->get('/posts/'.$post->id.'/edit')
            ->assertOk()
            ->assertSee('value="Old title"', false)
            ->assertSee('name="_method" value="PUT"', false);

        $this->actingAs($user)->put('/posts/'.$post->id, [
            'title' => 'New title',
            'body' => 'New body',
            'cover_image' => UploadedFile::fake()->image('new.png'),
        ])->assertRedirect('/posts')->assertSessionHas('success', 'Post Updated');

        $post->refresh();
        $this->assertSame('New title', $post->title);
        $this->assertSame('New body', $post->body);
        Storage::disk('public')->assertExists('cover_images/'.$post->cover_image);
    }

    public function test_update_without_image_keeps_the_cover(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->for($user)->create(['cover_image' => 'kept.jpg']);

        $this->actingAs($user)->put('/posts/'.$post->id, ['title' => 'T', 'body' => 'B'])
            ->assertRedirect('/posts');

        $this->assertSame('kept.jpg', $post->fresh()->cover_image);
    }

    public function test_update_rejects_a_non_image_upload(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->for($user)->create();

        $this->actingAs($user)->from('/posts/'.$post->id.'/edit')->put('/posts/'.$post->id, [
            'title' => 'T',
            'body' => 'B',
            'cover_image' => UploadedFile::fake()->create('shell.php', 1, 'text/x-php'),
        ])->assertSessionHasErrors('cover_image');

        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_other_users_cannot_edit_update_or_delete(): void
    {
        $post = Post::factory()->create(['title' => 'Untouched']);
        $intruder = User::factory()->create();

        $this->actingAs($intruder)->get('/posts/'.$post->id.'/edit')
            ->assertRedirect('/posts')->assertSessionHas('error', 'Unauthorized Page');
        $this->actingAs($intruder)->put('/posts/'.$post->id, ['title' => 'Hacked', 'body' => 'x'])
            ->assertRedirect('/posts')->assertSessionHas('error', 'Unauthorized Page');
        $this->actingAs($intruder)->delete('/posts/'.$post->id)
            ->assertRedirect('/posts')->assertSessionHas('error', 'Unauthorized Page');

        $this->assertSame('Untouched', $post->fresh()->title);
    }

    public function test_owner_sees_edit_and_delete_on_the_post_page(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->for($user)->create();

        $this->actingAs($user)->get('/posts/'.$post->id)
            ->assertOk()
            ->assertSee('href="/posts/'.$post->id.'/edit"', false)
            ->assertSee('name="_method" value="DELETE"', false);
    }

    public function test_owner_can_delete_a_post_and_its_image(): void
    {
        $user = User::factory()->create();
        Storage::disk('public')->put('cover_images/pic_1.jpg', 'x');
        $post = Post::factory()->for($user)->create(['cover_image' => 'pic_1.jpg']);

        $this->actingAs($user)->delete('/posts/'.$post->id)
            ->assertRedirect('/posts')->assertSessionHas('success', 'Post Removed');

        $this->assertModelMissing($post);
        Storage::disk('public')->assertMissing('cover_images/pic_1.jpg');
    }

    private const ATTACK = '<p>Hi<script>alert(1)</script></p><img src=x onerror="alert(2)">'
        . '<a href="javascript:alert(3)">click</a><iframe src="https://evil.test"></iframe>'
        . '<p onclick="alert(4)" style="color:red">styled</p>';

    private const FORMATTING = '<h2>Title</h2><p><strong>bold</strong> <i>it</i> <a href="https://example.com/x">link</a></p>'
        . '<ul><li>one</li></ul><blockquote><p>quote</p></blockquote>'
        . '<figure class="table"><table><tbody><tr><td colspan="2">cell</td></tr></tbody></table></figure>';

    public function test_a_new_post_body_is_stored_without_anything_that_runs_script(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/posts', ['title' => 'Attack', 'body' => self::ATTACK . self::FORMATTING])->assertRedirect('/posts');

        $body = Post::firstOrFail()->body;
        foreach (['<script', 'onerror', 'onclick', 'javascript:', '<iframe', 'style='] as $bad) {
            $this->assertStringNotContainsStringIgnoringCase($bad, $body);
        }
        foreach (['<h2>Title</h2>', '<strong>bold</strong>', '<i>it</i>', 'href="https://example.com/x"', '<li>one</li>', '<blockquote>', '<td colspan="2">cell</td>'] as $kept) {
            $this->assertStringContainsString($kept, $body);
        }
    }

    public function test_an_updated_post_body_is_cleaned_too(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->for($user)->create();

        $this->actingAs($user)->put("/posts/{$post->id}", ['title' => 'Edited', 'body' => self::ATTACK])->assertRedirect('/posts');

        $this->assertStringNotContainsStringIgnoringCase('<script', $post->fresh()->body);
        $this->assertStringNotContainsStringIgnoringCase('onerror', $post->fresh()->body);
    }

    public function test_a_post_saved_before_the_fix_is_shown_cleaned(): void
    {
        $post = Post::factory()->for(User::factory())->create(['body' => self::ATTACK . '<p><strong>kept</strong></p>']);

        $html = $this->get("/posts/{$post->id}")->assertOk()->getContent();

        foreach (['<script>alert(1)', 'onerror=', 'onclick=', 'javascript:alert', '<iframe'] as $bad) {
            $this->assertStringNotContainsStringIgnoringCase($bad, $html);
        }
        $this->assertStringContainsString('<strong>kept</strong>', $html);
    }
}
