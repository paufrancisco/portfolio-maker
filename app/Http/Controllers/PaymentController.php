<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Portfolio;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class PaymentController extends Controller
{
    public function create(Request $request, Portfolio $portfolio): Response
    {
        abort_unless($portfolio->user_id === $request->user()->id, 403);

        return Inertia::render('Payments/Create', [
            'portfolio' => $portfolio->only(['id', 'status']),
            'amount' => config('portfolio.price'),
            'qrUrl' => config('portfolio.qr_path'),
            'walletLabel' => config('portfolio.wallet_label'),
            'payment' => $portfolio->payments()->latest('id')->first(['id', 'reference_no', 'status', 'amount']),
        ]);
    }

    public function store(Request $request, Portfolio $portfolio): RedirectResponse
    {
        abort_unless($portfolio->user_id === $request->user()->id, 403);

        if ($portfolio->payments()->where('status', 'pending')->exists()) {
            return back()->withErrors(['reference_no' => 'You already have a payment waiting for review.']);
        }

        $validated = $request->validate([
            'reference_no' => ['required', 'string', 'max:50'],
            'proof' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ]);

        $path = Storage::disk('s3')->putFile(
            'payments/'.$request->user()->id,
            $validated['proof']
        );

        Payment::create([
            'portfolio_id' => $portfolio->id,
            'reference_no' => $validated['reference_no'],
            'proof_path' => $path,
            'amount' => config('portfolio.price'),
            'status' => 'pending',
        ]);

        $portfolio->update(['status' => 'awaiting_payment']);

        return back()->with('status', 'Payment submitted.');
    }
}
