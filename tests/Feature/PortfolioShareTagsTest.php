<?php

namespace Tests\Feature;

use App\Models\Portfolio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortfolioShareTagsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->assertSame('sqlite', config('database.default'), 'Tests must use SQLite.');

        $this->withoutVite();
    }

    private function published(array $published, array $draft = []): void
    {
        Portfolio::create([
            'user_id' => User::factory()->create()->id,
            'template_key' => 'professional',
            'slug' => 'maria',
            'status' => 'published',
            'data' => $draft ?: $published,
            'published_data' => $published,
        ]);
    }

    public function test_live_page_has_share_tags_from_published_data(): void
    {
        $this->published(
            ['name' => 'Maria', 'headline' => 'Web Dev', 'intro' => 'I build apps.', 'photo_url' => 'https://example.com/me.jpg'],
            ['name' => 'Draft Name', 'headline' => 'Draft headline']
        );

        $this->get('/p/maria')->assertOk()
            ->assertSee('<meta property="og:title" content="Maria | Web Dev">', false)
            ->assertSee('<meta property="og:description" content="I build apps.">', false)
            ->assertSee('<meta property="og:image" content="https://example.com/me.jpg">', false)
            ->assertSee('summary_large_image', false)
            ->assertDontSee('Draft Name');
    }

    public function test_no_image_tag_without_a_photo(): void
    {
        $this->published(['name' => 'Maria', 'about' => "Line one\nline two."]);

        $this->get('/p/maria')->assertOk()
            ->assertSee('<meta property="og:title" content="Maria">', false)
            ->assertSee('Line one line two.', false)
            ->assertDontSee('og:image', false);
    }

    public function test_other_pages_have_no_share_tags(): void
    {
        $this->get('/')->assertOk()->assertDontSee('og:title', false);
    }
}
