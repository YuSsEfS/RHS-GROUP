<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

use App\Http\Controllers\JobOfferController;
use App\Http\Controllers\ContactFormController;
use App\Http\Controllers\ApplicationFormController;
use App\Http\Controllers\CatalogueController;
use App\Http\Controllers\FormationPublicController;
use App\Http\Controllers\QrController;

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\ContentController;
use App\Http\Controllers\Admin\JobOfferAdminController;
use App\Http\Controllers\Admin\ContactMessageController;
use App\Http\Controllers\Admin\ApplicationController;
use App\Http\Controllers\Admin\FormationController;
use App\Http\Controllers\Admin\FormationAdminController;
use App\Http\Controllers\Admin\CvController;
use App\Http\Controllers\Admin\RecruitmentRequestController;
use App\Http\Controllers\Admin\CvDownloadController;

Route::redirect('/index2.html', '/', 301);
Route::redirect('/old/documentation/index.html.bak.bak', '/', 301);

/*
|--------------------------------------------------------------------------
| FRONT PAGES (static views)
|--------------------------------------------------------------------------
*/
Route::view('/', 'pages.home')->name('home');
Route::view('/a-propos', 'pages.about')->name('about');
Route::view('/services', 'pages.services')->name('services');
Route::view('/contact', 'pages.contact')->name('contact');

/*
|--------------------------------------------------------------------------
| APPLY (Postuler)
|--------------------------------------------------------------------------
*/
Route::get('/postuler', [ApplicationFormController::class, 'create'])->name('apply');
Route::post('/postuler', [ApplicationFormController::class, 'store'])->name('apply.store');

Route::view('/candidats', 'pages.candidates')->name('candidates');
Route::view('/entreprises', 'pages.enterprises')->name('enterprises');
Route::view('/ressources', 'pages.resources')->name('resources');
Route::view('/faq', 'pages.faq')->name('faq');
Route::view('/tawassol', 'pages.tawassol')->name('tawassol');

/*
|--------------------------------------------------------------------------
| Breeze expects a "dashboard" route after login
|--------------------------------------------------------------------------
*/
Route::get('/dashboard', function () {
    if (Auth::check() && Auth::user()->role === 'admin') {
        return redirect()->route('admin.dashboard');
    }

    return redirect()->route('home');
})->middleware(['auth'])->name('dashboard');

/*
|--------------------------------------------------------------------------
| CATALOGUE
|--------------------------------------------------------------------------
*/
Route::get('/catalogue-formation', [CatalogueController::class, 'index'])->name('catalogue');
Route::view('/catalogue-formation/{slug}', 'pages.catalogue-show')->name('catalogue.show');
Route::get('/catalogue', [CatalogueController::class, 'index'])->name('catalogue');
Route::get('/formations/{formation}', [FormationPublicController::class, 'show'])
    ->name('formations.show');

/*
|--------------------------------------------------------------------------
| JOBS (Front)
|--------------------------------------------------------------------------
*/
Route::get('/offres', [JobOfferController::class, 'index'])->name('jobs');
Route::get('/offres/suggest', [JobOfferController::class, 'suggest'])
    ->name('jobs.suggest');
Route::get('/offres/{slug}', [JobOfferController::class, 'show'])->name('jobs.show');

Route::get('/offres-alias', fn () => redirect()->route('jobs'))->name('offres');
Route::get('/offres-alias/{slug}', fn (string $slug) => redirect()->route('jobs.show', $slug))->name('offres.show');

/*
|--------------------------------------------------------------------------
| FORMS (Front)
|--------------------------------------------------------------------------
*/
Route::post('/contact', [ContactFormController::class, 'store'])->name('contact.store');
Route::post('/contact-send', [ContactFormController::class, 'store'])->name('contact.send');

/*
|--------------------------------------------------------------------------
| Public files (no storage:link needed)
|--------------------------------------------------------------------------
*/
Route::get('/public-file/{path}', function ($path) {
    abort_unless(Storage::disk('public')->exists($path), 404);
    return Storage::disk('public')->response($path);
})->where('path', '.*')->name('public.file');

