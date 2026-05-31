<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\CvBankController;
use App\Http\Controllers\Api\ExternalCvController;
use App\Http\Controllers\Api\MeetingController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\MatchingController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\RecruitmentRequestController;
use App\Http\Controllers\Api\RhResourceController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login'])->name('api.login');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('api.logout');
    Route::get('/me', [AuthController::class, 'me'])->name('api.me');
    Route::post('/profile', [AuthController::class, 'updateProfile'])->name('api.profile.update');
    Route::get('/dashboard', [DashboardController::class, 'show'])->name('api.dashboard.show');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('api.notifications.index');
    Route::post('/notifications/device', [NotificationController::class, 'registerDevice'])->name('api.notifications.device.store');
    Route::post('/notifications/device/debug', [NotificationController::class, 'debugDevice'])->name('api.notifications.device.debug');
    Route::delete('/notifications/device', [NotificationController::class, 'unregisterDevice'])->name('api.notifications.device.destroy');
    Route::get('/users', [UserController::class, 'index'])->name('api.users.index');

    Route::get('/cvs', [CvBankController::class, 'index'])->name('api.cvs.index');
    Route::get('/cvs/{cv}', [CvBankController::class, 'show'])->name('api.cvs.show');
    Route::get('/cvs/{cv}/download', [CvBankController::class, 'download'])->name('api.cvs.download');
    Route::get('/external-cvs', [ExternalCvController::class, 'index'])->name('api.external-cvs.index');
    Route::get('/external-cvs/{externalCvBatch}', [ExternalCvController::class, 'show'])->name('api.external-cvs.show');
    Route::get('/external-cv-files/{externalCv}/download', [ExternalCvController::class, 'download'])->name('api.external-cvs.files.download');

    Route::get('/matching', [MatchingController::class, 'index'])->name('api.matching.index');
    Route::get('/matching/{recruitmentRequest}', [MatchingController::class, 'show'])->name('api.matching.show');
    Route::post('/matching/{recruitmentRequest}/cancel', [MatchingController::class, 'cancel'])->name('api.matching.cancel');

    Route::get('/meetings', [MeetingController::class, 'index'])->name('api.meetings.index');
    Route::post('/meetings', [MeetingController::class, 'store'])->name('api.meetings.store');
    Route::get('/meetings/{meeting}', [MeetingController::class, 'show'])->name('api.meetings.show');

    Route::get('/messages', [MessageController::class, 'index'])->name('api.messages.index');
    Route::get('/messages/targets', [MessageController::class, 'targets'])->name('api.messages.targets');
    Route::post('/messages', [MessageController::class, 'store'])->name('api.messages.store');
    Route::get('/messages/attachments/{message}/download', [MessageController::class, 'attachment'])->name('api.messages.attachments.download');
    Route::get('/messages/{conversation}', [MessageController::class, 'show'])->name('api.messages.show');
    Route::post('/messages/{conversation}', [MessageController::class, 'send'])->name('api.messages.send');

    Route::get('/rh-resources', [RhResourceController::class, 'index'])->name('api.rh-resources.index');
    Route::get('/rh-resources/{rhResource}', [RhResourceController::class, 'show'])->name('api.rh-resources.show');
    Route::get('/rh-resources/{rhResource}/download', [RhResourceController::class, 'download'])->name('api.rh-resources.download');

    Route::get('/recruitment-requests', [RecruitmentRequestController::class, 'index'])->name('api.recruitment-requests.index');
    Route::post('/recruitment-requests', [RecruitmentRequestController::class, 'store'])->name('api.recruitment-requests.store');
    Route::get('/recruitment-requests/{recruitmentRequest}', [RecruitmentRequestController::class, 'show'])->name('api.recruitment-requests.show');
});
