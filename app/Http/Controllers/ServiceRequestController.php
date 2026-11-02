<?php

namespace App\Http\Controllers;

use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ServiceRequestController extends Controller
{
    public function create(Request $request): Response
    {
        $type = $request->query('type');

        return Inertia::render('Services/Create', [
            'types' => ServiceRequest::TYPES,
            'selectedType' => is_string($type) && array_key_exists($type, ServiceRequest::TYPES) ? $type : 'thesis_build',
            'email' => (string) $request->query('email', ''),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'type' => ['required', Rule::in(array_keys(ServiceRequest::TYPES))],
            'email' => ['required', 'email', 'max:150'],
            'details' => ['required', 'string', 'min:10', 'max:5000'],
            'attachment' => ['nullable', 'file', 'max:10240', 'mimes:pdf,zip,doc,docx,txt,png,jpg,jpeg'],
        ]);

        $path = null;

        if ($request->hasFile('attachment')) {
            $stored = Storage::disk('s3')->putFile('service-requests', $request->file('attachment'));
            $path = $stored ?: null;
        }

        $serviceRequest = ServiceRequest::create([
            'type' => $validated['type'],
            'email' => $validated['email'],
            'details' => $validated['details'],
            'attachment_path' => $path,
            'token' => Str::random(40),
        ]);

        $url = route('services.show', $serviceRequest->token);

        try {
            Mail::raw(
                "We received your request ({$serviceRequest->typeLabel()}).\n\nTrack its status here:\n{$url}\n\nKeep this link private. Anyone with it can view your request.",
                fn ($message) => $message->to($serviceRequest->email)->subject('We received your request')
            );

            $admins = User::where('is_admin', true)->pluck('email')->all();

            if ($admins) {
                Mail::raw(
                    "New request: {$serviceRequest->typeLabel()}\nFrom: {$serviceRequest->email}\n\n{$serviceRequest->details}",
                    fn ($message) => $message->to($admins)->subject('New service request')
                );
            }
        } catch (\Throwable $e) {
            report($e);
        }

        return redirect()->route('services.show', $serviceRequest->token);
    }

    public function pay(Request $request, string $token): RedirectResponse
    {
        $serviceRequest = ServiceRequest::where('token', $token)->firstOrFail();

        // Only a quoted request can receive a payment proof
        abort_unless($serviceRequest->status === 'quoted', 422);

        $validated = $request->validate([
            'reference_no' => ['required', 'string', 'max:50'],
            'proof' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ]);

        $path = Storage::disk('s3')->putFile('service-requests/payments', $validated['proof']);

        if (! $path) {
            return back()->withErrors(['proof' => 'The proof could not be uploaded. Please try again.']);
        }

        $oldPath = $serviceRequest->payment_proof_path;

        $serviceRequest->update([
            'payment_reference' => $validated['reference_no'],
            'payment_proof_path' => $path,
            'payment_submitted_at' => now(),
            'status' => 'payment_submitted',
            'admin_note' => null,
        ]);

        if ($oldPath) {
            try {
                Storage::disk('s3')->delete($oldPath);
            } catch (\Throwable $e) {
                report($e);
            }
        }

        try {
            $admins = User::where('is_admin', true)->pluck('email')->all();

            if ($admins) {
                Mail::raw(
                    "A client submitted payment proof.\n\nRequest: {$serviceRequest->typeLabel()}\nFrom: {$serviceRequest->email}\nReference: {$serviceRequest->payment_reference}\n\nReview it here:\n".route('admin.requests.show', $serviceRequest->id),
                    fn ($message) => $message->to($admins)->subject('Payment proof submitted')
                );
            }
        } catch (\Throwable $e) {
            report($e);
        }

        return redirect()->route('services.show', $serviceRequest->token)->with('status', 'Payment submitted.');
    }

    public function show(string $token): Response
    {
        $serviceRequest = ServiceRequest::where('token', $token)->firstOrFail();

        $review = \App\Models\Review::where('service_request_id', $serviceRequest->id)->first();

        return Inertia::render('Services/Show', [
            'payment' => [
                'qr_url' => config('portfolio.qr_path'),
                'wallet_label' => config('portfolio.wallet_label'),
            ],
            'serviceRequest' => [
                'type_label' => $serviceRequest->typeLabel(),
                'status' => $serviceRequest->status,
                'status_label' => $serviceRequest->statusLabel(),
                'details' => $serviceRequest->details,
                'quote_amount' => $serviceRequest->quote_amount,
                'quote_note' => $serviceRequest->quote_note,
                'can_pay' => $serviceRequest->status === 'quoted',
                'payment_reference' => $serviceRequest->payment_reference,
                'payment_note' => $serviceRequest->status === 'quoted' ? $serviceRequest->admin_note : null,
                'delivery_url' => $serviceRequest->status === 'delivered' ? $serviceRequest->delivery_url : null,
                'created_at' => $serviceRequest->created_at->toDayDateTimeString(),
                'can_review' => $serviceRequest->status === 'delivered' && ! $review,
                'review' => $review ? ['rating' => $review->rating, 'comment' => $review->comment, 'status' => $review->status] : null,
            ],
        ]);
    }
}