/*
|--------------------------------------------------------------------------
| QR (SVG)
|--------------------------------------------------------------------------
*/
Route::get('/qr/{url}', [QrController::class, 'show'])
    ->where('url', '.*')
    ->name('qr');

/*
|--------------------------------------------------------------------------
| ADMIN (auth + admin middleware)
|--------------------------------------------------------------------------
*/
Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'admin'])
    ->group(function () {

        Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

        Route::get('/content', [ContentController::class, 'index'])->name('content.index');
        Route::post('/content', [ContentController::class, 'save'])->name('content.save');

        Route::get('/builder', [ContentController::class, 'builder'])->name('content.builder');
        Route::get('/builder/data', [ContentController::class, 'builderData'])->name('content.builder.data');
        Route::post('/builder/upload', [ContentController::class, 'builderUpload'])->name('content.builder.upload');
        Route::post('/builder/save', [ContentController::class, 'builderSave'])->name('content.builder.save');

        /*
        |--------------------------------------------------------------------------
        | OFFERS ADMIN
        |--------------------------------------------------------------------------
        */
        Route::get('/offers/suggest', [JobOfferAdminController::class, 'suggest'])
            ->name('offers.suggest');

        Route::resource('offers', JobOfferAdminController::class)->parameters([
            'offers' => 'offer',
        ]);

        Route::patch('offers/{offer}/publish', [JobOfferAdminController::class, 'publish'])
            ->name('offers.publish');

        /*
        |--------------------------------------------------------------------------
        | MESSAGES
        |--------------------------------------------------------------------------
        */
        Route::get('/messages', [ContactMessageController::class, 'index'])->name('messages.index');
        Route::get('/messages/{message}', [ContactMessageController::class, 'show'])->name('messages.show');

        /*
        |--------------------------------------------------------------------------
        | APPLICATIONS
        |--------------------------------------------------------------------------
        */
        Route::get('/applications/suggest', [ApplicationController::class, 'suggest'])
            ->name('applications.suggest');

        Route::get('/applications', [ApplicationController::class, 'index'])
            ->name('applications.index');

        Route::get('/applications/{application}', [ApplicationController::class, 'show'])
            ->whereNumber('application')
            ->name('applications.show');

        Route::get('/applications/{application}/cv', [ApplicationController::class, 'cv'])
            ->name('applications.cv');

        Route::get('/applications/{application}/letter', [ApplicationController::class, 'letter'])
            ->name('applications.letter');

        /*
        |--------------------------------------------------------------------------
        | PROFILE
        |--------------------------------------------------------------------------
        */
        Route::get('/profile', [\App\Http\Controllers\Admin\ProfileController::class, 'edit'])
            ->name('profile.edit');

        Route::patch('/profile', [\App\Http\Controllers\Admin\ProfileController::class, 'update'])
            ->name('profile.update');

        Route::patch('/profile/password', [\App\Http\Controllers\Admin\ProfileController::class, 'password'])
            ->name('profile.password');

        /*
        |--------------------------------------------------------------------------
        | FORMATIONS
        |--------------------------------------------------------------------------
        */
        Route::resource('formations', FormationController::class);

        Route::patch('formations/{formation}/publish', [FormationAdminController::class, 'publish'])
            ->name('formations.publish');

        Route::get('formations/suggest', [FormationAdminController::class, 'suggest'])
            ->name('formations.suggest');

        /*
        |--------------------------------------------------------------------------
        | AI RECRUITMENT / CV MATCHING
        |--------------------------------------------------------------------------
        */
        Route::get('/cvs', [CvController::class, 'index'])->name('cvs.index');
        Route::get('/cvs/create', [CvController::class, 'create'])->name('cvs.create');
        Route::post('/cvs', [CvController::class, 'store'])->name('cvs.store');
        Route::get('/cvs/{cv}/open', [CvController::class, 'open'])->name('cvs.open');

        Route::get('/recruitment-requests/create', [RecruitmentRequestController::class, 'create'])
            ->name('recruitment_requests.create');

        Route::get('/recruitment-requests/import-docx', function () {
            return redirect()->route('admin.recruitment_requests.create');
        })->name('recruitment_requests.import_docx.form');

        Route::post('/recruitment-requests/import-docx', [RecruitmentRequestController::class, 'importDocx'])
            ->name('recruitment-requests.import-docx');

        Route::post('/recruitment-requests', [RecruitmentRequestController::class, 'store'])
            ->name('recruitment_requests.store');

        Route::get('/recruitment-requests/{recruitmentRequest}/results', [RecruitmentRequestController::class, 'results'])
            ->name('recruitment_requests.results');
        
        Route::post('/matches/{match}/analyze-ai', [RecruitmentRequestController::class, 'analyzeWithAi'])
            ->name('matches.analyzeAi');

        Route::post('/matches/{match}/toggle-selection', [RecruitmentRequestController::class, 'toggleSelection'])
            ->name('matches.toggleSelection');

        Route::get('/recruitment-requests/{recruitmentRequest}/download-selected', [CvDownloadController::class, 'downloadSelected'])
            ->name('recruitment_requests.downloadSelected');
    });

