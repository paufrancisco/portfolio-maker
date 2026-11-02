<?php

namespace App\Http\Controllers;

use App\Models\Resume;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ResumeController extends Controller
{
    public function index(Request $request): Response
    {
        $resumes = Resume::where('user_id', $request->user()->id)
            ->latest('updated_at')
            ->get()
            ->map(fn (Resume $r) => [
                'id' => $r->id,
                'title' => $r->title,
                'template_label' => Resume::TEMPLATES[$r->template_key] ?? $r->template_key,
                'updated_at' => $r->updated_at->toDayDateTimeString(),
            ]);

        return Inertia::render('Resumes/Index', [
            'resumes' => $resumes,
            'templates' => Resume::TEMPLATES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'template_key' => ['nullable', Rule::in(array_keys(Resume::TEMPLATES))],
        ]);

        $data = Resume::blankData();
        $data['personal']['name'] = $request->user()->name;
        $data['personal']['email'] = $request->user()->email;

        $resume = Resume::create([
            'user_id' => $request->user()->id,
            'title' => 'Untitled resume',
            'template_key' => $validated['template_key'] ?? 'classic',
            'data' => $data,
        ]);

        return redirect()->route('resumes.edit', $resume->id);
    }

    public function edit(Request $request, Resume $resume): Response
    {
        $this->authorizeOwner($request, $resume);

        return Inertia::render('Resumes/Edit', [
            'resume' => [
                'id' => $resume->id,
                'title' => $resume->title,
                'template_key' => $resume->template_key,
                'content' => array_replace_recursive(Resume::blankData(), $resume->data ?? []),
            ],
            'templates' => Resume::TEMPLATES,
        ]);
    }

    public function update(Request $request, Resume $resume): RedirectResponse
    {
        $this->authorizeOwner($request, $resume);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:100'],
            'template_key' => ['required', Rule::in(array_keys(Resume::TEMPLATES))],
            'content' => ['required', 'array'],
            'content.personal' => ['nullable', 'array'],
            'content.personal.name' => ['nullable', 'string', 'max:100'],
            'content.personal.headline' => ['nullable', 'string', 'max:150'],
            'content.personal.email' => ['nullable', 'email', 'max:150'],
            'content.personal.phone' => ['nullable', 'string', 'max:40'],
            'content.personal.location' => ['nullable', 'string', 'max:100'],
            'content.personal.website' => ['nullable', 'string', 'max:200'],
            'content.personal.linkedin' => ['nullable', 'string', 'max:200'],
            'content.summary' => ['nullable', 'string', 'max:2000'],
            'content.experience' => ['nullable', 'array', 'max:15'],
            'content.experience.*.role' => ['nullable', 'string', 'max:100'],
            'content.experience.*.company' => ['nullable', 'string', 'max:100'],
            'content.experience.*.period' => ['nullable', 'string', 'max:100'],
            'content.experience.*.details' => ['nullable', 'string', 'max:2000'],
            'content.education' => ['nullable', 'array', 'max:10'],
            'content.education.*.school' => ['nullable', 'string', 'max:150'],
            'content.education.*.degree' => ['nullable', 'string', 'max:150'],
            'content.education.*.period' => ['nullable', 'string', 'max:100'],
            'content.education.*.details' => ['nullable', 'string', 'max:1000'],
            'content.skills' => ['nullable', 'array', 'max:40'],
            'content.skills.*' => ['string', 'max:50'],
            'content.projects' => ['nullable', 'array', 'max:10'],
            'content.projects.*.name' => ['nullable', 'string', 'max:100'],
            'content.projects.*.link' => ['nullable', 'string', 'max:200'],
            'content.projects.*.details' => ['nullable', 'string', 'max:1000'],
        ]);

        $content = $validated['content'];

        // Reminder: drop rows where every field is empty, so an accidental "Add" leaves nothing behind
        foreach (['experience', 'education', 'projects'] as $section) {
            $content[$section] = array_values(array_filter(
                $content[$section] ?? [],
                fn ($row) => collect($row)->filter(fn ($v) => $v !== null && $v !== '')->isNotEmpty()
            ));
        }

        $content['skills'] = array_values($content['skills'] ?? []);

        $resume->update([
            'title' => $validated['title'],
            'template_key' => $validated['template_key'],
            'data' => $content,
        ]);

        return back()->with('status', 'Resume saved.');
    }

    public function preview(Request $request, Resume $resume)
    {
        $this->authorizeOwner($request, $resume);

        return response()->view('resumes.'.$this->templateFor($resume), $this->viewData($resume));
    }

    public function destroy(Request $request, Resume $resume): RedirectResponse
    {
        $this->authorizeOwner($request, $resume);

        $resume->delete();

        return redirect()->route('resumes.index');
    }

    // Reminder: the PDF in phase 3 must use these same two helpers so it matches the preview
    private function templateFor(Resume $resume): string
    {
        return array_key_exists($resume->template_key, Resume::TEMPLATES) ? $resume->template_key : 'classic';
    }

    private function viewData(Resume $resume): array
    {
        return [
            'title' => $resume->title,
            'd' => array_replace_recursive(Resume::blankData(), $resume->data ?? []),
        ];
    }

    private function authorizeOwner(Request $request, Resume $resume): void
    {
        abort_unless($resume->user_id === $request->user()->id, 403);
    }
}
