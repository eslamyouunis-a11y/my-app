<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class ForceArabic
{
    public function handle(Request $request, Closure $next)
    {
        // ✅ التغيير هنا: ضفنا "-u-nu-latn"
        // دي معناها: لغة عربية، بس استخدم نظام الأرقام اللاتيني (الإنجليزي)
        App::setLocale('ar-u-nu-latn');

        return $next($request);
    }
}
