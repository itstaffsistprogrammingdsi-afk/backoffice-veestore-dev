<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ClampPerPage
{
    private const DEFAULT_PER_PAGE = 20;

    private const MAX_PER_PAGE = 100;

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->has('per_page')) {
            $value = $request->input('per_page');
            $perPage = is_scalar($value)
                ? filter_var($value, FILTER_VALIDATE_INT)
                : false;

            if ($perPage === false) {
                $perPage = self::DEFAULT_PER_PAGE;
            }

            $perPage = min(self::MAX_PER_PAGE, max(1, $perPage));

            if ($request->query->has('per_page')) {
                $request->query->set('per_page', $perPage);
            }

            if ($request->request->has('per_page')) {
                $request->request->set('per_page', $perPage);
            }

            if ($request->isJson() && $request->json()->has('per_page')) {
                $request->json()->set('per_page', $perPage);
            }
        }

        return $next($request);
    }
}
