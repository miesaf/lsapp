<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_sent_to_login(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_dashboard_lists_only_the_users_posts(): void
    {
        $user = User::factory()->create();
        $mine = Post::factory()->for($user)->create(['title' => 'My own post']);
        Post::factory()->create(['title' => 'Someone else post']);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Your Blog Post')
            ->assertSee('My own post')
            ->assertDontSee('Someone else post')
            ->assertSee('href="/posts/'.$mine->id.'/edit"', false)
            ->assertSee('action="'.route('posts.destroy', $mine).'"', false);
    }

    public function test_dashboard_without_posts(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('You have no posts');
    }
}
