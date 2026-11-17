<?php

namespace App\Http\Controllers;

use App\Models\ChangeRequest;
use App\Models\Portfolio;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ChangeRequestController extends Controller
{
    public function index(Request $request): Response
    {
        $userId = $request->user()->id;

        $changeRequests = ChangeRequest::with('portfolio')
            ->where('user_id', $userId)
            ->latest('last_message_at')
            ->get()
            ->map(fn ($cr) => [
                'id' => $cr->id,
                'title' => $cr->title,
                'portfolio' => $cr->portfolio->data['name'] ?? 'Untitled portfolio',
                'status' => $cr->status,
                'status_label' => $cr->statusLabel(),
                'type_label' => $cr->typeLabel(),
                'quote_amount' => $cr->quote_amount,
                'unread' => $this->isUnread($cr),
                'last_message_at' => $cr->last_message_at?->toIso8601String(),
            ])
            ->values();

        $portfolios = Portfolio::where('user_id', $userId)
            ->latest('id')
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->data['name'] ?? 'Untitled portfolio',
            ])
            ->values();

        return Inertia::render('ChangeRequests/Index', [
            'changeRequests' => $changeRequests,
            'portfolios' => $portfolios,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $userId = $request->user()->id;

        $validated = $request->validate([
            'portfolio_id' => ['required', 'integer', Rule::exists('portfolios', 'id')->where('user_id', $userId)],
            'title' => ['required', 'string', 'max:120'],
            'body' => ['required', 'string', 'min:10', 'max:2000'],
            'images' => ['nullable', 'array', 'max:4'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:3072', 'dimensions:max_width=5000,max_height=5000'],
        ]);

        $urls = $this->storeImages($request);

        $changeRequest = DB::transaction(function () use ($validated, $userId, $urls) {
            $changeRequest = ChangeRequest::create([
                'portfolio_id' => $validated['portfolio_id'],
                'user_id' => $userId,
                'title' => $validated['title'],
                'status' => 'pending',
                'last_message_at' => now(),
                'customer_read_at' => now(),
            ]);

            $changeRequest->messages()->create([
                'user_id' => $userId,
                'is_admin' => false,
                'body' => $validated['body'],
                'image_urls' => $urls ?: null,
            ]);

            return $changeRequest;
        });

        return redirect()->route('change-requests.show', $changeRequest);
    }

    public function show(Request $request, ChangeRequest $changeRequest): Response
    {
        $this->authorizeOwner($request, $changeRequest);

        $changeRequest->update(['customer_read_at' => now()]);

        $messages = $changeRequest->messages()
            ->with('user')
            ->oldest('id')
            ->get()
            ->map(fn ($m) => [
                'id' => $m->id,
                'body' => $m->body,
                'is_admin' => $m->is_admin,
                'author' => $m->is_admin ? 'Admin' : ($m->user->name ?? 'You'),
                'image_urls' => $m->image_urls ?? [],
                'created_at' => $m->created_at->toIso8601String(),
            ])
            ->values();

        return Inertia::render('ChangeRequests/Show', [
            'changeRequest' => [
                'id' => $changeRequest->id,
                'title' => $changeRequest->title,
                'portfolio' => $changeRequest->portfolio->data['name'] ?? 'Untitled portfolio',
                'status' => $changeRequest->status,
                'status_label' => $changeRequest->statusLabel(),
                'type' => $changeRequest->type,
                'type_label' => $changeRequest->typeLabel(),
                'quote_amount' => $changeRequest->quote_amount,
                'quote_note' => $changeRequest->quote_note,
                'payment_status' => $changeRequest->payment_status,
                'messages' => $messages,
            ],
        ]);
    }

    public function reply(Request $request, ChangeRequest $changeRequest): RedirectResponse
    {
        $this->authorizeOwner($request, $changeRequest);

        $validated = $request->validate([
            'body' => ['required', 'string', 'min:2', 'max:2000'],
            'images' => ['nullable', 'array', 'max:4'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:3072', 'dimensions:max_width=5000,max_height=5000'],
        ]);

        $urls = $this->storeImages($request);

        DB::transaction(function () use ($request, $changeRequest, $validated, $urls) {
            $changeRequest->messages()->create([
                'user_id' => $request->user()->id,
                'is_admin' => false,
                'body' => $validated['body'],
                'image_urls' => $urls ?: null,
            ]);

            $changeRequest->update([
                'last_message_at' => now(),
                'customer_read_at' => now(),
            ]);
        });

        return back();
    }

    private function authorizeOwner(Request $request, ChangeRequest $changeRequest): void
    {
        abort_unless($changeRequest->user_id === $request->user()->id, 403);
    }

    private function isUnread(ChangeRequest $changeRequest): bool
    {
        if (! $changeRequest->last_message_at) {
            return false;
        }

        return $changeRequest->customer_read_at === null
            || $changeRequest->customer_read_at->lt($changeRequest->last_message_at);
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
