<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\BookingAdminController;
use App\Http\Controllers\Admin\ContentController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ExperienceController;
use App\Http\Controllers\Admin\ExportController;
use App\Http\Controllers\Admin\MediaController;
use App\Http\Controllers\Admin\ProjectController;
use App\Http\Controllers\Admin\PublishController;
use App\Http\Controllers\Admin\ResumeController;
use App\Http\Controllers\Admin\SeoController;
use App\Http\Controllers\Api\AssistantController as PublicAssistantController;
use App\Http\Controllers\Api\BookingController as PublicBookingController;
use App\Http\Controllers\PublicSiteController;
use Illuminate\Support\Facades\Route;

Route::get('/sitemap.xml', [PublicSiteController::class, 'sitemap'])->name('sitemap');
Route::get('/robots.txt', [PublicSiteController::class, 'robots'])->name('robots');

/* Homepage AI portfolio assistant (OpenAI Responses API; no streaming worker). */
Route::prefix('api/assistant')->name('api.assistant.')->group(function () {
    Route::get('/status', [PublicAssistantController::class, 'status'])->middleware('throttle:60,1')->name('status');
    Route::post('/chat', [PublicAssistantController::class, 'chat'])->middleware('throttle:20,1')->name('chat');
});

/* Self-hosted Book a Call API (no Cal.com / Calendly). */
Route::prefix('api/booking')->name('api.booking.')->group(function () {
    Route::get('/bootstrap', [PublicBookingController::class, 'bootstrap'])->middleware('throttle:60,1')->name('bootstrap');
    Route::get('/config', [PublicBookingController::class, 'config'])->name('config');
    Route::get('/month', [PublicBookingController::class, 'month'])->middleware('throttle:60,1')->name('month');
    Route::get('/slots', [PublicBookingController::class, 'slots'])->middleware('throttle:60,1')->name('slots');
    Route::get('/convert', [PublicBookingController::class, 'convert'])->middleware('throttle:60,1')->name('convert');
    Route::post('/', [PublicBookingController::class, 'store'])->middleware('throttle:8,1')->name('store');
    Route::get('/{reference}/ics', [PublicBookingController::class, 'ics'])->where('reference', '[A-Za-z0-9\-]+')->name('ics');
});

Route::get('/', fn (\Illuminate\Http\Request $r) => app(PublicSiteController::class)->home($r, 'en'))->name('home');
Route::get('/work', fn (\Illuminate\Http\Request $r) => app(PublicSiteController::class)->work($r, 'en'))->name('work');
Route::get('/about', fn (\Illuminate\Http\Request $r) => app(PublicSiteController::class)->about($r, 'en'))->name('about');
Route::get('/ask', fn (\Illuminate\Http\Request $r) => app(PublicSiteController::class)->ask($r, 'en'))->name('ask');
Route::get('/contact', fn (\Illuminate\Http\Request $r) => app(PublicSiteController::class)->contact($r, 'en'))->name('contact');
Route::get('/work/{slug}', fn (\Illuminate\Http\Request $r, string $slug) => app(PublicSiteController::class)->project($r, 'en', $slug))->name('project');

