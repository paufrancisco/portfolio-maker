<?php

namespace App\Http\Controllers;

use App\Models\Portfolio;
use App\Models\Template;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class PublicPortfolioController extends Controller
{
    public function show(string $slug): Response
    {
        $portfolio = Portfolio::where('slug', $slug)->where('status', 'published')->firstOrFail();
        $template = Template::where('key', $portfolio->template_key)->first()
            ?? Template::where('key', 'professional')->first();

        $data = $this->publicData($portfolio);

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
