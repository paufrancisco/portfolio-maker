<?php

namespace App\Http\Middleware;

use App\Models\ChangeRequest;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user(),
            ],
            'unreadChangeRequests' => fn () => $request->user()
                ? ChangeRequest::where('user_id', $request->user()->id)
                    ->whereNotNull('last_message_at')
                    ->where(function ($query) {
                        $query->whereNull('customer_read_at')
                            ->orWhereColumn('customer_read_at', '<', 'last_message_at');
                    })
                    ->count()
                : 0,
        ];
    }
}
