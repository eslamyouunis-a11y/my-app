<?php

namespace App\Providers\Filament;

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
            ->id('admin')
            ->path('admin')
            ->authGuard('web')
            ->login()

            ->maxContentWidth(MaxWidth::Full)
            ->sidebarCollapsibleOnDesktop()

            // ✅ تعطيل زر "إضافة وبدء إضافة المزيد" عالمياً
            ->bootUsing(function () {
                CreateRecord::disableCreateAnother();
                TableCreateAction::configureUsing(fn (TableCreateAction $action) => $action->createAnother(false));
                HeaderCreateAction::configureUsing(fn (HeaderCreateAction $action) => $action->createAnother(false));
            })

            // ✅ BRAND / LOGO
            ->brandLogo(fn () => new HtmlString('
                <div class="meta-logo-wrapper">
                    <img src="/logo-full.svg" class="logo-full" alt="Cargo">
                    <img src="/logo-icon.svg" class="logo-icon" alt="Cargo">
                </div>
            '))
            ->brandLogoHeight('4rem')
            ->brandName('Cargo Admin')

            ->darkMode(false)

            // ✅ COLORS (زي اللي كنت عامله)
            ->colors([
                'primary' => [
                    50  => '#eef1f3',
                    100 => '#d9dfe3',
                    200 => '#b3bdc6',
                    300 => '#8d9ca9',
                    400 => '#5b7283',
                    500 => '#283943',
                    600 => '#24333c',
                    700 => '#1f2c34',
                    800 => '#19242a',
                    900 => '#121a20',
                ],
            ])

            // ✅ FONT (Cairo) — ده الصح في Filament v3
            ->font('Cairo', provider: GoogleFontProvider::class)

            // ✅ RTL + CSS عالمي + ضمان الخط
            ->renderHook('panels::head.end', fn () => new HtmlString('
                <style>
                    /* ===== RTL Global ===== */
                    html { direction: rtl; }
                    body { direction: rtl; text-align: right; }

                    /* ===== Font Fallback ===== */
                    :root { --fi-font-family: "Cairo", ui-sans-serif, system-ui, -apple-system, "Segoe UI", Arial, "Noto Sans Arabic", sans-serif; }
                    body, .fi-body, .fi-main, .fi-layout { font-family: var(--fi-font-family) !important; }

                    /* ===== Global Background ===== */
                    body, .fi-main {
                        background: linear-gradient(135deg, #f0f2f5 0%, #ffffff 45%, #eef1f6 100%);
                    }

                    .fi-layout {
                        --sidebar-width: 240px;
                        --sidebar-collapsed-width: 72px;
                    }

                    .fi-sidebar {
                        background: #ffffff !important;
                        border-inline-start: 1px solid #e4e6eb; /* بدل left/right */
                    }

                    .meta-logo-wrapper {
                        min-height: 72px;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                    }
                    .logo-full { height: 56px; width: auto; display: block; }
                    .logo-icon { height: 42px; width: auto; display: none; }
                    .fi-layout[data-sidebar-collapsed="true"] .logo-full { display: none !important; }
                    .fi-layout[data-sidebar-collapsed="true"] .logo-icon { display: block !important; }

                    .fi-sidebar-item-label,
                    .fi-sidebar-group-label span,
                    .fi-sidebar-item > a span {
                        font-weight: 900 !important;
                        color: #050505;
                    }

                    .fi-sidebar-item > a {
                        margin-inline: 8px;
                        border-radius: 12px;
                        transition: background .2s ease;
                    }

                    .fi-sidebar-item-active > a { background: #283943 !important; }
                    .fi-sidebar-item-active > a span,
                    .fi-sidebar-item-active > a svg { color: #ffffff !important; }

                    .fi-sidebar-item:not(.fi-sidebar-item-active) > a:hover { background: #f2f3f5; }

                    .fi-section {
                        border-radius: 16px !important;
                        box-shadow: 0 8px 24px rgba(0,0,0,.04) !important;
                        border: 1px solid #eef2f7 !important;
                    }

                    .fi-section-header {
                        font-weight: 800 !important;
                        font-size: 15px;
                        color: #1f2937;
                    }

                    .fi-badge {
                        border-radius: 999px !important;
                        padding: 6px 14px !important;
                        font-weight: 700;
                    }
                </style>

                <script>
                    // ✅ تأكيد dir/lang حتى لو حصل override من مكان تاني
                    document.documentElement.setAttribute("dir", "rtl");
                    document.documentElement.setAttribute("lang", "ar");
                </script>
            '))

            // ✅ NAV GROUPS (اختياري زي اللي كنت عامله)
            ->navigationGroups([
                NavigationGroup::make()->label('إدارة العمليات')->collapsible(false),
                NavigationGroup::make()->label('إدارة الشحنات')->collapsible(false),
                NavigationGroup::make()->label('الإعدادات')->icon('heroicon-o-cog-6-tooth')->collapsed(),
            ])

            // ✅ اكتشاف الريسورسز الحالية بتاعتك (Admin)
            ->discoverResources(
                in: app_path('Filament/Admin/Resources'),
                for: 'App\\Filament\\Admin\\Resources'
            )
            ->discoverPages(
                in: app_path('Filament/Admin/Pages'),
                for: 'App\\Filament\\Admin\\Pages'
            )
            ->discoverWidgets(
                in: app_path('Filament/Admin/Widgets'),
                for: 'App\\Filament\\Admin\\Widgets'
            )

            // ✅ خلي Dashboard يظهر في السايدبار
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

                // ✅ منع الدخول إلا للـ admin
                'panel.role:admin',
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
