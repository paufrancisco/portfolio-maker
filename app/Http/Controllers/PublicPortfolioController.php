<?php

namespace App\Http\Controllers;

use App\Models\Portfolio;
use App\Models\Template;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class PublicPortfolioController extends Controller
{
    public function show(Request $request, string $slug): Response
    {
        $portfolio = Portfolio::where('slug', $slug)->where('status', 'published')->firstOrFail();
        $this->countView($request, $portfolio);
        $template = Template::where('key', $portfolio->template_key)->first()
            ?? Template::where('key', 'professional')->first();

        $data = $this->publicData($portfolio);
        View::share('og', $this->shareTags($portfolio, $data));

        return Inertia::render('Portfolios/Preview', [
            'portfolio' => [
                'template_key' => $portfolio->template_key,
                'data' => $data,
            ],
            'theme' => array_merge($template?->theme_options ?? [], array_filter($data['theme'] ?? [])),
            'watermark' => false,
        ]);
    }

    public function resume(string $slug): RedirectResponse
    {
        $portfolio = Portfolio::where('slug', $slug)->where('status', 'published')->firstOrFail();

        $live = $portfolio->published_data ?? $portfolio->data ?? [];
        abort_unless(($live['show_resume'] ?? false) && $portfolio->resume_path, 404);

        try {
            $url = Storage::disk('s3')->temporaryUrl($portfolio->resume_path, now()->addMinutes(5));
        } catch (\Throwable $e) {
            report($e);
            abort(404);
        }

        return redirect()->away($url);
    }

    // Count one view per browser per 6 hours. Skips the owner and bots. No IP is stored.
    private function countView(Request $request, Portfolio $portfolio): void
    {
        if ($request->user()?->id === $portfolio->user_id) {
            return;
        }

        $agent = (string) $request->userAgent();
        if ($agent === '' || preg_match('/bot|crawl|spider|slurp|preview|facebookexternalhit|whatsapp|telegram|curl|wget/i', $agent)) {
            return;
        }

        $cookie = 'pv_'.$portfolio->id;
        if ($request->cookie($cookie)) {
            return;
        }

        DB::table('portfolios')->where('id', $portfolio->id)->increment('view_count');
        Cookie::queue($cookie, '1', 360);
    }

    // Link preview tags (Facebook, Messenger, WhatsApp, LinkedIn). Built from published data only.
    private function shareTags(Portfolio $portfolio, array $data): array
    {
        $name = trim((string) ($data['name'] ?? ''));
        $headline = trim((string) ($data['headline'] ?? ''));
        $about = trim((string) (($data['intro'] ?? '') ?: ($data['about'] ?? '')));
        $photo = (string) ($data['photo_url'] ?? '');

        return [
            'site' => config('app.name'),
            'title' => $name !== '' ? ($headline !== '' ? "$name | $headline" : $name) : config('app.name'),
            'description' => Str::limit(preg_replace('/\s+/', ' ', $about), 160) ?: 'Portfolio on '.config('app.name'),
            'url' => route('portfolios.public', $portfolio->slug),
            'image' => str_starts_with($photo, 'https://') ? $photo : null,
        ];
    }

    private function publicData(Portfolio $portfolio): array
    {
        $data = $portfolio->published_data ?? $portfolio->data ?? [];
        $show = ($data['show_resume'] ?? false) && $portfolio->resume_path;

        unset($data['show_resume'], $data['imported_from_resume'], $data['resume_url']);

        if ($show) {
            $data['resume_url'] = route('portfolios.resume', $portfolio->slug);
        }

        return $data;
    }
}
