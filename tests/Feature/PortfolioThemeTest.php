<?php

namespace Tests\Feature;

use App\Models\Portfolio;
use App\Models\Template;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PortfolioThemeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->assertSame('sqlite', config('database.default'), 'Tests must use SQLite.');

        $this->withoutVite();

        Template::create([
            'key' => 'professional',
            'name' => 'Professional',
            'theme_options' => ['primary' => '#1f2937', 'accent' => '#2563eb', 'background' => '#ffffff', 'font' => 'Inter, sans-serif'],
            'is_active' => true,
        ]);
    }

    private function portfolio(User $user, array $extra = []): Portfolio
    {
        return Portfolio::create($extra + [
            'user_id' => $user->id,
            'template_key' => 'professional',
            'slug' => 'maria',
            'status' => 'draft',
            'data' => ['name' => 'Maria'],
        ]);
    }

    public function test_owner_can_save_custom_colors(): void
    {
        $user = User::factory()->create();
        $portfolio = $this->portfolio($user);

        $this->actingAs($user)->put(route('portfolios.update', $portfolio), [
            'template_key' => 'professional',
            'profile' => ['name' => 'Maria', 'theme' => ['accent' => '#ff0000', 'primary' => '', 'background' => '']],
        ])->assertSessionHasNoErrors();

        $this->assertSame(['accent' => '#ff0000'], $portfolio->fresh()->data['theme']);
    }

    public function test_invalid_color_is_rejected(): void
    {
        $user = User::factory()->create();
        $portfolio = $this->portfolio($user);

        $this->actingAs($user)->put(route('portfolios.update', $portfolio), [
            'template_key' => 'professional',
            'profile' => ['name' => 'Maria', 'theme' => ['accent' => 'red']],
        ])->assertSessionHasErrors('profile.theme.accent');
    }

    public function test_live_page_uses_published_colors_over_template(): void
    {
        $user = User::factory()->create();
        $this->portfolio($user, [
            'status' => 'published',
            'data' => ['name' => 'Maria', 'theme' => ['accent' => '#00ff00']],
            'published_data' => ['name' => 'Maria', 'theme' => ['accent' => '#ff0000']],
        ]);

        $this->get('/p/maria')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('theme.accent', '#ff0000')
            ->where('theme.primary', '#1f2937'));
    }

    public function test_live_page_falls_back_to_template_colors(): void
    {
        $user = User::factory()->create();
        $this->portfolio($user, ['status' => 'published', 'published_data' => ['name' => 'Maria']]);

        $this->get('/p/maria')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('theme.accent', '#2563eb'));
    }
}
