<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ServiceRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ServiceRequestController extends Controller
{
    public function index(): Response
    {
        $requests = ServiceRequest::query()
            ->orderByRaw("case when status in ('received', 'payment_submitted') then 0 else 1 end")
            ->latest('id')
            ->get()
            ->map(fn (ServiceRequest $r) => [
                'id' => $r->id,
                'type_label' => $r->typeLabel(),
                'email' => $r->email,
                'status' => $r->status,
                'status_label' => $r->statusLabel(),
                'quote_amount' => $r->quote_amount,
                'created_at' => $r->created_at->toDayDateTimeString(),
            ]);

        return Inertia::render('Admin/Requests', ['requests' => $requests]);
    }

    public function show(ServiceRequest $serviceRequest): Response
    {
        return Inertia::render('Admin/RequestShow', [
            'serviceRequest' => [
                'id' => $serviceRequest->id,
                'type_label' => $serviceRequest->typeLabel(),
                'email' => $serviceRequest->email,
                'status' => $serviceRequest->status,
                'status_label' => $serviceRequest->statusLabel(),
                'details' => $serviceRequest->details,
                'quote_amount' => $serviceRequest->quote_amount,
                'quote_note' => $serviceRequest->quote_note,
                'delivery_url' => $serviceRequest->delivery_url,
                'admin_note' => $serviceRequest->admin_note,
                'created_at' => $serviceRequest->created_at->toDayDateTimeString(),
                'attachment_url' => $this->attachmentUrl($serviceRequest),
                'status_url' => route('services.show', $serviceRequest->token),
            ],
        ]);
    }

    public function quote(Request $request, ServiceRequest $serviceRequest): RedirectResponse
    {
        abort_unless(in_array($serviceRequest->status, ['received', 'quoted'], true), 422);

        $validated = $request->validate([
            'quote_amount' => ['required', 'numeric', 'min:1', 'max:1000000'],
            'quote_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $serviceRequest->update([
            'quote_amount' => $validated['quote_amount'],
            'quote_note' => $validated['quote_note'] ?? null,
            'status' => 'quoted',
        ]);

        $this->notify(
            $serviceRequest,
            'Your quote is ready',
            "We reviewed your request ({$serviceRequest->typeLabel()}).\n\nPrice: {$serviceRequest->quote_amount}\n"
            .($serviceRequest->quote_note ? "\nNote: {$serviceRequest->quote_note}\n" : '')
            ."\nSee the details and your status here:\n".route('services.show', $serviceRequest->token)
        );

        return back();
    }

    public function markPaid(ServiceRequest $serviceRequest): RedirectResponse
    {
        abort_unless(in_array($serviceRequest->status, ['quoted', 'payment_submitted'], true), 422);

        $serviceRequest->update(['status' => 'paid', 'paid_at' => now()]);

        $this->notify(
            $serviceRequest,
            'Payment received',
            "We received your payment for {$serviceRequest->typeLabel()}. We are working on it now.\n\n"
            .route('services.show', $serviceRequest->token)
        );

        return back();
    }

    public function deliver(Request $request, ServiceRequest $serviceRequest): RedirectResponse
    {
        abort_unless($serviceRequest->status === 'paid', 422);

        $validated = $request->validate([
            'delivery_url' => ['required', 'url:http,https', 'max:500'],
        ]);

        $serviceRequest->update([
            'delivery_url' => $validated['delivery_url'],
            'status' => 'delivered',
        ]);

        $this->notify(
            $serviceRequest,
            'Your request is ready',
            "Your {$serviceRequest->typeLabel()} is ready:\n\n{$serviceRequest->delivery_url}\n\n"
            ."You can also open it from your status page, where you can leave a review:\n"
            .route('services.show', $serviceRequest->token)
        );

        return back();
    }

    public function reject(Request $request, ServiceRequest $serviceRequest): RedirectResponse
    {
        abort_unless(in_array($serviceRequest->status, ['received', 'quoted'], true), 422);

        $validated = $request->validate([
            'admin_note' => ['required', 'string', 'max:1000'],
        ]);

        $serviceRequest->update(['status' => 'rejected', 'admin_note' => $validated['admin_note']]);

        $this->notify(
            $serviceRequest,
            'About your request',
            "We are not able to take your request ({$serviceRequest->typeLabel()}).\n\nReason: {$serviceRequest->admin_note}"
        );

        return back();
    }

    private function attachmentUrl(ServiceRequest $serviceRequest): ?string
    {
        if (! $serviceRequest->attachment_path) {
            return null;
        }

        try {
            return Storage::disk('s3')->temporaryUrl($serviceRequest->attachment_path, now()->addMinutes(15));
        } catch (\Throwable $e) {
            report($e);

            return null;
        }
    }

    private function notify(ServiceRequest $serviceRequest, string $subject, string $body): void
    {
        try {
            Mail::raw($body, fn ($message) => $message->to($serviceRequest->email)->subject($subject));
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
