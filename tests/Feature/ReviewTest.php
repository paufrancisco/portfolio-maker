<?php

namespace Tests\Feature;

use App\Models\Review;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->assertSame('sqlite', config('database.default'), 'Tests must use SQLite.');

        $this->withoutVite();
        Storage::fake('s3');
    }

    private function serviceRequest(string $status = 'delivered'): ServiceRequest
    {
        return ServiceRequest::create([
            'type' => 'math_tutor',
            'email' => 'student@example.com',
            'details' => 'Solve this integral for me please.',
            'token' => Str::random(40),
            'status' => $status,
        ]);
    }

    private function reviewPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Maria',
            'rating' => 5,
            'comment' => 'Very clear explanation, thank you!',
        ], $overrides);
    }

    public function test_cannot_review_before_delivery(): void
    {
        $serviceRequest = $this->serviceRequest('quoted');

        $this->post(route('services.review', $serviceRequest->token), $this->reviewPayload())
            ->assertForbidden();

        $this->assertSame(0, Review::count());
    }

    public function test_client_can_review_after_delivery_and_it_starts_pending(): void
    {
        $serviceRequest = $this->serviceRequest();

        $this->post(route('services.review', $serviceRequest->token), $this->reviewPayload())
            ->assertRedirect(route('services.show', $serviceRequest->token));

        $review = Review::firstOrFail();
        $this->assertSame('pending', $review->status);
        $this->assertSame(5, $review->rating);
    }

    public function test_only_one_review_per_request(): void
    {
        $serviceRequest = $this->serviceRequest();

        $this->post(route('services.review', $serviceRequest->token), $this->reviewPayload());
        $this->post(route('services.review', $serviceRequest->token), $this->reviewPayload(['name' => 'Again']))
            ->assertStatus(422);

        $this->assertSame(1, Review::count());
    }

    public function test_invalid_rating_is_rejected(): void
    {
        $serviceRequest = $this->serviceRequest();

        $this->post(route('services.review', $serviceRequest->token), $this->reviewPayload(['rating' => 9]))
            ->assertSessionHasErrors('rating');

        $this->assertSame(0, Review::count());
    }

    public function test_home_page_shows_only_approved_reviews(): void
    {
        Review::create(['service_request_id' => $this->serviceRequest()->id, 'name' => 'Approved', 'rating' => 5, 'comment' => 'Great work, highly recommended.', 'status' => 'approved']);
        Review::create(['service_request_id' => $this->serviceRequest()->id, 'name' => 'Pending', 'rating' => 1, 'comment' => 'Waiting for approval here.', 'status' => 'pending']);
        Review::create(['service_request_id' => $this->serviceRequest()->id, 'name' => 'Hidden', 'rating' => 1, 'comment' => 'This one was hidden away.', 'status' => 'hidden']);

        $this->get('/')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Welcome')
                ->has('reviews', 1)
                ->where('reviews.0.name', 'Approved'));
    }

    public function test_admin_can_approve_and_hide_reviews(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $review = Review::create(['service_request_id' => $this->serviceRequest()->id, 'name' => 'Maria', 'rating' => 5, 'comment' => 'Great work, highly recommended.']);

        $this->actingAs($admin)->post("/admin/reviews/{$review->id}/approve")->assertRedirect();
        $this->assertSame('approved', $review->fresh()->status);

        $this->actingAs($admin)->post("/admin/reviews/{$review->id}/hide")->assertRedirect();
        $this->assertSame('hidden', $review->fresh()->status);
    }

    public function test_non_admin_cannot_manage_reviews(): void
    {
        $customer = User::factory()->create(['is_admin' => false]);
        $review = Review::create(['service_request_id' => $this->serviceRequest()->id, 'name' => 'Maria', 'rating' => 5, 'comment' => 'Great work, highly recommended.']);

        $this->actingAs($customer)->get('/admin/reviews')->assertForbidden();
        $this->actingAs($customer)->post("/admin/reviews/{$review->id}/approve")->assertForbidden();
        $this->assertSame('pending', $review->fresh()->status);
    }
}
