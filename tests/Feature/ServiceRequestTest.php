<?php

namespace Tests\Feature;

use App\Models\ServiceRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ServiceRequestTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->assertSame('sqlite', config('database.default'), 'Tests must use SQLite.');

        $this->withoutVite();
        Storage::fake('s3');
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'type' => 'thesis_debug',
            'email' => 'student@example.com',
            'details' => 'My Laravel login page returns a 419 error.',
        ], $overrides);
    }

    public function test_visitor_can_submit_a_request_without_logging_in(): void
    {
        $response = $this->post(route('services.store'), $this->payload());

        $serviceRequest = ServiceRequest::firstOrFail();
        $this->assertSame('student@example.com', $serviceRequest->email);
        $this->assertSame('received', $serviceRequest->status);
        $this->assertSame(40, strlen($serviceRequest->token));
        $response->assertRedirect(route('services.show', $serviceRequest->token));
    }

    public function test_invalid_type_is_rejected(): void
    {
        $this->post(route('services.store'), $this->payload(['type' => 'hacking']))
            ->assertSessionHasErrors('type');

        $this->assertSame(0, ServiceRequest::count());
    }

    public function test_attachment_is_stored_on_the_private_disk(): void
    {
        $file = UploadedFile::fake()->create('problem.pdf', 100, 'application/pdf');

        $this->post(route('services.store'), $this->payload(['attachment' => $file]));

        $serviceRequest = ServiceRequest::firstOrFail();
        $this->assertNotNull($serviceRequest->attachment_path);
        Storage::disk('s3')->assertExists($serviceRequest->attachment_path);
    }

    public function test_status_page_is_reachable_only_with_the_right_token(): void
    {
        $this->post(route('services.store'), $this->payload());
        $serviceRequest = ServiceRequest::firstOrFail();

        $this->get(route('services.show', $serviceRequest->token))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Services/Show'));

        $this->get(route('services.show', 'not-a-real-token'))->assertNotFound();
    }
}
