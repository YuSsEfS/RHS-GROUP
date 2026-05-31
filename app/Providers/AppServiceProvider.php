<?php

namespace App\Providers;

use App\Services\SidebarNotificationService;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('admin.layouts.app', function ($view) {
            $user = auth()->user();

            $view->with('sidebarNotifications', $user
                ? app(SidebarNotificationService::class)->forAdmin($user)
                : [
                    'items' => [],
                    'groups' => [],
                    'client_register' => [
                        'enabled' => false,
                        'url' => null,
                    ],
                ]);
        });

        View::composer('employee._sidebar', function ($view) {
            $user = auth()->user();

            $view->with('employeeSidebarNotifications', $user
                ? app(SidebarNotificationService::class)->forEmployee($user)
                : [
                    'items' => [],
                ]);
        });

        View::composer('dashboard.layouts.app', function ($view) {
            $user = auth()->user();

            if (!$user) {
                $view->with('portalHeaderData', [
                    'messages_count' => 0,
                ]);

                return;
            }

            $messagesCount = 0;

            if ($user->hasAnyRole([\App\Models\User::ROLE_EMPLOYEE, \App\Models\User::ROLE_SUPERVISOR])) {
                $messagesCount = data_get(app(SidebarNotificationService::class)->forEmployee($user), 'items.conversations', 0);
            }

            $view->with('portalHeaderData', [
                'messages_count' => $messagesCount,
            ]);
        });
    }
}
