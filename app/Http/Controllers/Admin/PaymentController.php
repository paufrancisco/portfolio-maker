<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use App\Models\Portfolio;
use Inertia\Inertia;
use Inertia\Response;

class PaymentController extends Controller
{
    public function index(): Response
    {
        $payments = Payment::with('portfolio.user')
            ->where('status', 'pending')
            ->latest('id')
            ->get()
            ->map(fn (Payment $payment) => [
                'id' => $payment->id,
                'reference_no' => $payment->reference_no,
                'amount' => $payment->amount,
                'created_at' => $payment->created_at->toDayDateTimeString(),
                'template_key' => $payment->portfolio->template_key,
                'client_name' => $payment->portfolio->data['name'] ?? $payment->portfolio->user->name,
                'client_email' => $payment->portfolio->user->email,
                'proof_url' => $this->proofUrl($payment->proof_path),
            ]);

        return Inertia::render('Admin/Payments/Index', [
            'payments' => $payments,
        ]);
    }

    public function approve(Payment $payment): RedirectResponse
    {
        abort_unless($payment->status === 'pending', 422);

        $portfolio = $payment->portfolio()->with('user')->firstOrFail();

        DB::transaction(function () use ($payment, $portfolio) {
            $payment->update(['status' => 'paid', 'reviewed_at' => now()]);
            $portfolio->update([
                'slug' => $portfolio->slug ?? $this->makeSlug($portfolio),
                'status' => 'published',
                'published_data' => $portfolio->data,
                'published_at' => now(),
            ]);
        });

        $url = route('portfolios.public', $portfolio->fresh()->slug);

        try {
            Mail::raw(
                "Your payment was received and your portfolio is now live:\n\n{$url}\n\nThank you!",
                fn ($message) => $message->to($portfolio->user->email)->subject('Your portfolio is live')
            );
        } catch (\Throwable $e) {
            report($e);
        }

        return redirect()->route('admin.payments.index');
    }

    public function reject(Request $request, Payment $payment): RedirectResponse
    {
        abort_unless($payment->status === 'pending', 422);

        $validated = $request->validate([
            'admin_note' => ['required', 'string', 'max:500'],
        ]);

        $payment->update([
            'status' => 'rejected',
            'admin_note' => $validated['admin_note'],
            'reviewed_at' => now(),
        ]);

        $user = $payment->portfolio()->with('user')->firstOrFail()->user;
        $url = route('payments.create', $payment->portfolio_id);

        try {
            Mail::raw(
                "Your payment could not be verified.\n\nReason: {$validated['admin_note']}\n\nPlease upload a new proof of payment here:\n{$url}",
                fn ($message) => $message->to($user->email)->subject('Payment needs your attention')
            );
        } catch (\Throwable $e) {
            report($e);
        }

        return redirect()->route('admin.payments.index');
    }

    private function makeSlug(Portfolio $portfolio): string
    {
        $base = Str::slug($portfolio->data['name'] ?? $portfolio->user->name) ?: 'portfolio';
        $slug = $base;
        $i = 2;

        while (Portfolio::where('slug', $slug)->where('id', '!=', $portfolio->id)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }

    private function proofUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        try {
            return Storage::disk('s3')->temporaryUrl($path, now()->addMinutes(10));
        } catch (\Throwable $e) {
            report($e);

            return null;
        }
    }
}
