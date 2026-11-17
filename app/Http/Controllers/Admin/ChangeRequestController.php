<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChangeRequest;
use App\Support\ChangeRequestNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ChangeRequestController extends Controller
{
    public function index(): Response
    {
        $changeRequests = ChangeRequest::with(['portfolio', 'user'])
            ->orderByRaw('case when admin_read_at is null or admin_read_at < last_message_at then 0 else 1 end')
            ->latest('last_message_at')
            ->get()
            ->map(fn (ChangeRequest $cr) => [
                'id' => $cr->id,
                'title' => $cr->title,
                'client' => $cr->user->name,
                'email' => $cr->user->email,
                'portfolio' => $cr->portfolio->data['name'] ?? 'Untitled portfolio',
                'status' => $cr->status,
                'status_label' => $cr->statusLabel(),
                'type_label' => $cr->typeLabel(),
                'quote_amount' => $cr->quote_amount,
                'unread' => $this->isUnread($cr),
                'last_message_at' => $cr->last_message_at?->toDayDateTimeString(),
            ])
            ->values();

        return Inertia::render('Admin/ChangeRequests/Index', [
            'changeRequests' => $changeRequests,
        ]);
    }

    public function show(ChangeRequest $changeRequest): Response
    {
        $changeRequest->update(['admin_read_at' => now()]);
        $changeRequest->load(['portfolio', 'user']);

        $messages = $changeRequest->messages()
            ->with('user')
            ->oldest('id')
            ->get()
            ->map(fn ($m) => [
                'id' => $m->id,
                'body' => $m->body,
                'is_admin' => $m->is_admin,
                'author' => $m->is_admin ? 'Admin' : ($m->user->name ?? 'Customer'),
                'image_urls' => $m->image_urls ?? [],
                'created_at' => $m->created_at->toDayDateTimeString(),
            ])
            ->values();

        return Inertia::render('Admin/ChangeRequests/Show', [
            'changeRequest' => [
                'id' => $changeRequest->id,
                'title' => $changeRequest->title,
                'client' => $changeRequest->user->name,
                'email' => $changeRequest->user->email,
                'portfolio' => $changeRequest->portfolio->data['name'] ?? 'Untitled portfolio',
                'preview_url' => route('admin.portfolios.preview', $changeRequest->portfolio_id),
                'status' => $changeRequest->status,
                'status_label' => $changeRequest->statusLabel(),
                'type' => $changeRequest->type,
                'type_label' => $changeRequest->typeLabel(),
                'quote_amount' => $changeRequest->quote_amount,
                'quote_note' => $changeRequest->quote_note,
                'messages' => $messages,
            ],
            'statuses' => ChangeRequest::STATUSES,
            'types' => ChangeRequest::TYPES,
        ]);
    }

    public function reply(Request $request, ChangeRequest $changeRequest): RedirectResponse
    {
        $validated = $request->validate([
            'body' => ['required', 'string', 'min:2', 'max:2000'],
            'images' => ['nullable', 'array', 'max:4'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:3072', 'dimensions:max_width=5000,max_height=5000'],
        ]);

        $urls = $this->storeImages($request);

        DB::transaction(function () use ($request, $changeRequest, $validated, $urls) {
            $changeRequest->messages()->create([
                'user_id' => $request->user()->id,
                'is_admin' => true,
                'body' => $validated['body'],
                'image_urls' => $urls ?: null,
            ]);

            $changeRequest->update([
                'last_message_at' => now(),
                'admin_read_at' => now(),
            ]);
        });

        ChangeRequestNotifier::toCustomer(
            $changeRequest,
            'New reply on your change request',
            "There is a new reply on \"{$changeRequest->title}\":\n\n"
            .Str::limit($validated['body'], 300)
            ."\n\nOpen the thread here:\n".route('change-requests.show', $changeRequest)
        );

        return back();
    }

    public function update(Request $request, ChangeRequest $changeRequest): RedirectResponse
    {
        $validated = $request->validate([
            'type' => ['required', Rule::in(array_keys(ChangeRequest::TYPES))],
            'status' => ['required', Rule::in(array_keys(ChangeRequest::STATUSES))],
            'quote_amount' => ['required_if:type,additional', 'nullable', 'numeric', 'min:1', 'max:1000000'],
            'quote_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $type = $validated['type'];
        $status = $validated['status'];
        $amount = $type === 'additional' ? (float) $validated['quote_amount'] : null;
        $note = $validated['quote_note'] ?? null;

        if ($type === 'additional' && $status === 'pending') {
            $status = 'quoted';
        }

        $oldAmount = $changeRequest->quote_amount === null ? null : (float) $changeRequest->quote_amount;

        $changed = $changeRequest->type !== $type
            || $changeRequest->status !== $status
            || $oldAmount !== $amount
            || $changeRequest->quote_note !== $note;

        if (! $changed) {
            return back();
        }

        $changeRequest->update([
            'type' => $type,
            'status' => $status,
            'quote_amount' => $amount,
            'quote_note' => $note,
            'last_message_at' => now(),
            'admin_read_at' => now(),
        ]);

        $body = "Your change request \"{$changeRequest->title}\" was updated.\n\n"
            ."Status: {$changeRequest->statusLabel()}\n"
            ."Type: {$changeRequest->typeLabel()}\n";

        if ($amount !== null) {
            $body .= 'Quote: PHP '.number_format($amount, 2)."\n";
        }

        if ($note) {
            $body .= "\nNote: {$note}\n";
        }

        $body .= "\nSee the details here:\n".route('change-requests.show', $changeRequest);

        ChangeRequestNotifier::toCustomer($changeRequest, 'Update on your change request', $body);

        return back();
    }

    private function isUnread(ChangeRequest $changeRequest): bool
    {
        if (! $changeRequest->last_message_at) {
            return false;
        }

        return $changeRequest->admin_read_at === null
            || $changeRequest->admin_read_at->lt($changeRequest->last_message_at);
    }

    private function storeImages(Request $request): array
    {
        $urls = [];

        foreach ($request->file('images', []) as $file) {
            $path = Storage::disk('images')->putFile('change-requests', $file, 'public');

            if ($path) {
                $urls[] = Storage::disk('images')->url($path);
            }
        }

        return $urls;
    }
}
