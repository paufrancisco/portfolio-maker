<?php

namespace App\Http\Controllers;

use App\Models\Portfolio;
use App\Models\Template;
use App\Services\ResumeParser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class PortfolioController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Portfolios/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'resume' => ['required', 'file', 'mimes:pdf,doc,docx', 'max:5120'],
        ]);

        $path = Storage::disk('s3')->putFile(
            'resumes/'.$request->user()->id,
            $validated['resume']
        );

        $parsed = app(ResumeParser::class)->parse($path);

        $portfolio = Portfolio::create([
            'user_id' => $request->user()->id,
            'template_key' => 'professional',
            'data' => [
                'name' => $parsed['name'],
                'email' => $parsed['email'],
                'phone' => $parsed['phone'],
                'headline' => '',
                'about' => '',
            ],
            'resume_path' => $path,
            'status' => 'draft',
        ]);

        return redirect()->route('portfolios.edit', $portfolio);
    }

    public function edit(Request $request, Portfolio $portfolio): Response
    {
        abort_unless($portfolio->user_id === $request->user()->id, 403);

        return Inertia::render('Portfolios/Edit', [
            'portfolio' => $portfolio,
            'templates' => Template::where('is_active', true)->get(['key', 'name', 'theme_options']),
        ]);
    }

    public function update(Request $request, Portfolio $portfolio): RedirectResponse
    {
        abort_unless($portfolio->user_id === $request->user()->id, 403);

        $validated = $request->validate([
            'template_key' => ['required', 'exists:templates,key'],
            'profile.name' => ['required', 'string', 'max:100'],
            'profile.email' => ['nullable', 'email', 'max:150'],
            'profile.phone' => ['nullable', 'string', 'max:40'],
            'profile.headline' => ['nullable', 'string', 'max:150'],
            'profile.about' => ['nullable', 'string', 'max:2000'],
        ]);

        $portfolio->update([
            'template_key' => $validated['template_key'],
            'data' => $validated['profile'],
        ]);

        return back()->with('status', 'Saved.');
    }
}
