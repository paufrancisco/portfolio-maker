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

    public function show(string $token): Response
    {
        $serviceRequest = ServiceRequest::where('token', $token)->firstOrFail();

        $review = \App\Models\Review::where('service_request_id', $serviceRequest->id)->first();

        return Inertia::render('Services/Show', [
            'serviceRequest' => [
                'type_label' => $serviceRequest->typeLabel(),
                'status' => $serviceRequest->status,
                'status_label' => $serviceRequest->statusLabel(),
                'details' => $serviceRequest->details,
                'quote_amount' => $serviceRequest->quote_amount,
                'quote_note' => $serviceRequest->quote_note,
                'delivery_url' => $serviceRequest->status === 'delivered' ? $serviceRequest->delivery_url : null,
                'created_at' => $serviceRequest->created_at->toDayDateTimeString(),
                'can_review' => $serviceRequest->status === 'delivered' && ! $review,
                'review' => $review ? ['rating' => $review->rating, 'comment' => $review->comment, 'status' => $review->status] : null,
            ],
        ]);
    }
}
