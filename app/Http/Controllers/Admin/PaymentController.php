<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Support\Facades\Storage;
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
