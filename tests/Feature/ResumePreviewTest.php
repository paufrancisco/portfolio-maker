<?php

namespace Tests\Feature;

use App\Models\Resume;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResumePreviewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->assertSame('sqlite', config('database.default'), 'Tests must use SQLite.');

        $this->withoutVite();
    }

    private function resumeFor(User $user, string $template = 'classic', string $name = 'Ana Cruz'): Resume
    {
        $data = Resume::blankData();
        $data['personal']['name'] = $name;
        $data['personal']['email'] = 'ana@example.com';
        $data['experience'] = [['role' => 'Developer', 'company' => 'Acme', 'period' => '2024', 'details' => "Built things\nShipped ₱ features"]];
        $data['skills'] = ['PHP', 'Vue'];

        return Resume::create(['user_id' => $user->id, 'title' => 'My resume', 'template_key' => $template, 'data' => $data]);
    }

    public function test_owner_can_preview_every_template(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        foreach (array_keys(Resume::TEMPLATES) as $template) {
            $resume = $this->resumeFor($user, $template);

            $this->actingAs($user)->get("/resumes/{$resume->id}/preview")
                ->assertOk()
                ->assertSee('Ana Cruz')
                ->assertSee('Developer')
                ->assertSee('PHP');
        }
    }

    public function test_unknown_template_falls_back_to_classic(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $resume = $this->resumeFor($user, 'bogus');

        $this->actingAs($user)->get("/resumes/{$resume->id}/preview")->assertOk()->assertSee('Ana Cruz');
    }

    public function test_user_input_is_escaped(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $resume = $this->resumeFor($user, 'classic', '<script>alert(1)</script>');

        $this->actingAs($user)->get("/resumes/{$resume->id}/preview")
            ->assertOk()
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('&lt;script&gt;', false);
    }

    public function test_other_users_cannot_preview(): void
    {
        $owner = User::factory()->create(['is_admin' => false]);
        $other = User::factory()->create(['is_admin' => false]);
        $resume = $this->resumeFor($owner);

        $this->actingAs($other)->get("/resumes/{$resume->id}/preview")->assertForbidden();
    }

    public function test_guest_is_sent_to_login(): void
    {
        $owner = User::factory()->create(['is_admin' => false]);
        $resume = $this->resumeFor($owner);

        $this->get("/resumes/{$resume->id}/preview")->assertRedirect('/login');
    }
}
