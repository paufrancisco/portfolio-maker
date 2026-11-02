<?php

namespace Tests\Feature;

use App\Models\Resume;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResumeDownloadTest extends TestCase
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
        $data['summary'] = 'Developer from Cavite. Salary range ₱30,000. José Rizal fan.';
        $data['experience'] = [['role' => 'Developer', 'company' => 'Acme', 'period' => '2024', 'details' => "Built things\nShipped features"]];
        $data['education'] = [['school' => 'STI College', 'degree' => 'BSIT', 'period' => '2023', 'details' => '']];
        $data['skills'] = ['PHP', 'Vue'];
        $data['projects'] = [['name' => 'CampusCraft', 'link' => 'https://example.com', 'details' => 'A school services site']];

        return Resume::create(['user_id' => $user->id, 'title' => 'My resume', 'template_key' => $template, 'data' => $data]);
    }

    public function test_owner_can_download_a_pdf_for_every_template(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        foreach (array_keys(Resume::TEMPLATES) as $template) {
            $resume = $this->resumeFor($user, $template);

            $response = $this->actingAs($user)->get("/resumes/{$resume->id}/download");

            $response->assertOk()->assertHeader('content-type', 'application/pdf');
            $this->assertStringStartsWith('%PDF', $response->getContent());
        }
    }

    public function test_filename_comes_from_the_name_on_the_resume(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $resume = $this->resumeFor($user);

        $response = $this->actingAs($user)->get("/resumes/{$resume->id}/download");

        $this->assertStringContainsString('ana-cruz-resume.pdf', $response->headers->get('content-disposition'));
    }

    public function test_other_users_cannot_download(): void
    {
        $owner = User::factory()->create(['is_admin' => false]);
        $other = User::factory()->create(['is_admin' => false]);
        $resume = $this->resumeFor($owner);

        $this->actingAs($other)->get("/resumes/{$resume->id}/download")->assertForbidden();
    }

    public function test_guest_is_sent_to_login(): void
    {
        $owner = User::factory()->create(['is_admin' => false]);
        $resume = $this->resumeFor($owner);

        $this->get("/resumes/{$resume->id}/download")->assertRedirect('/login');
    }
}
