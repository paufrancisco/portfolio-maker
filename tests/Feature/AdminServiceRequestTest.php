<?php

namespace Tests\Feature;

use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminServiceRequestTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->assertSame('sqlite', config('database.default'), 'Tests must use SQLite.');

        $this->withoutVite();
        Storage::fake('s3');
    }

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    private function request(string $status = 'received'): ServiceRequest
    {
        return ServiceRequest::create([
            'type' => 'programming_tutor',
            'email' => 'student@example.com',
            'details' => 'Why does my loop never end?',
            'token' => Str::random(40),
            'status' => $status,
        ]);
    }

    public function test_non_admin_cannot_access_requests(): void
    {
        $customer = User::factory()->create(['is_admin' => false]);
        $serviceRequest = $this->request();

        $this->actingAs($customer)->get('/admin/requests')->assertForbidden();
        $this->actingAs($customer)->post("/admin/requests/{$serviceRequest->id}/quote", ['quote_amount' => 100])->assertForbidden();
        $this->assertSame('received', $serviceRequest->fresh()->status);
    }

    public function test_admin_can_send_a_quote(): void
    {
        $serviceRequest = $this->request();

        $this->actingAs($this->admin())
            ->post("/admin/requests/{$serviceRequest->id}/quote", ['quote_amount' => 250, 'quote_note' => 'Includes the video.'])
            ->assertRedirect();

        $serviceRequest->refresh();
        $this->assertSame('quoted', $serviceRequest->status);
        $this->assertSame('250.00', $serviceRequest->quote_amount);
    }

    public function test_quote_is_rejected_once_paid(): void
    {
        $serviceRequest = $this->request('paid');

        $this->actingAs($this->admin())
            ->post("/admin/requests/{$serviceRequest->id}/quote", ['quote_amount' => 999])
            ->assertStatus(422);

        $this->assertSame('paid', $serviceRequest->fresh()->status);
    }

    public function test_cannot_deliver_before_payment(): void
    {
        $serviceRequest = $this->request('quoted');

        $this->actingAs($this->admin())
            ->post("/admin/requests/{$serviceRequest->id}/deliver", ['delivery_url' => 'https://youtu.be/abc'])
            ->assertStatus(422);

        $this->assertSame('quoted', $serviceRequest->fresh()->status);
        $this->assertNull($serviceRequest->fresh()->delivery_url);
    }

    public function test_admin_can_mark_paid_and_deliver(): void
    {
        $serviceRequest = $this->request('quoted');
        $admin = $this->admin();

        $this->actingAs($admin)->post("/admin/requests/{$serviceRequest->id}/paid")->assertRedirect();
        $this->assertSame('paid', $serviceRequest->fresh()->status);
        $this->assertNotNull($serviceRequest->fresh()->paid_at);

        $this->actingAs($admin)
            ->post("/admin/requests/{$serviceRequest->id}/deliver", ['delivery_url' => 'https://youtu.be/abc'])
            ->assertRedirect();

        $serviceRequest->refresh();
        $this->assertSame('delivered', $serviceRequest->status);
        $this->assertSame('https://youtu.be/abc', $serviceRequest->delivery_url);
    }

    public function test_admin_can_reject_with_a_note(): void
    {
        $serviceRequest = $this->request();

        $this->actingAs($this->admin())
            ->post("/admin/requests/{$serviceRequest->id}/reject", ['admin_note' => 'Outside my topics.'])
            ->assertRedirect();

        $serviceRequest->refresh();
        $this->assertSame('rejected', $serviceRequest->status);
        $this->assertSame('Outside my topics.', $serviceRequest->admin_note);
    }

    public function test_reject_requires_a_note(): void
    {
        $serviceRequest = $this->request();

        $this->actingAs($this->admin())
            ->post("/admin/requests/{$serviceRequest->id}/reject", ['admin_note' => ''])
            ->assertSessionHasErrors('admin_note');

        $this->assertSame('received', $serviceRequest->fresh()->status);
    }
}
