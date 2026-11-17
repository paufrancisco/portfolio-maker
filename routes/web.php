<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\PaymentController as AdminPaymentController;
use App\Http\Controllers\Admin\ReviewController as AdminReviewController;
use App\Http\Controllers\Admin\ServiceRequestController as AdminServiceRequestController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PortfolioController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicPortfolioController;
use App\Http\Controllers\ResumeController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\ServiceRequestController;
use App\Http\Middleware\EnsureUserIsAdmin;
use App\Models\Portfolio;
use App\Models\Resume;
use App\Models\Review;
use App\Support\SampleContent;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;

Route::get('/', function () {
    $hasReviews = Schema::hasTable('reviews');

    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
        'portfolioPrice' => config('portfolio.price') > 0 ? config('portfolio.price') : null,
        'reviews' => ! $hasReviews ? [] : Review::with('serviceRequest')->where('status', 'approved')->latest('id')->take(12)->get()
            ->map(fn ($r) => ['name' => $r->name, 'rating' => $r->rating, 'comment' => $r->comment, 'service' => $r->serviceRequest?->typeLabel(), 'verified' => $r->service_request_id !== null, 'photo_url' => $r->photo_url])->values(),
        'reviewStats' => [
            'count' => $hasReviews ? Review::where('status', 'approved')->count() : 0,
            'average' => $hasReviews ? round((float) Review::where('status', 'approved')->avg('rating'), 1) : 0,
        ],
    ]);
});

// Public sample resumes for the home page previews (fake data only)
Route::get('/samples/resume/{key}', function (string $key) {
    return response()->view('resumes.'.$key, [
        'title' => 'Sample resume',
        'd' => array_replace_recursive(Resume::blankData(), SampleContent::resume()),
    ]);
})->whereIn('key', ['classic', 'modern', 'minimal']);

// Public sample portfolios for the home page previews (fake data only)
Route::get('/samples/portfolio/{key}', function (string $key) {
    return Inertia::render('Portfolios/Preview', [
        'portfolio' => [
            'id' => 0,
            'template_key' => $key,
            'saved_template_key' => $key,
            'status' => 'published',
            'data' => SampleContent::portfolio(),
        ],
        'theme' => SampleContent::theme($key),
        'watermark' => false,
        'templates' => null,
    ]);
})->whereIn('key', ['professional', 'pink', 'blue', 'showcase', 'minimal', 'editorial']);

