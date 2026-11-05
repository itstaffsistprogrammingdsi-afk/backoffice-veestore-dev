<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->session()->get('locale', config('app.locale', 'id'));
        $locale = in_array($locale, ['id', 'en'], true) ? $locale : 'id';

        app()->setLocale($locale);

        return $next($request);
    }
}
