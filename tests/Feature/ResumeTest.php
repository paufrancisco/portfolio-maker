<?php

namespace Tests\Feature;

use App\Models\Resume;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ResumeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->assertSame('sqlite', config('database.default'), 'Tests must use SQLite.');

        $this->withoutVite();
    }

    private function resumeFor(User $user): Resume
    {
        return Resume::create([
            'user_id' => $user->id,
            'title' => 'My resume',
            'template_key' => 'classic',
            'data' => Resume::blankData(),
        ]);
    }

    private function payload(array $content = []): array
    {
        return [
            'title' => 'Updated resume',
            'template_key' => 'modern',
            'content' => array_replace_recursive(Resume::blankData(), $content),
        ];
    }

    public function test_guest_is_sent_to_login(): void
    {
        $this->get('/resumes')->assertRedirect('/login');
        $this->post('/resumes')->assertRedirect('/login');
    }

    public function test_user_can_create_a_resume_prefilled_with_their_details(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $response = $this->actingAs($user)->post('/resumes', ['template_key' => 'minimal']);

        $resume = Resume::where('user_id', $user->id)->firstOrFail();
        $response->assertRedirect("/resumes/{$resume->id}/edit");
        $this->assertSame('minimal', $resume->template_key);
        $this->assertSame($user->email, $resume->data['personal']['email']);

        $this->actingAs($user)->get("/resumes/{$resume->id}/edit")
            ->assertInertia(fn (Assert $page) => $page->component('Resumes/Edit')->where('resume.id', $resume->id));
    }

    public function test_user_can_save_and_empty_rows_are_dropped(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $resume = $this->resumeFor($user);

        $this->actingAs($user)->put("/resumes/{$resume->id}", $this->payload([
            'personal' => ['name' => 'Ana Cruz', 'email' => 'ana@example.com'],
            'experience' => [
                ['role' => 'Developer', 'company' => 'Acme', 'period' => '2024', 'details' => 'Built things'],
                ['role' => '', 'company' => '', 'period' => '', 'details' => ''],
            ],
            'skills' => ['PHP', 'Vue'],
        ]))->assertRedirect();

        $resume->refresh();
        $this->assertSame('Updated resume', $resume->title);
        $this->assertSame('modern', $resume->template_key);
        $this->assertSame('Ana Cruz', $resume->data['personal']['name']);
        $this->assertCount(1, $resume->data['experience']);
        $this->assertSame(['PHP', 'Vue'], $resume->data['skills']);
    }

    public function test_validation_rejects_bad_input(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $resume = $this->resumeFor($user);

        $bad = $this->payload(['personal' => ['email' => 'not-an-email']]);
        $bad['template_key'] = 'fancy';
        $bad['title'] = '';

        $this->actingAs($user)->put("/resumes/{$resume->id}", $bad)
            ->assertSessionHasErrors(['title', 'template_key', 'content.personal.email']);

        $this->assertSame('My resume', $resume->fresh()->title);
    }

    public function test_user_cannot_touch_someone_elses_resume(): void
    {
        $owner = User::factory()->create(['is_admin' => false]);
        $other = User::factory()->create(['is_admin' => false]);
        $resume = $this->resumeFor($owner);

        $this->actingAs($other)->get("/resumes/{$resume->id}/edit")->assertForbidden();
        $this->actingAs($other)->put("/resumes/{$resume->id}", $this->payload())->assertForbidden();
        $this->actingAs($other)->delete("/resumes/{$resume->id}")->assertForbidden();

        $this->assertSame('My resume', $resume->fresh()->title);
    }

    public function test_user_can_delete_their_own_resume(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $resume = $this->resumeFor($user);

        $this->actingAs($user)->delete("/resumes/{$resume->id}")->assertRedirect('/resumes');

        $this->assertNull(Resume::find($resume->id));
    }
}
