<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Auth\Access\AuthorizationException;
use Spatie\Permission\Exceptions\UnauthorizedException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            // You can add Sentry/Bugsnag/etc here if needed
        });
    }

    /**
     * Render an exception into an HTTP response.
     */
    public function render($request, Throwable $e)
    {
        // Tangani semua kasus "tidak punya izin" → 403
        if (
            $e instanceof UnauthorizedException ||               // Spatie\Permission
            $e instanceof AuthorizationException ||               // Gate/Policy Laravel
            $e instanceof AccessDeniedHttpException ||            // Symfony 403
            ($e instanceof HttpExceptionInterface && $e->getStatusCode() === 403)
        ) {
            $message = 'Anda tidak memiliki izin untuk mengakses halaman ini.';

            // Jika request mengharapkan JSON (API/AJAX)
            if ($request->expectsJson() || $request->is('*api*')) {
                return response()->json([
                    'message' => $message,
                ], 403);
            }

            // Render ke view errors/403.blade.php
            return response()->view('errors.403', [
                'message' => $message,
            ], 403);
        }

        // Fallback ke default handler
        return parent::render($request, $e);
    }
}
