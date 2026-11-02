<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\Portfolio;
use App\Models\Template;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PortfolioFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Safety net: never run these tests against a real database.
        $this->assertSame('sqlite', config('database.default'), 'Tests must use SQLite.');

        $this->withoutVite();
        Storage::fake('s3');
        Storage::fake('images');
    }

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    private function customer(): User
    {
        return User::factory()->create(['is_admin' => false]);
    }

    private function portfolio(User $user, array $overrides = []): Portfolio
    {
        return Portfolio::create(array_merge([
            'user_id' => $user->id,
            'template_key' => 'professional',
            'data' => ['name' => 'Jane Doe', 'headline' => 'Draft headline'],
            'status' => 'awaiting_payment',
        ], $overrides));
    }

    private function pendingPayment(Portfolio $portfolio): Payment
    {
        return Payment::create([
            'portfolio_id' => $portfolio->id,
            'reference_no' => 'REF123',
            'proof_path' => 'payments/1/proof.png',
            'amount' => 500,
            'status' => 'pending',
        ]);
    }

    public function test_admin_can_approve_payment_and_portfolio_is_published(): void
    {
        $customer = $this->customer();
        $portfolio = $this->portfolio($customer);
        $payment = $this->pendingPayment($portfolio);

        $this->actingAs($this->admin())
            ->post(route('admin.payments.approve', $payment))
            ->assertRedirect(route('admin.payments.index'));

        $portfolio->refresh();
        $this->assertSame('paid', $payment->fresh()->status);
        $this->assertSame('published', $portfolio->status);
        $this->assertSame('jane-doe', $portfolio->slug);
        $this->assertSame('Draft headline', $portfolio->published_data['headline']);
    }

    public function test_non_admin_cannot_approve_or_reject_payments(): void
    {
        $customer = $this->customer();
        $payment = $this->pendingPayment($this->portfolio($customer));

        $this->actingAs($customer)->post(route('admin.payments.approve', $payment))->assertForbidden();
        $this->actingAs($customer)
            ->post(route('admin.payments.reject', $payment), ['admin_note' => 'x'])
            ->assertForbidden();

        $this->assertSame('pending', $payment->fresh()->status);
    }

    public function test_guest_cannot_approve_payments(): void
    {
        $payment = $this->pendingPayment($this->portfolio($this->customer()));

        $this->post(route('admin.payments.approve', $payment))->assertRedirect(route('login'));
        $this->assertSame('pending', $payment->fresh()->status);
    }

    public function test_reject_requires_a_note_and_does_not_publish(): void
    {
        $portfolio = $this->portfolio($this->customer());
        $payment = $this->pendingPayment($portfolio);
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.payments.reject', $payment), ['admin_note' => ''])
            ->assertSessionHasErrors('admin_note');
        $this->assertSame('pending', $payment->fresh()->status);

        $this->actingAs($admin)
            ->post(route('admin.payments.reject', $payment), ['admin_note' => 'Blurry receipt'])
            ->assertRedirect();

        $payment->refresh();
        $this->assertSame('rejected', $payment->status);
        $this->assertSame('Blurry receipt', $payment->admin_note);
        $this->assertNotSame('published', $portfolio->fresh()->status);
    }

    public function test_already_reviewed_payment_cannot_be_approved_again(): void
    {
        $payment = $this->pendingPayment($this->portfolio($this->customer()));
        $payment->update(['status' => 'rejected']);

        $this->actingAs($this->admin())
            ->post(route('admin.payments.approve', $payment))
            ->assertStatus(422);
    }

    public function test_customer_can_resubmit_after_rejection(): void
    {
        $customer = $this->customer();
        $portfolio = $this->portfolio($customer);
        $this->pendingPayment($portfolio)->update(['status' => 'rejected']);

        $this->actingAs($customer)
            ->post(route('payments.store', $portfolio), [
                'reference_no' => 'NEWREF',
                'proof' => \Illuminate\Http\UploadedFile::fake()->image('proof.png'),
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(1, $portfolio->payments()->where('status', 'pending')->count());
    }

    public function test_customer_cannot_submit_two_pending_payments(): void
    {
        $customer = $this->customer();
        $portfolio = $this->portfolio($customer);
        $this->pendingPayment($portfolio);

        $this->actingAs($customer)
            ->post(route('payments.store', $portfolio), [
                'reference_no' => 'AGAIN',
                'proof' => \Illuminate\Http\UploadedFile::fake()->image('proof.png'),
            ])
            ->assertSessionHasErrors('reference_no');
    }

    public function test_public_page_only_serves_published_portfolios(): void
    {
        $owner = $this->customer();
        $draft = $this->portfolio($owner, ['status' => 'draft', 'slug' => 'draft-person']);
        $pending = $this->portfolio($owner, ['status' => 'awaiting_payment', 'slug' => 'pending-person']);

        $this->get('/p/draft-person')->assertNotFound();
        $this->get('/p/pending-person')->assertNotFound();
        $this->get('/p/does-not-exist')->assertNotFound();
    }

    public function test_public_page_shows_published_data_not_the_draft(): void
    {
        $this->portfolio($this->customer(), [
            'status' => 'published',
            'slug' => 'jane-doe',
            'data' => ['name' => 'Jane Doe', 'headline' => 'Unpublished edit'],
            'published_data' => ['name' => 'Jane Doe', 'headline' => 'Live headline'],
        ]);

        $this->get('/p/jane-doe')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Portfolios/Preview')
                ->where('portfolio.data.headline', 'Live headline')
                ->where('watermark', false));
    }

    public function test_public_page_does_not_leak_internal_fields(): void
    {
        $this->portfolio($this->customer(), [
            'status' => 'published',
            'slug' => 'jane-doe',
            'resume_path' => 'resumes/1/secret.pdf',
            'published_data' => ['name' => 'Jane Doe', 'imported_from_resume' => true, 'show_resume' => false],
        ]);

        $this->get('/p/jane-doe')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->missing('portfolio.data.imported_from_resume')
                ->missing('portfolio.data.show_resume')
                ->missing('portfolio.data.resume_url')
                ->missing('portfolio.id')
                ->missing('portfolio.status'));
    }

    public function test_resume_download_is_hidden_unless_owner_enabled_it(): void
    {
        $this->portfolio($this->customer(), [
            'status' => 'published',
            'slug' => 'jane-doe',
            'resume_path' => 'resumes/1/cv.pdf',
            'published_data' => ['name' => 'Jane Doe', 'show_resume' => false],
        ]);

        $this->get('/p/jane-doe/resume')->assertNotFound();
    }

    public function test_customer_cannot_view_or_edit_another_customers_portfolio(): void
    {
        $portfolio = $this->portfolio($this->customer());
        $intruder = $this->customer();

        $this->actingAs($intruder)->get(route('portfolios.edit', $portfolio))->assertForbidden();
        $this->actingAs($intruder)->get(route('portfolios.preview', $portfolio))->assertForbidden();
        $this->actingAs($intruder)->get(route('payments.create', $portfolio))->assertForbidden();
    }

    public function test_publish_update_copies_draft_to_live_and_clears_request(): void
    {
        $portfolio = $this->portfolio($this->customer(), [
            'status' => 'published',
            'slug' => 'jane-doe',
            'data' => ['name' => 'Jane Doe', 'headline' => 'New headline'],
            'published_data' => ['name' => 'Jane Doe', 'headline' => 'Old headline'],
            'update_requested_at' => now(),
        ]);

        $this->actingAs($this->admin())
            ->post(route('admin.portfolios.publish-update', $portfolio))
            ->assertRedirect(route('admin.payments.index'));

        $portfolio->refresh();
        $this->assertSame('New headline', $portfolio->published_data['headline']);
        $this->assertNull($portfolio->update_requested_at);
    }

    private function seedTemplates(): void
    {
        Template::create(['key' => 'professional', 'name' => 'Professional']);
        Template::create(['key' => 'pink', 'name' => 'Pink']);
    }

    public function test_update_ignores_template_change_on_published_portfolio(): void
    {
        $this->seedTemplates();
        $customer = $this->customer();
        $portfolio = $this->portfolio($customer, [
            'status' => 'published',
            'slug' => 'jane-doe',
            'published_data' => ['name' => 'Jane Doe'],
        ]);

        $this->actingAs($customer)
            ->put(route('portfolios.update', $portfolio), [
                'template_key' => 'pink',
                'profile' => ['name' => 'Jane Doe', 'headline' => 'Edited headline'],
            ])
            ->assertRedirect();

        $portfolio->refresh();
        $this->assertSame('professional', $portfolio->template_key);
        $this->assertSame('Edited headline', $portfolio->data['headline']);
    }

    public function test_update_changes_template_on_draft_portfolio(): void
    {
        $this->seedTemplates();
        $customer = $this->customer();
        $portfolio = $this->portfolio($customer);

        $this->actingAs($customer)
            ->put(route('portfolios.update', $portfolio), [
                'template_key' => 'pink',
                'profile' => ['name' => 'Jane Doe'],
            ])
            ->assertRedirect();

        $this->assertSame('pink', $portfolio->fresh()->template_key);
    }

    public function test_set_template_is_rejected_for_published_portfolio(): void
    {
        $this->seedTemplates();
        $customer = $this->customer();
        $portfolio = $this->portfolio($customer, [
            'status' => 'published',
            'slug' => 'jane-doe',
            'published_data' => ['name' => 'Jane Doe'],
        ]);

        $this->actingAs($customer)
            ->post(route('portfolios.template', $portfolio), ['template_key' => 'pink'])
            ->assertStatus(422);

        $this->assertSame('professional', $portfolio->fresh()->template_key);
    }
}
