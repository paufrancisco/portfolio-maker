<?php

namespace App\Http\Controllers;

use App\Models\Portfolio;
use App\Models\Template;
use App\Services\ResumeParser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
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

        try {
            $parsed = app(ResumeParser::class)->parse($path);
        } catch (\Throwable $e) {
            report($e);
            $parsed = [];
        }

        $portfolio = Portfolio::create([
            'user_id' => $request->user()->id,
            'template_key' => 'professional',
            'data' => [
                'name' => $parsed['name'] ?? null,
                'email' => $parsed['email'] ?? null,
                'phone' => $parsed['phone'] ?? null,
                'headline' => $parsed['headline'],
                'about' => $parsed['about'],
                'skills' => $parsed['skills'],
                'experience' => $parsed['experience'],
                'projects' => $parsed['projects'],
                'certifications' => $parsed['certifications'],
                'education' => $parsed['education'],
                'linkedin' => $parsed['linkedin'],
                'github' => $parsed['github'],
                'imported_from_resume' => true,
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

    public function preview(Request $request, Portfolio $portfolio): Response
    {
        abort_unless($portfolio->user_id === $request->user()->id, 403);

        $template = Template::where('key', $portfolio->template_key)->firstOrFail();

        return Inertia::render('Portfolios/Preview', [
            'portfolio' => [
                'id' => $portfolio->id,
                'template_key' => $portfolio->template_key,
                'status' => $portfolio->status,
                'data' => array_merge($portfolio->data ?? [], ($portfolio->data['show_resume'] ?? false) && $portfolio->resume_path
                    ? ['resume_url' => '#resume-preview']
                    : []),
            ],
            'theme' => $template->theme_options,
            'watermark' => $portfolio->status !== 'published',
        ]);
    }

    public function update(Request $request, Portfolio $portfolio): RedirectResponse
    {
        abort_unless($portfolio->user_id === $request->user()->id, 403);

        $validated = $request->validate([
            'template_key' => ['required', 'exists:templates,key'],
            ...$this->profileRules(),
        ]);

        $portfolio->update([
            'template_key' => $validated['template_key'],
            'data' => $this->buildData($portfolio, $validated['profile']),
        ]);

        return back()->with('status', 'Saved.');
    }

    public function uploadImage(Request $request, Portfolio $portfolio): JsonResponse
    {
        abort_unless($portfolio->user_id === $request->user()->id, 403);

        $request->validate([
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:max_width=4000,max_height=4000'],
        ]);

        $path = Storage::disk('images')->putFile(
            'portfolios/'.$portfolio->id,
            $request->file('image'),
            'public'
        );

        if (! $path) {
            return response()->json(['message' => 'Upload failed. Please try again.'], 500);
        }

        return response()->json(['url' => Storage::disk('images')->url($path)]);
    }

    public function requestUpdate(Request $request, Portfolio $portfolio): RedirectResponse
    {
        abort_unless($portfolio->user_id === $request->user()->id, 403);
        abort_unless($portfolio->status === 'published', 422);

        $portfolio->update(['update_requested_at' => now()]);

        return back()->with('status', 'Update requested. We will review and publish your changes.');
    }

    private const SECTION_FIELDS = [
        'experience' => ['company', 'position', 'type', 'start', 'end', 'description'],
        'projects' => ['title', 'role', 'description', 'technologies', 'repo_url', 'demo_url', 'image_url'],
        'certifications' => ['title', 'issuer', 'date', 'description'],
        'education' => ['school', 'degree', 'year', 'achievements'],
    ];

    private function profileRules(): array
    {
        $rules = [
            'profile.name' => ['required', 'string', 'max:100'],
            'profile.email' => ['nullable', 'email', 'max:150'],
            'profile.phone' => ['nullable', 'string', 'max:40'],
            'profile.headline' => ['nullable', 'string', 'max:150'],
            'profile.intro' => ['nullable', 'string', 'max:600'],
            'profile.about' => ['nullable', 'string', 'max:2000'],
            'profile.skills' => ['nullable', 'string', 'max:1500'],
            'profile.linkedin' => ['nullable', 'url:http,https', 'max:255'],
            'profile.github' => ['nullable', 'url:http,https', 'max:255'],
            'profile.website' => ['nullable', 'url:http,https', 'max:255'],
            'profile.photo_url' => ['nullable', 'url:https', 'max:500'],
            'profile.show_resume' => ['nullable', 'boolean'],
        ];

        foreach (self::SECTION_FIELDS as $section => $fields) {
            $rules["profile.$section"] = ['nullable', 'array', 'max:20'];

            foreach ($fields as $field) {
                $rules["profile.$section.*.$field"] = str_ends_with($field, '_url')
                    ? ['nullable', 'url:http,https', 'max:255']
                    : ['nullable', 'string', 'max:'.($field === 'description' || $field === 'achievements' ? 1500 : 200)];
            }
        }

        return $rules;
    }

    private function buildData(Portfolio $portfolio, array $profile): array
    {
        $profile['show_resume'] = (bool) ($profile['show_resume'] ?? false);

        $profile['skills'] = array_values(array_filter(
            array_map('trim', explode(',', $profile['skills'] ?? ''))
        ));

        foreach (self::SECTION_FIELDS as $section => $fields) {
            $rows = [];

            foreach ($profile[$section] ?? [] as $row) {
                $clean = [];
                foreach ($fields as $field) {
                    $clean[$field] = $row[$field] ?? '';
                }
                if (array_filter($clean, fn ($v) => $v !== '' && $v !== null)) {
                    $rows[] = $clean;
                }
            }

            $profile[$section] = $rows;
        }

        // Keep keys not managed by this form (e.g. photo_url) instead of wiping them.
        return array_merge($portfolio->data ?? [], $profile);
    }
}
