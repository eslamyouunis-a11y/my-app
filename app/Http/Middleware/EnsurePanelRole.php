<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePanelRole
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
        // السماح بصفحات login بدون قيود
        if ($request->is('*login')) {
            return $next($request);
        }

        $user = $request->user();

        // لو مش مسجل دخول -> login الخاص بالبانل
        if (! $user) {
            $panel = $request->segment(1); // admin|branch|merchant|courier
            return redirect("/{$panel}/login");
        }

        // لو الدور مش مطابق
        if (! $user->hasRole($role)) {

            if ($user->hasRole('admin')) {
                return redirect('/admin');
            }

            if ($user->hasRole('branch')) {
                return redirect('/branch');
            }

            if ($user->hasRole('merchant')) {
                return redirect('/merchant');
            }

            if ($user->hasRole('courier')) {
                return redirect('/courier');
            }

            return redirect('/');
        }

        return $next($request);
    }
}
