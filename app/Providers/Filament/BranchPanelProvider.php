<?php

namespace App\Providers\Filament;

use App\Http\Middleware\ForceArabic;
use Filament\FontProviders\GoogleFontProvider;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\View\PanelsRenderHook;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\HtmlString;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class BranchPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('branch')
            ->path('branch')
            ->authGuard('web')
            ->login()
            ->brandLogo(fn () => new HtmlString('
                <div class="meta-logo-wrapper">
                    <img src="/logo-full.svg" class="logo-full" alt="Cargo">
                    <img src="/logo-icon.svg" class="logo-icon" alt="Cargo">
                </div>
            '))
            ->brandLogoHeight('4rem')
            ->brandName('Cargo Branch')
            ->colors([
                'primary' => [
                    50  => '#fff7ed',
                    100 => '#ffedd5',
                    200 => '#fed7aa',
                    300 => '#fdba74',
                    400 => '#fb923c',
                    500 => '#f97316',
                    600 => '#ea580c',
                    700 => '#c2410c',
                    800 => '#9a3412',
                    900 => '#7c2d12',
                ],
            ])
            ->font('Cairo', provider: GoogleFontProvider::class)
            ->renderHook('panels::head.end', function () {
                $version = @filemtime(public_path('css/filament-theme.css')) ?: time();
                return new HtmlString('<link rel="stylesheet" href="/css/filament-theme.css?v=' . $version . '">');
            })
            ->renderHook(PanelsRenderHook::TOPBAR_START, fn () => view('filament.partials.topbar-title'))
            
            ->discoverResources(
                in: app_path('Filament/Branch/Resources'),
                for: 'App\\Filament\\Branch\\Resources'
            )
            ->discoverPages(
                in: app_path('Filament/Branch/Pages'),
                for: 'App\\Filament\\Branch\\Pages'
            )
            ->discoverWidgets(
                in: app_path('Filament/Branch/Widgets'),
                for: 'App\\Filament\\Branch\\Widgets'
            )
            ->pages([
                Pages\Dashboard::class,
            ])
            ->widgets([
                Widgets\AccountWidget::class,
                Widgets\FilamentInfoWidget::class,
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

                'panel.role:branch',
                ForceArabic::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
