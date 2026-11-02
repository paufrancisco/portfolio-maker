<?php

namespace Tests\Feature;

use App\Models\Portfolio;
use App\Models\Template;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortfolioSkillGroupsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->assertSame('sqlite', config('database.default'), 'Tests must use SQLite.');

        Template::create(['key' => 'professional', 'name' => 'Professional', 'theme_options' => [], 'is_active' => true]);
    }

    private function save(string $skills): Portfolio
    {
        $user = User::factory()->create();
        $portfolio = Portfolio::create([
            'user_id' => $user->id,
            'template_key' => 'professional',
            'slug' => 'maria',
            'status' => 'draft',
            'data' => ['name' => 'Maria'],
        ]);

        $this->actingAs($user)->put(route('portfolios.update', $portfolio), [
            'template_key' => 'professional',
            'profile' => ['name' => 'Maria', 'skills' => $skills],
        ])->assertSessionHasNoErrors();

        return $portfolio->fresh();
    }

    public function test_group_lines_are_saved_as_groups_and_flat_list(): void
    {
        $data = $this->save("Frontend: Vue, React\nBackend: Laravel, MySQL")->data;

        $this->assertSame([
            ['name' => 'Frontend', 'items' => ['Vue', 'React']],
            ['name' => 'Backend', 'items' => ['Laravel', 'MySQL']],
        ], $data['skill_groups']);
        $this->assertSame(['Vue', 'React', 'Laravel', 'MySQL'], $data['skills']);
    }

    public function test_plain_comma_list_stays_flat_with_no_groups(): void
    {
        $data = $this->save('Laravel, Vue.js, Git')->data;

        $this->assertSame([], $data['skill_groups']);
        $this->assertSame(['Laravel', 'Vue.js', 'Git'], $data['skills']);
    }

    public function test_loose_items_next_to_groups_go_under_other(): void
    {
        $data = $this->save("Frontend: Vue\nGit, Docker")->data;

        $this->assertSame([
            ['name' => 'Frontend', 'items' => ['Vue']],
            ['name' => 'Other', 'items' => ['Git', 'Docker']],
        ], $data['skill_groups']);
        $this->assertSame(['Vue', 'Git', 'Docker'], $data['skills']);
    }
}
