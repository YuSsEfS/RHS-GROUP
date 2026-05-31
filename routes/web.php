<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\User;

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
use App\Http\Controllers\Admin\CvController;
use App\Http\Controllers\Admin\MatchingHistoryController;
use App\Http\Controllers\Admin\RecruitmentRequestController;
use App\Http\Controllers\Admin\CvDownloadController;
use App\Http\Controllers\Admin\CvFolderController;
use App\Http\Controllers\Admin\ExternalCvController;
use App\Http\Controllers\Admin\GlobalSearchController;
use App\Http\Controllers\Admin\ConversationController as AdminConversationController;
use App\Http\Controllers\Admin\MeetingController as AdminMeetingController;
use App\Http\Controllers\Admin\RhResourceController as AdminRhResourceController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\ClientRecruitmentRequestController as AdminClientRecruitmentRequestController;
use App\Http\Controllers\Admin\EmployeeReportController as AdminEmployeeReportController;
use App\Http\Controllers\Admin\EmployeeLeaveRequestController as AdminEmployeeLeaveRequestController;
use App\Http\Controllers\Admin\EmployeeInternalRequestController as AdminEmployeeInternalRequestController;
use App\Http\Controllers\Admin\ClientRequestAlertController as AdminClientRequestAlertController;
use App\Http\Controllers\Auth\ClientRegistrationController;
use App\Http\Controllers\Employee\DashboardController as EmployeeDashboardController;
use App\Http\Controllers\Employee\ProfileController as EmployeeProfileController;
use App\Http\Controllers\Employee\ConversationController as EmployeeConversationController;
use App\Http\Controllers\Employee\CvBankController as EmployeeCvBankController;
use App\Http\Controllers\Employee\ExternalCvController as EmployeeExternalCvController;
use App\Http\Controllers\Employee\MeetingController as EmployeeMeetingController;
use App\Http\Controllers\Employee\RhResourceController as EmployeeRhResourceController;
use App\Http\Controllers\Employee\ReportController as EmployeeReportController;
use App\Http\Controllers\Employee\LeaveRequestController as EmployeeLeaveRequestController;
use App\Http\Controllers\Employee\InternalRequestController as EmployeeInternalRequestController;
use App\Http\Controllers\Employee\ClientRequestAlertController as EmployeeClientRequestAlertController;
use App\Http\Controllers\Employee\RecruitmentRequestController as EmployeeRecruitmentRequestController;
use App\Http\Controllers\Client\DashboardController as ClientDashboardController;
use App\Http\Controllers\Client\ProfileController as ClientProfileController;
use App\Http\Controllers\Client\ClientRequestAlertController as ClientClientRequestAlertController;
use App\Http\Controllers\Client\RecruitmentRequestController as ClientRecruitmentRequestController;
use App\Http\Controllers\Admin\OdooPreselectionController;

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
Route::get('/postuler', fn () => redirect(route('jobs', ['apply' => 'spontaneous']) . '#jobs-list'))->name('apply');
Route::post('/postuler', [ApplicationFormController::class, 'store'])->name('apply.store');

Route::middleware('guest')->group(function () {
    Route::get('/client/register', [ClientRegistrationController::class, 'create'])->name('client.register');
    Route::post('/client/register', [ClientRegistrationController::class, 'store'])->name('client.register.store');
});

/*
|--------------------------------------------------------------------------
| DASHBOARD REDIRECT
|--------------------------------------------------------------------------
*/
Route::get('/dashboard', function () {
    $user = Auth::user();

    if (!$user) {
        return redirect()->route('home');
    }

    if (!$user->isApproved() && !$user->isAdmin()) {
        return redirect()->route('account.pending');
    }

    if ($user->isAdmin()) {
        return redirect()->route('admin.dashboard');
    }

    if ($user->hasAnyRole([User::ROLE_EMPLOYEE, User::ROLE_SUPERVISOR])) {
        return redirect()->route('employee.dashboard');
    }

    if ($user->hasRole(User::ROLE_CLIENT)) {
        return redirect()->route('client.dashboard');
    }

    return redirect()->route('home');
})->middleware(['auth'])->name('dashboard');

