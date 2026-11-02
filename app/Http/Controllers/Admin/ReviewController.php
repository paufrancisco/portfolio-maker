<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ReviewController extends Controller
{
    public function index(): Response
    {
        $reviews = Review::with('serviceRequest')
            ->orderByRaw("case when status = 'pending' then 0 else 1 end")
            ->latest('id')
            ->get()
            ->map(fn (Review $review) => [
                'id' => $review->id,
                'name' => $review->name,
                'rating' => $review->rating,
                'comment' => $review->comment,
                'status' => $review->status,
                'service' => $review->serviceRequest->typeLabel(),
                'email' => $review->serviceRequest->email,
                'created_at' => $review->created_at->toDayDateTimeString(),
            ]);

        return Inertia::render('Admin/Reviews', ['reviews' => $reviews]);
    }

    public function approve(Review $review): RedirectResponse
    {
        $review->update(['status' => 'approved']);

        return back();
    }

    public function hide(Review $review): RedirectResponse
    {
        $review->update(['status' => 'hidden']);

        return back();
    }
}
