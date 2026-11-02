<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class LegalPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->assertSame('sqlite', config('database.default'), 'Tests must use SQLite.');

        $this->withoutVite();
    }

    public function test_terms_and_privacy_are_public(): void
    {
        foreach (['terms', 'privacy'] as $page) {
            $this->get("/{$page}")
                ->assertOk()
                ->assertInertia(fn (Assert $p) => $p->component('Legal/Show')->where('page', $page));
        }
    }

    public function test_pages_also_open_when_logged_in(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)->get('/terms')->assertOk();
        $this->actingAs($user)->get('/privacy')->assertOk();
    }
}
