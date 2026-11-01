<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class RecordBusinessAudit
{
    private const SENSITIVE_KEYS = [
        'password', 'password_confirmation', 'current_password', 'token',
        'access_token', 'refresh_token', 'secret', 'authorization', 'pin',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (!in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return $response;
        }

        try {
            if (!Schema::hasTable('business_audit_logs')) {
                return $response;
            }

            $routeName = $request->route()?->getName();
            $user = $request->user();

            DB::table('business_audit_logs')->insert([
                'id' => (string) Str::uuid(),
                'user_id' => $user?->getAuthIdentifier(),
                'user_name' => $user->name ?? $user->username ?? $user->email ?? null,
                'app_type' => 'backoffice',
                'module' => $this->moduleName($routeName, $request->path()),
                'action' => $this->actionName($request->method(), $routeName),
                'route_name' => $routeName,
                'url' => $request->path(),
                'method' => $request->method(),
                'ip_address' => $request->ip(),
                'user_agent' => Str::limit((string) $request->userAgent(), 1000, ''),
                'response_status' => $response->getStatusCode(),
                'request_data' => json_encode($this->sanitize($request->except(['_token', '_method']))),
                'context' => json_encode([
                    'route_parameters' => $request->route()?->parameters() ?? [],
                    'successful' => $response->getStatusCode() < 400,
                ]),
                'occurred_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable $exception) {
            Log::warning('Business audit log gagal disimpan.', [
                'message' => $exception->getMessage(),
                'route' => $request->route()?->getName(),
            ]);
        }

        return $response;
    }

    private function sanitize(array $data): array
    {
        foreach ($data as $key => $value) {
            if (in_array(strtolower((string) $key), self::SENSITIVE_KEYS, true)) {
                $data[$key] = '[DISENSOR]';
            } elseif (is_array($value)) {
                $data[$key] = $this->sanitize($value);
            } elseif (is_string($value)) {
                $data[$key] = Str::limit($value, 2000, '…');
            }
        }

        return $data;
    }

    private function moduleName(?string $routeName, string $path): string
    {
        $source = $routeName ?: str_replace('/', '.', $path);
        $parts = array_values(array_filter(explode('.', $source)));

        return Str::headline(implode(' ', array_slice($parts, 0, 2))) ?: 'Aplikasi';
    }

    private function actionName(string $method, ?string $routeName): string
    {
        $last = $routeName ? Str::afterLast($routeName, '.') : '';

        return match (true) {
            in_array($last, ['approve', 'approved'], true) => 'approve',
            in_array($last, ['reject', 'rejected'], true) => 'reject',
            $method === 'POST' => 'create',
            in_array($method, ['PUT', 'PATCH'], true) => 'update',
            $method === 'DELETE' => 'delete',
            default => strtolower($method),
        };
    }
}
