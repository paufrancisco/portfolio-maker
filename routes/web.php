<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
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
        Route::get('/payments', [\App\Http\Controllers\Admin\PaymentController::class, 'index'])->name('admin.payments.index');
        Route::post('/payments/{payment}/approve', [\App\Http\Controllers\Admin\PaymentController::class, 'approve'])->name('admin.payments.approve');
        Route::post('/payments/{payment}/reject', [\App\Http\Controllers\Admin\PaymentController::class, 'reject'])->name('admin.payments.reject');
    });
    Route::put('/portfolios/{portfolio}', [\App\Http\Controllers\PortfolioController::class, 'update'])->name('portfolios.update');
    Route::post('/portfolios/{portfolio}/images', [\App\Http\Controllers\PortfolioController::class, 'uploadImage'])->middleware('throttle:30,1')->name('portfolios.images');
});

require __DIR__.'/auth.php';

Route::get('/p/{slug}', [\App\Http\Controllers\PublicPortfolioController::class, 'show'])->name('portfolios.public');
Route::get('/p/{slug}/resume', [\App\Http\Controllers\PublicPortfolioController::class, 'resume'])->middleware('throttle:20,1')->name('portfolios.resume');
