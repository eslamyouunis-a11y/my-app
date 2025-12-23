<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Filament\Tables\Columns\TextColumn;
use Filament\Infolists\Components\TextEntry;

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
        // ✅ 1. اختصار للجداول (Tables)
        TextColumn::macro('egp', function () {
            /** @var TextColumn $this */ // 👈 السطر ده عشان الـ Error يختفي
            return $this
                ->numeric(decimalPlaces: 0)
                ->suffix(' جنيه')
                ->weight('bold')
                ->color('success')
                ->alignEnd();
        });

        // ✅ 2. اختصار لصفحات العرض (Infolists)
        TextEntry::macro('egp', function () {
            /** @var TextEntry $this */ // 👈 السطر ده عشان الـ Error يختفي
            return $this
                ->numeric(decimalPlaces: 0)
                ->suffix(' جنيه')
                ->weight('bold')
                ->color('success');
        });
    }
}
