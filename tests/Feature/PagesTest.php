<?php

namespace Tests\Feature;

use Tests\TestCase;

class PagesTest extends TestCase
{
    public function test_home_page_shows_the_welcome_title(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Welcome to Laravel')
            ->assertSee('href="/login"', false)
            ->assertSee('href="/register"', false);
    }

    public function test_about_page(): void
    {
        $this->get('/about')->assertOk()->assertSee('About Us');
    }

    public function test_services_page_lists_the_services(): void
    {
        $this->get('/services')
            ->assertOk()
            ->assertSeeInOrder(['Services', 'Web Design', 'Programming', 'SEO']);
    }

    public function test_layout_loads_the_vite_assets_and_navbar(): void
    {
        $this->get('/about')
            ->assertSee(config('app.name'))
            ->assertSee('href="/posts"', false)
            ->assertSee('Login')
            ->assertSee('Register');
    }
}