Route::get('/account/pending', function () {
    return view('auth.account-pending', [
        'user' => auth()->user(),
    ]);
})->middleware('auth')->name('account.pending');

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
        Route::get('/dashboard/charts', [AdminDashboardController::class, 'charts'])->name('dashboard.charts');
        Route::get('/notifications/sidebar', function (\App\Services\SidebarNotificationService $notifications) {
            $user = auth()->user();

            return response()->json($notifications->forAdmin($user));
        })->name('notifications.sidebar');
        Route::resource('users', AdminUserController::class)->except('show');
        Route::get('/search', [GlobalSearchController::class, 'index'])->name('search.index');
        Route::get('/search/suggest', [GlobalSearchController::class, 'suggest'])->name('search.suggest');
        Route::get('/conversations', [AdminConversationController::class, 'index'])->name('conversations.index');
        Route::post('/conversations', [AdminConversationController::class, 'store'])->name('conversations.store');
        Route::get('/conversations/{conversation}', [AdminConversationController::class, 'show'])->name('conversations.show');
        Route::get('/conversations/{conversation}/messages', [AdminConversationController::class, 'messages'])->name('conversations.messages.index');
        Route::post('/conversations/{conversation}/messages', [AdminConversationController::class, 'send'])->name('conversations.messages.store');
        Route::patch('/conversations/{conversation}', [AdminConversationController::class, 'update'])->name('conversations.update');
        Route::get('/conversation-messages/{message}/attachment', [AdminConversationController::class, 'attachment'])->name('conversation-messages.attachment');
        Route::get('/conversation-messages/{message}/preview', [AdminConversationController::class, 'preview'])->name('conversation-messages.preview');
        Route::post('/conversations/{conversation}/attachments/download', [AdminConversationController::class, 'downloadAttachments'])->name('conversations.attachments.download');
        Route::delete('/conversation-messages/{message}', [AdminConversationController::class, 'destroyMessage'])->name('conversation-messages.destroy');
        Route::get('/client-recruitment-requests', [AdminClientRecruitmentRequestController::class, 'index'])
            ->name('client-recruitment-requests.index');
        Route::get('/client-recruitment-requests/{clientRecruitmentRequest}/edit', [AdminClientRecruitmentRequestController::class, 'edit'])
            ->name('client-recruitment-requests.edit');
        Route::put('/client-recruitment-requests/{clientRecruitmentRequest}', [AdminClientRecruitmentRequestController::class, 'update'])
            ->name('client-recruitment-requests.update');
        Route::get('/client-request-alerts', [AdminClientRequestAlertController::class, 'index'])
            ->name('client-request-alerts.index');
        Route::get('/client-request-alerts/{clientRequestAlert}/edit', [AdminClientRequestAlertController::class, 'edit'])
            ->name('client-request-alerts.edit');
        Route::put('/client-request-alerts/{clientRequestAlert}', [AdminClientRequestAlertController::class, 'update'])
            ->name('client-request-alerts.update');
        Route::get('/employee-reports', [AdminEmployeeReportController::class, 'index'])
            ->name('employee-reports.index');
        Route::get('/employee-reports/{employeeReport}', [AdminEmployeeReportController::class, 'show'])
            ->name('employee-reports.show');
        Route::patch('/employee-reports/{employeeReport}/review', [AdminEmployeeReportController::class, 'review'])
            ->name('employee-reports.review');
        Route::get('/employee-leave-requests', [AdminEmployeeLeaveRequestController::class, 'index'])
            ->name('employee-leave-requests.index');
        Route::get('/employee-leave-requests/{employeeLeaveRequest}/edit', [AdminEmployeeLeaveRequestController::class, 'edit'])
            ->name('employee-leave-requests.edit');
        Route::put('/employee-leave-requests/{employeeLeaveRequest}', [AdminEmployeeLeaveRequestController::class, 'update'])
            ->name('employee-leave-requests.update');
        Route::get('/employee-internal-requests', [AdminEmployeeInternalRequestController::class, 'index'])
            ->name('employee-internal-requests.index');
        Route::get('/employee-internal-requests/{employeeInternalRequest}/edit', [AdminEmployeeInternalRequestController::class, 'edit'])
            ->name('employee-internal-requests.edit');
        Route::put('/employee-internal-requests/{employeeInternalRequest}', [AdminEmployeeInternalRequestController::class, 'update'])
            ->name('employee-internal-requests.update');

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
        | MEETINGS & RH RESOURCES
        |--------------------------------------------------------------------------
        */
        Route::resource('meetings', AdminMeetingController::class);
        Route::get('/rh-resources/{rhResource}/download', [AdminRhResourceController::class, 'download'])
            ->name('rh-resources.download');
        Route::resource('rh-resources', AdminRhResourceController::class)
            ->parameters(['rh-resources' => 'rhResource']);

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

        Route::patch('/formations/{formation}/publish', [FormationController::class, 'publish'])
            ->name('formations.publish');

        Route::get('/formations/suggest', [FormationController::class, 'suggest'])
            ->name('formations.suggest');

        /*
        |--------------------------------------------------------------------------
        | CV BANK
        |--------------------------------------------------------------------------
        */
        Route::get('/cvs', [CvController::class, 'index'])->name('cvs.index');
        Route::get('/cvs/archived', [CvController::class, 'archived'])->name('cvs.archived');
        Route::get('/cvs/create', [CvController::class, 'create'])->name('cvs.create');
        Route::post('/cvs', [CvController::class, 'store'])->name('cvs.store');
        Route::get('/cvs/import-status/{cvImportBatch}', [CvController::class, 'importStatus'])
            ->name('cvs.import-status');
        Route::get('/cvs/storage-optimization-status', [CvController::class, 'storageOptimizationStatus'])
            ->name('cvs.storage-optimization-status');

        Route::delete('/cvs/bulk-delete', [CvController::class, 'bulkDestroy'])
            ->name('cvs.bulk-destroy');
        Route::patch('/cvs/bulk-archive', [CvController::class, 'bulkArchive'])
            ->name('cvs.bulk-archive');
        Route::patch('/cvs/bulk-assign-folder', [CvController::class, 'bulkAssignFolder'])
            ->name('cvs.bulk-assign-folder');
        Route::post('/cvs/bulk-optimize-storage', [CvController::class, 'bulkOptimizeStorage'])
            ->name('cvs.bulk-optimize-storage');
        Route::post('/cvs/optimize-uncompressed-storage', [CvController::class, 'optimizeUncompressedStorage'])
            ->name('cvs.optimize-uncompressed-storage');
        Route::post('/cvs/retry-failed-compression', [CvController::class, 'retryFailedCompression'])
            ->name('cvs.retry-failed-compression');

        Route::get('/cvs/{cv}/open', [CvController::class, 'open'])->name('cvs.open');
        Route::get('/cvs/{cv}/stream', [CvController::class, 'stream'])->name('cvs.stream');
        Route::patch('/cvs/{cv}/archive', [CvController::class, 'archive'])->name('cvs.archive');
        Route::patch('/cvs/{cv}/restore', [CvController::class, 'restore'])->name('cvs.restore');
        Route::patch('/cvs/{cv}/assign-folder', [CvController::class, 'assignFolder'])->name('cvs.assign-folder');
        Route::post('/cvs/{cv}/optimize-storage', [CvController::class, 'optimizeStorage'])
            ->name('cvs.optimize-storage');
        Route::delete('/cvs/{cv}', [CvController::class, 'destroy'])->name('cvs.destroy');

        /*
        |--------------------------------------------------------------------------
        | CV FOLDERS
        |--------------------------------------------------------------------------
        */
        Route::post('/cv-folders', [CvFolderController::class, 'store'])
            ->name('cv-folders.store');

        Route::patch('/cv-folders/{cvFolder}/archive', [CvFolderController::class, 'archive'])
            ->name('cv-folders.archive');
        Route::patch('/cv-folders/{cvFolder}/restore', [CvFolderController::class, 'restore'])
            ->name('cv-folders.restore');

        Route::delete('/cv-folders/{cvFolder}', [CvFolderController::class, 'destroy'])
            ->name('cv-folders.destroy');

        /*
        |--------------------------------------------------------------------------
        | RECRUITMENT REQUESTS
        |--------------------------------------------------------------------------
        */
        Route::get('/recruitment-requests/create', [RecruitmentRequestController::class, 'create'])
            ->name('recruitment_requests.create');
        Route::post('/recruitment-requests/{recruitmentRequest}/export-selected-odoo', [OdooPreselectionController::class, 'exportSelected'])
            ->name('recruitment_requests.exportSelectedOdoo');
        Route::get('/recruitment-requests/{recruitmentRequest}/odoo-export-status', [OdooPreselectionController::class, 'exportStatus'])
            ->name('recruitment_requests.odooExportStatus');
        Route::get('/matching-history', [MatchingHistoryController::class, 'index'])
            ->name('matching-history.index');

        Route::get('/recruitment-requests/import-docx', function () {
            return redirect()->route('admin.recruitment_requests.create');
        })->name('recruitment_requests.import_docx.form');

        Route::post('/recruitment-requests/import-docx', [RecruitmentRequestController::class, 'importDocx'])
            ->name('recruitment-requests.import-docx');

        Route::post('/recruitment-requests', [RecruitmentRequestController::class, 'store'])
            ->name('recruitment_requests.store');

        Route::get('/recruitment-requests/{recruitmentRequest}/results', [RecruitmentRequestController::class, 'results'])
            ->name('recruitment_requests.results');
        Route::get('/recruitment-requests/{recruitmentRequest}/results/suggest', [RecruitmentRequestController::class, 'matchSuggestions'])
            ->name('recruitment_requests.results.suggest');
        Route::get('/recruitment-requests/{recruitmentRequest}/matching-status', [RecruitmentRequestController::class, 'matchingStatus'])
            ->name('recruitment_requests.matching-status');
        Route::patch('/recruitment-requests/{recruitmentRequest}/cancel-matching', [RecruitmentRequestController::class, 'cancelMatching'])
            ->name('recruitment_requests.cancel-matching');

        Route::post('/matches/{match}/analyze-ai', [RecruitmentRequestController::class, 'analyzeWithAi'])
            ->name('matches.analyzeAi');

        Route::post('/matches/{match}/toggle-selection', [RecruitmentRequestController::class, 'toggleSelection'])
            ->name('matches.toggleSelection');

        Route::match(['get', 'post'], '/recruitment-requests/{recruitmentRequest}/download-selected', [CvDownloadController::class, 'downloadSelected'])
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
        Route::get('/external-cvs/{externalCvBatch}/status', [ExternalCvController::class, 'status'])
            ->name('external-cvs.status');

        Route::post('/external-cvs/{externalCvBatch}/index', [ExternalCvController::class, 'indexBatch'])
            ->name('external-cvs.index-batch');

        Route::delete('/external-cvs/{externalCvBatch}', [ExternalCvController::class, 'destroy'])
            ->name('external-cvs.destroy');

        Route::get('/external-cv-files/{externalCv}/open', [ExternalCvController::class, 'open'])
            ->name('external-cvs.files.open');
    });

