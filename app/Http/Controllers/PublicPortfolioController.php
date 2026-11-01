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
        $template = Template::where('key', $portfolio->template_key)->firstOrFail();

        return Inertia::render('Portfolios/Preview', [
            'portfolio' => $portfolio->only(['id', 'template_key', 'data', 'status']),
            'theme' => $template->theme_options,
            'watermark' => false,
        ]);
    }
}
