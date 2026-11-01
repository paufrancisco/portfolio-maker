<?php

namespace App\Http\Controllers;

use App\Models\Portfolio;
use App\Models\Template;
use Inertia\Inertia;
use Inertia\Response;

class PublicPortfolioController extends Controller
{
    public function show(string $slug): Response
    {
        $portfolio = Portfolio::where('slug', $slug)->where('status', 'published')->firstOrFail();
        $template = Template::where('key', $portfolio->template_key)->first()
            ?? Template::where('key', 'professional')->first();

        return Inertia::render('Portfolios/Preview', [
            'portfolio' => $portfolio->only(['template_key', 'data']),
            'theme' => $template?->theme_options ?? [],
            'watermark' => false,
        ]);
    }
}