Route::get('/dashboard', function (Request $request) {
    $portfolios = Portfolio::where('user_id', $request->user()->id)
        ->latest('id')
        ->get()
        ->map(fn ($p) => [
            'id' => $p->id,
            'name' => $p->data['name'] ?? 'Untitled portfolio',
            'headline' => $p->data['headline'] ?? '',
            'template_key' => $p->template_key,
            'status' => $p->status,
            'slug' => $p->slug,
            'view_count' => (int) $p->view_count,
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
    Route::get('/portfolios/create', [PortfolioController::class, 'create'])->name('portfolios.create');
    Route::post('/portfolios', [PortfolioController::class, 'store'])->name('portfolios.store');
    Route::get('/portfolios/{portfolio}/edit', [PortfolioController::class, 'edit'])->name('portfolios.edit');
    Route::get('/portfolios/{portfolio}/preview', [PortfolioController::class, 'preview'])->name('portfolios.preview');
    Route::get('/portfolios/{portfolio}/payment', [PaymentController::class, 'create'])->name('payments.create');
    Route::post('/portfolios/{portfolio}/payment', [PaymentController::class, 'store'])->name('payments.store');

    Route::middleware([EnsureUserIsAdmin::class])->prefix('admin')->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('admin.dashboard');
        Route::get('/payments', [AdminPaymentController::class, 'index'])->name('admin.payments.index');
        Route::post('/payments/{payment}/approve', [AdminPaymentController::class, 'approve'])->name('admin.payments.approve');
        Route::post('/payments/{payment}/reject', [AdminPaymentController::class, 'reject'])->name('admin.payments.reject');
        Route::post('/portfolios/{portfolio}/publish-update', [AdminPaymentController::class, 'publishUpdate'])->name('admin.portfolios.publish-update');
        Route::get('/portfolios/{portfolio}/preview', [AdminPaymentController::class, 'previewDraft'])->name('admin.portfolios.preview');
        Route::get('/reviews', [AdminReviewController::class, 'index'])->name('admin.reviews.index');
        Route::get('/requests', [AdminServiceRequestController::class, 'index'])->name('admin.requests.index');
        Route::get('/requests/{serviceRequest}', [AdminServiceRequestController::class, 'show'])->name('admin.requests.show');
        Route::post('/requests/{serviceRequest}/quote', [AdminServiceRequestController::class, 'quote'])->name('admin.requests.quote');
        Route::post('/requests/{serviceRequest}/payment/approve', [AdminServiceRequestController::class, 'approvePayment'])->name('admin.requests.payment.approve');
        Route::post('/requests/{serviceRequest}/payment/reject', [AdminServiceRequestController::class, 'rejectPayment'])->name('admin.requests.payment.reject');
        Route::post('/requests/{serviceRequest}/deliver', [AdminServiceRequestController::class, 'deliver'])->name('admin.requests.deliver');
        Route::post('/requests/{serviceRequest}/reject', [AdminServiceRequestController::class, 'reject'])->name('admin.requests.reject');
        Route::post('/reviews/{review}/approve', [AdminReviewController::class, 'approve'])->name('admin.reviews.approve');
        Route::post('/reviews/{review}/hide', [AdminReviewController::class, 'hide'])->name('admin.reviews.hide');
    });
    Route::put('/portfolios/{portfolio}', [PortfolioController::class, 'update'])->name('portfolios.update');
    Route::post('/portfolios/{portfolio}/images', [PortfolioController::class, 'uploadImage'])->middleware('throttle:30,1')->name('portfolios.images');
    Route::post('/portfolios/{portfolio}/template', [PortfolioController::class, 'setTemplate'])->name('portfolios.template');
    Route::post('/portfolios/{portfolio}/request-update', [PortfolioController::class, 'requestUpdate'])->middleware('throttle:10,1')->name('portfolios.request-update');
    Route::get('/resumes', [ResumeController::class, 'index'])->name('resumes.index');
    Route::post('/resumes', [ResumeController::class, 'store'])->name('resumes.store');
    Route::get('/resumes/{resume}/edit', [ResumeController::class, 'edit'])->name('resumes.edit');
    Route::put('/resumes/{resume}', [ResumeController::class, 'update'])->name('resumes.update');
    Route::delete('/resumes/{resume}', [ResumeController::class, 'destroy'])->name('resumes.destroy');
    Route::get('/resumes/{resume}/preview', [ResumeController::class, 'preview'])->name('resumes.preview');
    Route::get('/resumes/{resume}/download', [ResumeController::class, 'download'])->middleware('throttle:20,1')->name('resumes.download');
});

require __DIR__.'/auth.php';

Route::get('/services/request', [ServiceRequestController::class, 'create'])->name('services.create');
Route::post('/services/request', [ServiceRequestController::class, 'store'])->middleware('throttle:5,1')->name('services.store');
Route::post('/services/{token}/review', [ReviewController::class, 'store'])->middleware('throttle:5,1')->name('services.review');
Route::post('/reviews', [ReviewController::class, 'storePublic'])->middleware('throttle:3,10')->name('reviews.store');
Route::post('/services/{token}/payment', [ServiceRequestController::class, 'pay'])->middleware('throttle:5,1')->name('services.pay');
Route::get('/services/{token}', [ServiceRequestController::class, 'show'])->middleware('throttle:60,1')->name('services.show');
Route::get('/terms', fn () => \Inertia\Inertia::render('Legal/Show', ['page' => 'terms']))->name('legal.terms');
Route::get('/privacy', fn () => \Inertia\Inertia::render('Legal/Show', ['page' => 'privacy']))->name('legal.privacy');

Route::get('/p/{slug}', [PublicPortfolioController::class, 'show'])->name('portfolios.public');
Route::get('/p/{slug}/resume', [PublicPortfolioController::class, 'resume'])->middleware('throttle:20,1')->name('portfolios.resume');
