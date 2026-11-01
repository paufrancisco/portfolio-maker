<?php

namespace App\Http\Controllers;

use App\Models\Portfolio;
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

        Portfolio::create([
            'user_id' => $request->user()->id,
            'template_key' => 'professional',
            'data' => [],
            'resume_path' => $path,
            'status' => 'draft',
        ]);

        return redirect()->route('dashboard')->with('status', 'Resume uploaded.');
    }
}
