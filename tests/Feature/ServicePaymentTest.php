<?php

namespace Tests\Feature;

use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ServicePaymentTest extends TestCase
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

    private function request(string $status = 'quoted'): ServiceRequest
    {
        return ServiceRequest::create([
            'type' => 'math_tutor',
            'email' => 'student@example.com',
            'details' => 'Please explain this integral.',
            'token' => Str::random(40),
            'status' => $status,
            'quote_amount' => 300,
        ]);
    }

    private function proof(string $name = 'proof.jpg'): UploadedFile
    {
        return UploadedFile::fake()->image($name);
    }

    public function test_client_can_submit_proof_when_quoted(): void
    {
        $serviceRequest = $this->request();

        $this->post("/services/{$serviceRequest->token}/payment", [
            'reference_no' => 'REF-123',
            'proof' => $this->proof(),
        ])->assertRedirect("/services/{$serviceRequest->token}");

        $serviceRequest->refresh();
        $this->assertSame('payment_submitted', $serviceRequest->status);
        $this->assertSame('REF-123', $serviceRequest->payment_reference);
        $this->assertNotNull($serviceRequest->payment_submitted_at);
        Storage::disk('s3')->assertExists($serviceRequest->payment_proof_path);
    }

    public function test_proof_is_refused_unless_quoted(): void
    {
        foreach (['received', 'payment_submitted', 'paid', 'delivered', 'rejected'] as $status) {
            $serviceRequest = $this->request($status);

            $this->post("/services/{$serviceRequest->token}/payment", [
                'reference_no' => 'REF-1',
                'proof' => $this->proof(),
            ])->assertStatus(422);

            $this->assertSame($status, $serviceRequest->fresh()->status);
            $this->assertNull($serviceRequest->fresh()->payment_proof_path);
        }
    }

    public function test_payment_validation(): void
    {
        $serviceRequest = $this->request();

        $this->post("/services/{$serviceRequest->token}/payment", [])
            ->assertSessionHasErrors(['reference_no', 'proof']);

        $this->post("/services/{$serviceRequest->token}/payment", [
            'reference_no' => 'REF-1',
            'proof' => UploadedFile::fake()->create('proof.exe', 10),
        ])->assertSessionHasErrors('proof');

        $this->assertSame('quoted', $serviceRequest->fresh()->status);
    }

    public function test_admin_can_approve_a_submitted_payment(): void
    {
        $serviceRequest = $this->request('payment_submitted');

        $this->actingAs($this->admin())
            ->post("/admin/requests/{$serviceRequest->id}/payment/approve")
            ->assertRedirect();

        $serviceRequest->refresh();
        $this->assertSame('paid', $serviceRequest->status);
        $this->assertNotNull($serviceRequest->paid_at);
    }

    public function test_approve_and_reject_payment_need_a_submitted_payment(): void
    {
        $admin = $this->admin();
        $serviceRequest = $this->request('quoted');

        $this->actingAs($admin)->post("/admin/requests/{$serviceRequest->id}/payment/approve")->assertStatus(422);
        $this->actingAs($admin)
            ->post("/admin/requests/{$serviceRequest->id}/payment/reject", ['admin_note' => 'No money.'])
            ->assertStatus(422);

        $this->assertSame('quoted', $serviceRequest->fresh()->status);
    }

    public function test_reject_payment_requires_a_note(): void
    {
        $serviceRequest = $this->request('payment_submitted');

        $this->actingAs($this->admin())
            ->post("/admin/requests/{$serviceRequest->id}/payment/reject", ['admin_note' => ''])
            ->assertSessionHasErrors('admin_note');

        $this->assertSame('payment_submitted', $serviceRequest->fresh()->status);
    }

    public function test_rejected_proof_returns_to_quoted_and_client_can_resubmit(): void
    {
        $serviceRequest = $this->request();

        $this->post("/services/{$serviceRequest->token}/payment", [
            'reference_no' => 'REF-1',
            'proof' => $this->proof('first.jpg'),
        ]);
        $oldPath = $serviceRequest->fresh()->payment_proof_path;
        Storage::disk('s3')->assertExists($oldPath);

        $this->actingAs($this->admin())
            ->post("/admin/requests/{$serviceRequest->id}/payment/reject", ['admin_note' => 'Amount does not match.'])
            ->assertRedirect();

        $this->assertSame('quoted', $serviceRequest->fresh()->status);

        $this->get("/services/{$serviceRequest->token}")
            ->assertInertia(fn (Assert $page) => $page
                ->component('Services/Show')
                ->where('serviceRequest.can_pay', true)
                ->where('serviceRequest.payment_note', 'Amount does not match.'));

        $this->post("/services/{$serviceRequest->token}/payment", [
            'reference_no' => 'REF-2',
            'proof' => $this->proof('second.jpg'),
        ])->assertRedirect();

        $serviceRequest->refresh();
        $this->assertSame('payment_submitted', $serviceRequest->status);
        $this->assertSame('REF-2', $serviceRequest->payment_reference);
        $this->assertNull($serviceRequest->admin_note);
        Storage::disk('s3')->assertMissing($oldPath);
        Storage::disk('s3')->assertExists($serviceRequest->payment_proof_path);
    }

    public function test_non_admin_cannot_approve_or_reject_payment(): void
    {
        $customer = User::factory()->create(['is_admin' => false]);
        $serviceRequest = $this->request('payment_submitted');

        $this->actingAs($customer)->post("/admin/requests/{$serviceRequest->id}/payment/approve")->assertForbidden();
        $this->actingAs($customer)
            ->post("/admin/requests/{$serviceRequest->id}/payment/reject", ['admin_note' => 'x'])
            ->assertForbidden();

        $this->assertSame('payment_submitted', $serviceRequest->fresh()->status);
    }
}
