<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    $hasReviews = \Illuminate\Support\Facades\Schema::hasTable('reviews');

    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
        'reviews' => ! $hasReviews ? [] : \App\Models\Review::with('serviceRequest')->where('status', 'approved')->latest('id')->take(12)->get()
            ->map(fn ($r) => ['name' => $r->name, 'rating' => $r->rating, 'comment' => $r->comment, 'service' => $r->serviceRequest->typeLabel()])->values(),
        'reviewStats' => [
            'count' => $hasReviews ? \App\Models\Review::where('status', 'approved')->count() : 0,
            'average' => $hasReviews ? round((float) \App\Models\Review::where('status', 'approved')->avg('rating'), 1) : 0,
        ],
    ]);
});

Route::get('/dashboard', function (\Illuminate\Http\Request $request) {
    $portfolios = \App\Models\Portfolio::where('user_id', $request->user()->id)
        ->latest('id')
        ->get()
        ->map(fn ($p) => [
            'id' => $p->id,
            'name' => $p->data['name'] ?? 'Untitled portfolio',
            'headline' => $p->data['headline'] ?? '',
            'template_key' => $p->template_key,
            'status' => $p->status,
            'slug' => $p->slug,
            'update_requested' => (bool) $p->update_requested_at,
            'has_unpublished_changes' => $p->status === 'published'
                && isset($p->published_data)
                && $p->published_data != $p->data,
        ]);

    return Inertia::render('Dashboard', [
        'portfolios' => $portfolios,
        'isAdmin' => (bool) $request->user()->is_admin,
    ]);
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware('auth')->group(function () {
    Route::get('/portfolios/create', [\App\Http\Controllers\PortfolioController::class, 'create'])->name('portfolios.create');
    Route::post('/portfolios', [\App\Http\Controllers\PortfolioController::class, 'store'])->name('portfolios.store');
    Route::get('/portfolios/{portfolio}/edit', [\App\Http\Controllers\PortfolioController::class, 'edit'])->name('portfolios.edit');
    Route::get('/portfolios/{portfolio}/preview', [\App\Http\Controllers\PortfolioController::class, 'preview'])->name('portfolios.preview');
    Route::get('/portfolios/{portfolio}/payment', [\App\Http\Controllers\PaymentController::class, 'create'])->name('payments.create');
    Route::post('/portfolios/{portfolio}/payment', [\App\Http\Controllers\PaymentController::class, 'store'])->name('payments.store');

    Route::middleware([\App\Http\Middleware\EnsureUserIsAdmin::class])->prefix('admin')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('admin.dashboard');
        Route::get('/payments', [\App\Http\Controllers\Admin\PaymentController::class, 'index'])->name('admin.payments.index');
        Route::post('/payments/{payment}/approve', [\App\Http\Controllers\Admin\PaymentController::class, 'approve'])->name('admin.payments.approve');
        Route::post('/payments/{payment}/reject', [\App\Http\Controllers\Admin\PaymentController::class, 'reject'])->name('admin.payments.reject');
        Route::post('/portfolios/{portfolio}/publish-update', [\App\Http\Controllers\Admin\PaymentController::class, 'publishUpdate'])->name('admin.portfolios.publish-update');
        Route::get('/portfolios/{portfolio}/preview', [\App\Http\Controllers\Admin\PaymentController::class, 'previewDraft'])->name('admin.portfolios.preview');
        Route::get('/reviews', [\App\Http\Controllers\Admin\ReviewController::class, 'index'])->name('admin.reviews.index');
        Route::get('/requests', [\App\Http\Controllers\Admin\ServiceRequestController::class, 'index'])->name('admin.requests.index');
        Route::get('/requests/{serviceRequest}', [\App\Http\Controllers\Admin\ServiceRequestController::class, 'show'])->name('admin.requests.show');
        Route::post('/requests/{serviceRequest}/quote', [\App\Http\Controllers\Admin\ServiceRequestController::class, 'quote'])->name('admin.requests.quote');
        Route::post('/requests/{serviceRequest}/payment/approve', [\App\Http\Controllers\Admin\ServiceRequestController::class, 'approvePayment'])->name('admin.requests.payment.approve');
        Route::post('/requests/{serviceRequest}/payment/reject', [\App\Http\Controllers\Admin\ServiceRequestController::class, 'rejectPayment'])->name('admin.requests.payment.reject');
        Route::post('/requests/{serviceRequest}/deliver', [\App\Http\Controllers\Admin\ServiceRequestController::class, 'deliver'])->name('admin.requests.deliver');
        Route::post('/requests/{serviceRequest}/reject', [\App\Http\Controllers\Admin\ServiceRequestController::class, 'reject'])->name('admin.requests.reject');
        Route::post('/reviews/{review}/approve', [\App\Http\Controllers\Admin\ReviewController::class, 'approve'])->name('admin.reviews.approve');
        Route::post('/reviews/{review}/hide', [\App\Http\Controllers\Admin\ReviewController::class, 'hide'])->name('admin.reviews.hide');
    });
    Route::put('/portfolios/{portfolio}', [\App\Http\Controllers\PortfolioController::class, 'update'])->name('portfolios.update');
    Route::post('/portfolios/{portfolio}/images', [\App\Http\Controllers\PortfolioController::class, 'uploadImage'])->middleware('throttle:30,1')->name('portfolios.images');
    Route::post('/portfolios/{portfolio}/template', [\App\Http\Controllers\PortfolioController::class, 'setTemplate'])->name('portfolios.template');
    Route::post('/portfolios/{portfolio}/request-update', [\App\Http\Controllers\PortfolioController::class, 'requestUpdate'])->middleware('throttle:10,1')->name('portfolios.request-update');
    Route::get('/resumes', [\App\Http\Controllers\ResumeController::class, 'index'])->name('resumes.index');
    Route::post('/resumes', [\App\Http\Controllers\ResumeController::class, 'store'])->name('resumes.store');
    Route::get('/resumes/{resume}/edit', [\App\Http\Controllers\ResumeController::class, 'edit'])->name('resumes.edit');
    Route::put('/resumes/{resume}', [\App\Http\Controllers\ResumeController::class, 'update'])->name('resumes.update');
    Route::delete('/resumes/{resume}', [\App\Http\Controllers\ResumeController::class, 'destroy'])->name('resumes.destroy');
    Route::get('/resumes/{resume}/preview', [\App\Http\Controllers\ResumeController::class, 'preview'])->name('resumes.preview');
    Route::get('/resumes/{resume}/download', [\App\Http\Controllers\ResumeController::class, 'download'])->middleware('throttle:20,1')->name('resumes.download');
});