Route::get('/fa', fn (\Illuminate\Http\Request $r) => app(PublicSiteController::class)->home($r, 'fa'))->name('fa.home');
Route::get('/fa/work', fn (\Illuminate\Http\Request $r) => app(PublicSiteController::class)->work($r, 'fa'))->name('fa.work');
Route::get('/fa/about', fn (\Illuminate\Http\Request $r) => app(PublicSiteController::class)->about($r, 'fa'))->name('fa.about');
Route::get('/fa/ask', fn (\Illuminate\Http\Request $r) => app(PublicSiteController::class)->ask($r, 'fa'))->name('fa.ask');
Route::get('/fa/contact', fn (\Illuminate\Http\Request $r) => app(PublicSiteController::class)->contact($r, 'fa'))->name('fa.contact');
Route::get('/fa/work/{slug}', fn (\Illuminate\Http\Request $r, string $slug) => app(PublicSiteController::class)->project($r, 'fa', $slug))->name('fa.project');

Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
        Route::post('/login', [AuthController::class, 'login'])
            ->middleware('throttle:5,1')
            ->name('login.submit');
    });

    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

    Route::middleware(['auth', 'owner'])->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/preview', [DashboardController::class, 'preview'])->name('preview');

        Route::get('/content', [ContentController::class, 'edit'])->name('content.edit');
        Route::post('/content', [ContentController::class, 'update'])->name('content.update');

        Route::get('/bookings', [BookingAdminController::class, 'index'])->name('bookings.index');
        Route::get('/bookings/export.csv', [BookingAdminController::class, 'exportCsv'])->name('bookings.export');
        Route::get('/bookings/settings', [BookingAdminController::class, 'settings'])->name('bookings.settings');
        Route::post('/bookings/settings', [BookingAdminController::class, 'updateSettings'])->name('bookings.settings.update');
        Route::post('/bookings/availability', [BookingAdminController::class, 'saveAvailability'])->name('bookings.availability');
        Route::post('/bookings/blocks', [BookingAdminController::class, 'storeBlock'])->name('bookings.blocks.store');
        Route::delete('/bookings/blocks/{block}', [BookingAdminController::class, 'destroyBlock'])->name('bookings.blocks.destroy');
        Route::get('/bookings/{booking}', [BookingAdminController::class, 'show'])->name('bookings.show');
        Route::post('/bookings/{booking}/cancel', [BookingAdminController::class, 'cancel'])->name('bookings.cancel');
        Route::post('/bookings/{booking}/complete', [BookingAdminController::class, 'complete'])->name('bookings.complete');

        Route::resource('experience', ExperienceController::class)->except('show');

        Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index');
        Route::get('/projects/create', [ProjectController::class, 'create'])->name('projects.create');
        Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store');
        Route::get('/projects/{project}/edit', [ProjectController::class, 'edit'])->name('projects.edit');
        Route::put('/projects/{project}', [ProjectController::class, 'update'])->name('projects.update');
        Route::post('/projects/import', [ProjectController::class, 'import'])->name('projects.import');
        Route::post('/projects/reorder', [ProjectController::class, 'reorder'])->name('projects.reorder');
        Route::post('/projects/{project}/archive', [ProjectController::class, 'archive'])->name('projects.archive');
        Route::post('/projects/{project}/restore', [ProjectController::class, 'restore'])->name('projects.restore');

        Route::get('/resume', [ResumeController::class, 'edit'])->name('resume.edit');
        Route::post('/resume/upload', [ResumeController::class, 'upload'])->name('resume.upload');
        Route::post('/resume/fields', [ResumeController::class, 'updateFields'])->name('resume.fields');
        Route::post('/resume/generate', [ResumeController::class, 'generate'])->name('resume.generate');
        Route::post('/resume/{resume}/activate', [ResumeController::class, 'activate'])->name('resume.activate');

        Route::get('/seo', [SeoController::class, 'edit'])->name('seo.edit');
        Route::post('/seo', [SeoController::class, 'update'])->name('seo.update');

        Route::get('/media', [MediaController::class, 'index'])->name('media.index');
        Route::post('/media', [MediaController::class, 'upload'])->name('media.upload');
        Route::put('/media/{media}', [MediaController::class, 'update'])->name('media.update');
        Route::get('/media/{media}/raw', [MediaController::class, 'raw'])->name('media.raw');

        Route::post('/publish', [PublishController::class, 'publish'])->name('publish');
        Route::post('/publish/{publication}/rollback', [PublishController::class, 'rollback'])->name('publish.rollback');

        Route::get('/export.json', [ExportController::class, 'json'])->name('export.json');
    });
});
