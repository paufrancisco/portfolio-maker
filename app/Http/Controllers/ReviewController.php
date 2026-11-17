<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Models\ServiceRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ReviewController extends Controller
{
    public function store(Request $request, string $token): RedirectResponse
    {
        $serviceRequest = ServiceRequest::where('token', $token)->firstOrFail();

        // Only real, delivered work can be reviewed, and only once.
        abort_unless($serviceRequest->status === 'delivered', 403);
        abort_if(Review::where('service_request_id', $serviceRequest->id)->exists(), 422);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:60'],
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['required', 'string', 'min:10', 'max:1000'],
        ]);

        Review::create([
            'service_request_id' => $serviceRequest->id,
            'name' => $validated['name'],
            'rating' => $validated['rating'],
            'comment' => $validated['comment'],
            'status' => 'pending',
        ]);

        return redirect()->route('services.show', $serviceRequest->token);
    }

    public function storePublic(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:60'],
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['required', 'string', 'min:10', 'max:1000'],
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072', 'dimensions:max_width=5000,max_height=5000'],
        ]);

        $path = Storage::disk('images')->putFile('reviews', $request->file('photo'), 'public');

        if (! $path) {
            return back()->withErrors(['photo' => 'Upload failed. Please try again.']);
        }

        Review::create([
            'name' => $validated['name'],
            'rating' => $validated['rating'],
            'comment' => $validated['comment'],
            'photo_url' => Storage::disk('images')->url($path),
            'status' => 'pending',
        ]);

        return back();
    }
}
