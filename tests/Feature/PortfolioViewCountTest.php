<?php

namespace Tests\Feature;

use App\Models\Portfolio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortfolioViewCountTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->assertSame('sqlite', config('database.default'), 'Tests must use SQLite.');

        $this->withoutVite();
    }

    private function published(User $owner, string $status = 'published'): Portfolio
    {
        return Portfolio::create([
            'user_id' => $owner->id,
            'template_key' => 'professional',
            'slug' => 'maria',
            'status' => $status,
            'data' => ['name' => 'Maria'],
            'published_data' => ['name' => 'Maria'],
        ]);
    }

    public function test_visitor_view_is_counted(): void
    {
        $portfolio = $this->published(User::factory()->create());

        $this->withHeader('User-Agent', 'Mozilla/5.0 Firefox/130.0')->get('/p/maria')->assertOk();

        $this->assertSame(1, (int) $portfolio->fresh()->view_count);
    }

    public function test_repeat_visit_with_cookie_is_not_counted(): void
    {
        $portfolio = $this->published(User::factory()->create());

        $this->withHeader('User-Agent', 'Mozilla/5.0 Firefox/130.0')
            ->withCookie('pv_'.$portfolio->id, '1')
            ->get('/p/maria')->assertOk();

        $this->assertSame(0, (int) $portfolio->fresh()->view_count);
    }

    public function test_owner_visit_is_not_counted(): void
    {
        $owner = User::factory()->create();
        $portfolio = $this->published($owner);

        $this->actingAs($owner)->withHeader('User-Agent', 'Mozilla/5.0 Firefox/130.0')->get('/p/maria')->assertOk();

        $this->assertSame(0, (int) $portfolio->fresh()->view_count);
    }

    public function test_bot_visit_is_not_counted(): void
    {
        $portfolio = $this->published(User::factory()->create());

        $this->withHeader('User-Agent', 'Mozilla/5.0 (compatible; Googlebot/2.1)')->get('/p/maria')->assertOk();

        $this->assertSame(0, (int) $portfolio->fresh()->view_count);
    }

    public function test_unpublished_portfolio_is_404_and_not_counted(): void
    {
        $portfolio = $this->published(User::factory()->create(), 'draft');

        $this->withHeader('User-Agent', 'Mozilla/5.0 Firefox/130.0')->get('/p/maria')->assertNotFound();

        $this->assertSame(0, (int) $portfolio->fresh()->view_count);
    }
}
