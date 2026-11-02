<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Portfolio;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('q', ''));

        $portfolios = Portfolio::with('user')
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($q) use ($search) {
                    $q->where('slug', 'ilike', "%{$search}%")
                        ->orWhere('data->name', 'ilike', "%{$search}%")
                        ->orWhereHas('user', fn ($u) => $u->where('email', 'ilike', "%{$search}%")
                            ->orWhere('name', 'ilike', "%{$search}%"));
                });
            })
            ->latest('id')
            ->limit(100)
            ->get()
            ->map(fn (Portfolio $p) => [
                'id' => $p->id,
                'name' => $p->data['name'] ?? $p->user->name,
                'email' => $p->user->email,
                'template_key' => $p->template_key,
                'status' => $p->status,
                'slug' => $p->slug,
                'published_at' => $p->published_at?->toDayDateTimeString(),
                'preview_url' => route('admin.portfolios.preview', $p->id),
            ]);

        $history = Payment::with('portfolio.user')
            ->whereIn('status', ['paid', 'rejected'])
            ->latest('id')
            ->limit(50)
            ->get()
            ->map(fn (Payment $p) => [
                'id' => $p->id,
                'client' => $p->portfolio->data['name'] ?? $p->portfolio->user->name,
                'reference_no' => $p->reference_no,
                'amount' => $p->amount,
                'status' => $p->status,
                'admin_note' => $p->admin_note,
                'reviewed_at' => $p->reviewed_at?->toDayDateTimeString(),
            ]);

        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'customers' => User::where('is_admin', false)->count(),
                'portfolios' => Portfolio::count(),
                'published' => Portfolio::where('status', 'published')->count(),
                'pending' => Payment::where('status', 'pending')->count(),
                'approved' => Payment::where('status', 'paid')->count(),
                'rejected' => Payment::where('status', 'rejected')->count(),
                'revenue' => (float) Payment::where('status', 'paid')->sum('amount'),
            ],
            'portfolios' => $portfolios,
            'history' => $history,
            'q' => $search,
        ]);
    }
}
