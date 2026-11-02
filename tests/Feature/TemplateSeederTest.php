<?php

namespace Tests\Feature;

use App\Models\Template;
use Database\Seeders\TemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TemplateSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_all_portfolio_templates_and_is_repeatable(): void
    {
        $this->assertSame('sqlite', config('database.default'), 'Tests must use SQLite.');

        $this->seed(TemplateSeeder::class);
        $this->seed(TemplateSeeder::class);

        $this->assertSame(
            ['blue', 'editorial', 'minimal', 'pink', 'professional', 'showcase'],
            Template::where('is_active', true)->orderBy('key')->pluck('key')->all()
        );
    }
}
