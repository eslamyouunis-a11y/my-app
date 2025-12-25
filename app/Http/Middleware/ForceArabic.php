<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Number;
use Symfony\Component\HttpFoundation\Response;

class ForceArabic
{
    public function handle(Request $request, Closure $next): Response
    {
        // تعيين اللغة العربية مع نظام الأرقام اللاتيني
        App::setLocale('ar');
        Number::useLocale('ar-u-nu-latn');

        return $next($request);
    }
}