/*
|--------------------------------------------------------------------------
| OPENAI TEST
|--------------------------------------------------------------------------
*/
// Route::get('/test-openai', function () {
//     try {
//         $apiKey = config('services.openai.api_key');

//         if (!$apiKey) {
//             return response()->json([
//                 'success' => false,
//                 'step' => 'config',
//                 'message' => 'OpenAI key not found in config(services.openai.api_key)',
//             ], 500);
//         }

//         $client = \OpenAI::client($apiKey);

//         $response = $client->chat()->create([
//             'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
//             'messages' => [
//                 [
//                     'role' => 'user',
//                     'content' => 'Reply only with OK',
//                 ],
//             ],
//         ]);

//         return response()->json([
//             'success' => true,
//             'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
//             'content' => $response->choices[0]->message->content ?? null,
//         ]);
//     } catch (\Throwable $e) {
//         return response()->json([
//             'success' => false,
//             'message' => $e->getMessage(),
//             'file' => $e->getFile(),
//             'line' => $e->getLine(),
//         ], 500);
//     }
// });
// Route::get('/test-openai', function () {
//     try {
//         $apiKey = config('services.openai.api_key');

//         if (!$apiKey) {
//             return response()->json([
//                 'success' => false,
//                 'step' => 'config',
//                 'message' => 'OpenAI key not found in config(services.openai.api_key)',
//             ], 500);
//         }

//         $client = \OpenAI::client($apiKey);

//         $response = $client->chat()->create([
//             'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
//             'messages' => [
//                 [
//                     'role' => 'user',
//                     'content' => 'Reply only with OK',
//                 ],
//             ],
//         ]);

//         return response()->json([
//             'success' => true,
//             'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
//             'content' => $response->choices[0]->message->content ?? null,
//         ]);
//     } catch (\Throwable $e) {
//         return response()->json([
//             'success' => false,
//             'message' => $e->getMessage(),
//             'file' => $e->getFile(),
//             'line' => $e->getLine(),
//         ], 500);
//     }
// });
/*

|--------------------------------------------------------------------------
| Breeze / Auth routes
|--------------------------------------------------------------------------
*/
// Route::get('/test-openai', function () {
//     try {
//         $client = \OpenAI::client(config('services.openai.api_key'));

//         $response = $client->chat()->create([
//             'model' => 'gpt-4o-mini',
//             'messages' => [
//                 [
//                     'role' => 'user',
//                     'content' => 'Say hello'
//                 ]
//             ],
//         ]);

//         return response()->json([
//             'success' => true,
//             'message' => $response->choices[0]->message->content ?? null
//         ]);

//     } catch (\Throwable $e) {
//         return response()->json([
//             'success' => false,
//             'error' => $e->getMessage()
//         ]);
//     }
// });
require __DIR__ . '/auth.php';