Route::prefix('employee')
    ->name('employee.')
    ->middleware(['auth', 'role:employee,supervisor', 'approved'])
    ->group(function () {
        Route::get('/', [EmployeeDashboardController::class, 'index'])->name('dashboard');
        Route::redirect('/dashboard', '/employee')->name('dashboard.alias');
        Route::get('/notifications/sidebar', function (\App\Services\SidebarNotificationService $notifications) {
            $user = auth()->user();

            return response()->json($notifications->forEmployee($user));
        })->name('notifications.sidebar');

        Route::middleware('permission:employee_reports')->group(function () {
            Route::get('/reports', [EmployeeReportController::class, 'index'])->name('reports.index');
            Route::post('/reports', [EmployeeReportController::class, 'store'])->name('reports.store');
        });

        Route::middleware('permission:employee_leave_requests')->group(function () {
            Route::get('/leave-requests', [EmployeeLeaveRequestController::class, 'index'])->name('leave-requests.index');
            Route::post('/leave-requests', [EmployeeLeaveRequestController::class, 'store'])->name('leave-requests.store');
            Route::patch('/leave-requests/{leaveRequest}/cancel', [EmployeeLeaveRequestController::class, 'cancel'])->name('leave-requests.cancel');
        });

        Route::middleware('permission:employee_internal_requests')->group(function () {
            Route::get('/internal-requests', [EmployeeInternalRequestController::class, 'index'])->name('internal-requests.index');
            Route::post('/internal-requests', [EmployeeInternalRequestController::class, 'store'])->name('internal-requests.store');
        });

        Route::middleware('permission:recruitment_requests,recruitment_assignments_view')->group(function () {
            Route::get('/recruitment-requests', [EmployeeRecruitmentRequestController::class, 'index'])->name('recruitment-requests.index');
            Route::get('/recruitment-requests/{recruitmentRequest}', [EmployeeRecruitmentRequestController::class, 'show'])->name('recruitment-requests.show');
            Route::get('/recruitment-requests/{recruitmentRequest}/results', [EmployeeRecruitmentRequestController::class, 'results'])->name('recruitment-requests.results');
            Route::get('/recruitment-requests/{recruitmentRequest}/results/suggest', [EmployeeRecruitmentRequestController::class, 'matchSuggestions'])->name('recruitment-requests.results.suggest');
            Route::get('/recruitment-requests/{recruitmentRequest}/matches/{match}/open', [EmployeeRecruitmentRequestController::class, 'openMatchCv'])->name('recruitment-requests.matches.open');
            Route::get('/recruitment-requests/{recruitmentRequest}/matches/{match}/stream', [EmployeeRecruitmentRequestController::class, 'streamMatchCv'])->name('recruitment-requests.matches.stream');
            Route::match(['get', 'post'], '/recruitment-requests/{recruitmentRequest}/download-selected', [EmployeeRecruitmentRequestController::class, 'downloadSelected'])->name('recruitment-requests.download-selected');
        });

        Route::middleware('permission:recruitment_assignments_update')->group(function () {
            Route::put('/recruitment-requests/{recruitmentRequest}', [EmployeeRecruitmentRequestController::class, 'update'])->name('recruitment-requests.update');
            Route::post('/recruitment-requests/{recruitmentRequest}/launch-matching', [EmployeeRecruitmentRequestController::class, 'launchMatching'])->name('recruitment-requests.launch-matching');
        });

        Route::middleware('permission:cv_bank,cv_bank_manage')->group(function () {
            Route::get('/cvs', [EmployeeCvBankController::class, 'index'])->name('cvs.index');
            Route::get('/cvs/{cv}/open', [EmployeeCvBankController::class, 'open'])->name('cvs.open');
            Route::get('/cvs/{cv}/stream', [EmployeeCvBankController::class, 'stream'])->name('cvs.stream');
        });

        Route::middleware('permission:external_cvs,external_cvs_manage')->group(function () {
            Route::get('/external-cvs', [EmployeeExternalCvController::class, 'index'])->name('external-cvs.index');
            Route::get('/external-cvs/{externalCvBatch}', [EmployeeExternalCvController::class, 'show'])->name('external-cvs.show');
            Route::get('/external-cv-files/{externalCv}/open', [EmployeeExternalCvController::class, 'open'])->name('external-cvs.files.open');
        });

        Route::middleware('permission:recruitment_requests,client_alerts_view')->group(function () {
            Route::get('/client-alerts', [EmployeeClientRequestAlertController::class, 'index'])->name('client-alerts.index');
        });

        Route::middleware('permission:meetings_manage')->group(function () {
            Route::get('/meetings/create', [EmployeeMeetingController::class, 'create'])->name('meetings.create');
            Route::post('/meetings', [EmployeeMeetingController::class, 'store'])->name('meetings.store');
            Route::get('/meetings/{meeting}/edit', [EmployeeMeetingController::class, 'edit'])->name('meetings.edit')->whereNumber('meeting');
            Route::put('/meetings/{meeting}', [EmployeeMeetingController::class, 'update'])->name('meetings.update')->whereNumber('meeting');
            Route::delete('/meetings/{meeting}', [EmployeeMeetingController::class, 'destroy'])->name('meetings.destroy')->whereNumber('meeting');
        });

        Route::middleware('permission:meetings_view,meetings_manage')->group(function () {
            Route::get('/meetings', [EmployeeMeetingController::class, 'index'])->name('meetings.index');
            Route::get('/meetings/{meeting}', [EmployeeMeetingController::class, 'show'])->name('meetings.show')->whereNumber('meeting');
        });

        Route::middleware('permission:rh_resources_view,rh_resources_manage')->group(function () {
            Route::get('/rh-resources', [EmployeeRhResourceController::class, 'index'])->name('rh-resources.index');
            Route::get('/rh-resources/{rhResource}', [EmployeeRhResourceController::class, 'show'])->name('rh-resources.show');
            Route::get('/rh-resources/{rhResource}/download', [EmployeeRhResourceController::class, 'download'])->name('rh-resources.download');
        });

        Route::get('/messages', [EmployeeConversationController::class, 'index'])->name('messages.index');
        Route::post('/messages', [EmployeeConversationController::class, 'store'])->name('messages.start');
        Route::get('/messages/{conversation}', [EmployeeConversationController::class, 'show'])->name('messages.show');
        Route::get('/messages/{conversation}/items', [EmployeeConversationController::class, 'messages'])->name('messages.items.index');
        Route::post('/messages/{conversation}', [EmployeeConversationController::class, 'send'])->name('messages.store');
        Route::get('/message-attachments/{message}', [EmployeeConversationController::class, 'attachment'])->name('messages.attachment');
        Route::get('/message-attachments/{message}/preview', [EmployeeConversationController::class, 'preview'])->name('messages.preview');
        Route::post('/messages/{conversation}/attachments/download', [EmployeeConversationController::class, 'downloadAttachments'])->name('messages.attachments.download');
        Route::delete('/message-items/{message}', [EmployeeConversationController::class, 'destroyMessage'])->name('messages.items.destroy');

        Route::get('/profile', [EmployeeProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/profile', [EmployeeProfileController::class, 'update'])->name('profile.update');
        Route::patch('/profile/password', [EmployeeProfileController::class, 'password'])->name('profile.password');
    });

Route::prefix('client')
    ->name('client.')
    ->middleware(['auth', 'role:client', 'approved'])
    ->group(function () {
        Route::get('/', [ClientDashboardController::class, 'index'])->name('dashboard');
        Route::redirect('/dashboard', '/client')->name('dashboard.alias');
        Route::get('/recruitment-requests', [ClientRecruitmentRequestController::class, 'index'])
            ->middleware('permission:recruitment_requests')
            ->name('recruitment-requests.index');
        Route::get('/recruitment-requests/create', [ClientRecruitmentRequestController::class, 'create'])
            ->middleware('permission:recruitment_requests')
            ->name('recruitment-requests.create');
        Route::get('/recruitment-requests/{recruitmentRequest}', [ClientRecruitmentRequestController::class, 'show'])
            ->middleware('permission:recruitment_requests')
            ->name('recruitment-requests.show');
        Route::post('/recruitment-requests', [ClientRecruitmentRequestController::class, 'store'])
            ->middleware('permission:recruitment_requests')
            ->name('recruitment-requests.store');
        Route::post('/recruitment-requests/{recruitmentRequest}/alerts', [ClientClientRequestAlertController::class, 'store'])
            ->middleware('permission:recruitment_requests')
            ->name('recruitment-requests.alerts.store');
        Route::get('/profile', [ClientProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/profile', [ClientProfileController::class, 'update'])->name('profile.update');
        Route::patch('/profile/password', [ClientProfileController::class, 'password'])->name('profile.password');
    });

require __DIR__ . '/auth.php';
