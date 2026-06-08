<?php

namespace App\Providers\Filament;

use App\Filament\Widgets\BusinessOverviewWidget;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use App\Filament\Widgets\CreatorValueLeaderboardWidget;
use App\Filament\Widgets\FollowUpAlertWidget;
use App\Filament\Widgets\FulfillmentMonitorWidget;
use App\Filament\Widgets\InviteConflictWidget;
use App\Filament\Widgets\TeamPerformanceWidget;
use App\Support\ThemeColor;
use Filament\Facades\Filament;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->databaseNotifications()
            ->colors([
                'primary' => Color::Rose,
            ])
            ->renderHook(
                'panels::head.end',
                fn (): string => '<link rel="stylesheet" href="'.asset('css/dycrm-admin.css').'?v=20260609-5">'
                    .ThemeColor::styleTagForUser(auth()->user())->toHtml(),
            )
            ->renderHook(
                PanelsRenderHook::USER_MENU_PROFILE_AFTER,
                fn (): string => view('filament.user-menu.theme-color-picker', [
                    'currentColor' => ThemeColor::normalize(Filament::auth()->user()?->theme_color),
                    'options' => ThemeColor::options(),
                ])->render(),
            )
            ->renderHook(
                'panels::body.end',
                fn (): string => '<script src="'.asset('js/creator-ai-diagnosis.js').'?v=20260608-1"></script>'
                    .'<script src="'.asset('js/sample-navigation-badge.js').'?v=20260607-4"></script>',
            )
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                BusinessOverviewWidget::class,
                FollowUpAlertWidget::class,
                InviteConflictWidget::class,
                FulfillmentMonitorWidget::class,
                TeamPerformanceWidget::class,
                CreatorValueLeaderboardWidget::class,
                Widgets\AccountWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->plugins([
                FilamentShieldPlugin::make(),
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
