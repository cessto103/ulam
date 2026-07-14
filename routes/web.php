<?php

use App\Http\Controllers\LanguageController;
use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', fn () => redirect('admin-panel'));

// Public legal pages — no auth; these URLs go on the Play Store listing.
Route::get('/legal/{slug}', function (string $slug) {
    $doc = \App\Models\LegalDocument::where('slug', $slug)->with('publishedVersion')->firstOrFail();
    abort_if(!$doc->publishedVersion, 404);

    return view('legal', [
        'title' => $doc->title,
        'version' => $doc->publishedVersion->version,
        'publishedAt' => $doc->publishedVersion->published_at?->format('F j, Y'),
        'html' => $doc->publishedVersion->contentHtml(),
    ]);
})->where('slug', '[a-z-]+');

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::post('/language', [LanguageController::class, 'switch'])->name('language.switch');

require __DIR__.'/auth.php';
