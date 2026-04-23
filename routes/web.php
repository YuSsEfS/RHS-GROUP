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
use App\Http\Controllers\Admin\CvFolderController;
use App\Http\Controllers\Admin\ExternalCvController;

Route::redirect('/index2.html', '/', 301);
Route::redirect('/old/documentation/index.html.bak.bak', '/', 301);

/*
|--------------------------------------------------------------------------
| FRONT PAGES
|--------------------------------------------------------------------------
*/
Route::view('/', 'pages.home')->name('home');
Route::view('/a-propos', 'pages.about')->name('about');
Route::view('/services', 'pages.services')->name('services');
Route::view('/contact', 'pages.contact')->name('contact');

Route::view('/candidats', 'pages.candidates')->name('candidates');
Route::view('/entreprises', 'pages.enterprises')->name('enterprises');
Route::view('/ressources', 'pages.resources')->name('resources');
Route::view('/faq', 'pages.faq')->name('faq');
Route::view('/tawassol', 'pages.tawassol')->name('tawassol');

/*
|--------------------------------------------------------------------------
| APPLY
|--------------------------------------------------------------------------
*/
Route::get('/postuler', [ApplicationFormController::class, 'create'])->name('apply');
Route::post('/postuler', [ApplicationFormController::class, 'store'])->name('apply.store');

/*
|--------------------------------------------------------------------------
| DASHBOARD REDIRECT
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
| JOBS FRONT
|--------------------------------------------------------------------------
*/
Route::get('/offres', [JobOfferController::class, 'index'])->name('jobs');
Route::get('/offres/suggest', [JobOfferController::class, 'suggest'])->name('jobs.suggest');
Route::get('/offres/{slug}', [JobOfferController::class, 'show'])->name('jobs.show');

Route::get('/offres-alias', fn () => redirect()->route('jobs'))->name('offres');
Route::get('/offres-alias/{slug}', fn (string $slug) => redirect()->route('jobs.show', $slug))->name('offres.show');

/*
|--------------------------------------------------------------------------
| FORMS FRONT
|--------------------------------------------------------------------------
*/
Route::post('/contact', [ContactFormController::class, 'store'])->name('contact.store');
Route::post('/contact-send', [ContactFormController::class, 'store'])->name('contact.send');

/*
|--------------------------------------------------------------------------
| PUBLIC FILES
|--------------------------------------------------------------------------
*/
Route::get('/public-file/{path}', function ($path) {
    abort_unless(Storage::disk('public')->exists($path), 404);

    return Storage::disk('public')->response($path);
})->where('path', '.*')->name('public.file');

/*
|--------------------------------------------------------------------------
| QR
|--------------------------------------------------------------------------
*/
Route::get('/qr/{url}', [QrController::class, 'show'])
    ->where('url', '.*')
    ->name('qr');

/*
|--------------------------------------------------------------------------
| ADMIN
|--------------------------------------------------------------------------
*/
Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'admin'])
    ->group(function () {

        Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

        /*
        |--------------------------------------------------------------------------
        | CONTENT
        |--------------------------------------------------------------------------
        */
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

        Route::patch('/offers/{offer}/publish', [JobOfferAdminController::class, 'publish'])
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

        Route::patch('/formations/{formation}/publish', [FormationAdminController::class, 'publish'])
            ->name('formations.publish');

        Route::get('/formations/suggest', [FormationAdminController::class, 'suggest'])
            ->name('formations.suggest');

        /*
        |--------------------------------------------------------------------------
        | CV BANK
        |--------------------------------------------------------------------------
        */
        Route::get('/cvs', [CvController::class, 'index'])->name('cvs.index');
        Route::get('/cvs/create', [CvController::class, 'create'])->name('cvs.create');
        Route::post('/cvs', [CvController::class, 'store'])->name('cvs.store');

        Route::delete('/cvs/bulk-delete', [CvController::class, 'bulkDestroy'])
            ->name('cvs.bulk-destroy');

        Route::get('/cvs/{cv}/open', [CvController::class, 'open'])->name('cvs.open');
        Route::patch('/cvs/{cv}/assign-folder', [CvController::class, 'assignFolder'])->name('cvs.assign-folder');
        Route::delete('/cvs/{cv}', [CvController::class, 'destroy'])->name('cvs.destroy');

        /*
        |--------------------------------------------------------------------------
        | CV FOLDERS
        |--------------------------------------------------------------------------
        */
        Route::post('/cv-folders', [CvFolderController::class, 'store'])
            ->name('cv-folders.store');

        Route::delete('/cv-folders/{cvFolder}', [CvFolderController::class, 'destroy'])
            ->name('cv-folders.destroy');

        /*
        |--------------------------------------------------------------------------
        | RECRUITMENT REQUESTS
        |--------------------------------------------------------------------------
        */
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

        /*
        |--------------------------------------------------------------------------
        | EXTERNAL CVS
        |--------------------------------------------------------------------------
        */
        Route::get('/external-cvs', [ExternalCvController::class, 'index'])->name('external-cvs.index');
        Route::get('/external-cvs/create', [ExternalCvController::class, 'create'])->name('external-cvs.create');
        Route::post('/external-cvs', [ExternalCvController::class, 'store'])->name('external-cvs.store');

        Route::get('/external-cvs/{externalCvBatch}', [ExternalCvController::class, 'show'])
            ->name('external-cvs.show');

        Route::post('/external-cvs/{externalCvBatch}/index', [ExternalCvController::class, 'indexBatch'])
            ->name('external-cvs.index-batch');

        Route::delete('/external-cvs/{externalCvBatch}', [ExternalCvController::class, 'destroy'])
            ->name('external-cvs.destroy');

        Route::get('/external-cv-files/{externalCv}/open', [ExternalCvController::class, 'open'])
            ->name('external-cvs.files.open');
    });

require __DIR__ . '/auth.php';