require __DIR__.'/auth.php';

Route::get('/services/request', [\App\Http\Controllers\ServiceRequestController::class, 'create'])->name('services.create');
Route::post('/services/request', [\App\Http\Controllers\ServiceRequestController::class, 'store'])->middleware('throttle:5,1')->name('services.store');
Route::post('/services/{token}/review', [\App\Http\Controllers\ReviewController::class, 'store'])->middleware('throttle:5,1')->name('services.review');
Route::post('/services/{token}/payment', [\App\Http\Controllers\ServiceRequestController::class, 'pay'])->middleware('throttle:5,1')->name('services.pay');
Route::get('/services/{token}', [\App\Http\Controllers\ServiceRequestController::class, 'show'])->middleware('throttle:60,1')->name('services.show');
Route::get('/terms', fn () => \Inertia\Inertia::render('Legal/Show', ['page' => 'terms']))->name('legal.terms');
Route::get('/privacy', fn () => \Inertia\Inertia::render('Legal/Show', ['page' => 'privacy']))->name('legal.privacy');

Route::get('/p/{slug}', [\App\Http\Controllers\PublicPortfolioController::class, 'show'])->name('portfolios.public');
Route::get('/p/{slug}/resume', [\App\Http\Controllers\PublicPortfolioController::class, 'resume'])->middleware('throttle:20,1')->name('portfolios.resume');
