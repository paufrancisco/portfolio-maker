<?php

namespace Tests\Feature;

use App\Models\Portfolio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CleanOrphanImagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->assertSame('sqlite', config('database.default'), 'Tests must use SQLite.');

        Storage::fake('images');
    }

    private function put(string $path, bool $old = true): void
    {
        Storage::disk('images')->put($path, 'x');

        if ($old) {
            touch(Storage::disk('images')->path($path), time() - 3 * 86400);
        }
    }

    public function test_dry_run_lists_but_deletes_nothing(): void
    {
        $this->put('portfolios/1/orphan.jpg');

        Artisan::call('portfolio:clean-images');

        $this->assertStringContainsString('portfolios/1/orphan.jpg', Artisan::output());
        Storage::disk('images')->assertExists('portfolios/1/orphan.jpg');
    }

    public function test_delete_removes_only_old_unreferenced_portfolio_images(): void
    {
        $disk = Storage::disk('images');

        $this->put('portfolios/1/orphan.jpg');
        $this->put('portfolios/1/photo.jpg');
        $this->put('portfolios/1/proj.jpg');
        $this->put('portfolios/1/recent.jpg', old: false);
        $this->put('site/payment-qr.jpg');

        Portfolio::create([
            'user_id' => User::factory()->create()->id,
            'template_key' => 'professional',
            'slug' => 'maria',
            'status' => 'published',
            'data' => ['name' => 'Maria', 'photo_url' => $disk->url('portfolios/1/photo.jpg')],
            'published_data' => ['name' => 'Maria', 'projects' => [['title' => 'App', 'image_url' => $disk->url('portfolios/1/proj.jpg')]]],
        ]);

        $this->assertSame(0, Artisan::call('portfolio:clean-images', ['--delete' => true]));

        $disk->assertMissing('portfolios/1/orphan.jpg');
        $disk->assertExists('portfolios/1/photo.jpg');
        $disk->assertExists('portfolios/1/proj.jpg');
        $disk->assertExists('portfolios/1/recent.jpg');
        $disk->assertExists('site/payment-qr.jpg');
    }

    public function test_refuses_to_delete_more_than_max(): void
    {
        foreach (['a', 'b', 'c'] as $name) {
            $this->put("portfolios/1/$name.jpg");
        }

        $this->assertSame(1, Artisan::call('portfolio:clean-images', ['--delete' => true, '--max' => 2]));

        foreach (['a', 'b', 'c'] as $name) {
            Storage::disk('images')->assertExists("portfolios/1/$name.jpg");
        }
    }
}
