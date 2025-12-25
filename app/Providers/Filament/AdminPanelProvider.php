<?php

namespace App\Providers\Filament;

use App\Http\Middleware\ForceArabic; // ✅ استدعاء الميدل وير لإجبار الأرقام الإنجليزية
use Filament\Actions\CreateAction as HeaderCreateAction;
use Filament\FontProviders\GoogleFontProvider;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\MaxWidth;
use Filament\View\PanelsRenderHook;
use Filament\Tables\Actions\CreateAction as TableCreateAction;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Illuminate\Support\HtmlString;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->authGuard('web')
            ->login()

            ->maxContentWidth(MaxWidth::Full)
            ->sidebarCollapsibleOnDesktop()

            // ✅ تعطيل زر "إضافة وبدء إضافة المزيد" عالمياً لتبسيط الواجهة
            ->bootUsing(function () {
                CreateRecord::disableCreateAnother();
                TableCreateAction::configureUsing(fn (TableCreateAction $action) => $action->createAnother(false));
                HeaderCreateAction::configureUsing(fn (HeaderCreateAction $action) => $action->createAnother(false));
            })

            // ✅ الشعار المخصص (Full & Icon)
            ->brandLogo(fn () => new HtmlString('
                <div class="meta-logo-wrapper">
                    <img src="/logo-full.svg" class="logo-full" alt="Cargo">
                    <img src="/logo-icon.svg" class="logo-icon" alt="Cargo">
                </div>
            '))
            ->brandLogoHeight('4rem')
            ->brandName('Cargo Admin')

            ->darkMode(false)
            ->renderHook('panels::head.end', function () {
                $version = @filemtime(public_path('css/filament-theme.css')) ?: time();
                return new HtmlString('<link rel="stylesheet" href="/css/filament-theme.css?v=' . $version . '">');
            })
            ->renderHook(PanelsRenderHook::TOPBAR_START, fn () => view('filament.partials.topbar-title'))

            // ✅ لوحة الألوان الخاصة بالهوية
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

            // ✅ تعيين خط "Cairo" كخط أساسي للنظام
            ->font('Cairo', provider: GoogleFontProvider::class)

            // ✅ تخصيص الـ CSS ودعم الـ RTL (من اليمين لليسار)
                        // ✅ مجموعات القائمة الجانبية
            ->navigationGroups([
                NavigationGroup::make()->label('إدارة العمليات')->collapsible(false),
                NavigationGroup::make()->label('إدارة الشحنات')->collapsible(false),
                NavigationGroup::make()->label('الإعدادات')->icon('heroicon-o-cog-6-tooth')->collapsed(),
            ])

            ->discoverResources(in: app_path('Filament/Admin/Resources'), for: 'App\\Filament\\Admin\\Resources')
            ->discoverPages(in: app_path('Filament/Admin/Pages'), for: 'App\\Filament\\Admin\\Pages')
            ->pages([
                \App\Filament\Admin\Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Admin/Widgets'), for: 'App\\Filament\\Admin\\Widgets')
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

                // ✅ الميدل وير لإجبار اللغة العربية وتصحيح نظام الأرقام
                ForceArabic::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
