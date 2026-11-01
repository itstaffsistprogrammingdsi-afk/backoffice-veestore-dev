<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard-analytics');
        }

        return view('content.authentications.auth-login-basic');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'login'    => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $loginInput = trim($credentials['login']);
        $password   = $credentials['password'];

        $loginField = filter_var($loginInput, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        /** @var \App\Models\User|null $user */
        $user = User::query()
            ->where($loginField, $loginInput)
            ->first();

        if (!$user || !Hash::check($password, $user->password)) {
            $this->writeSessionLogSafe(
                request: $request,
                user: $user,
                status: 'failed',
                type: 'login',
                failureReason: 'Kredensial yang diberikan salah.',
                loginInput: $loginInput,
                loginField: $loginField
            );

            return back()
                ->withErrors(['login' => 'Email/Username atau password salah.'])
                ->withInput($request->only('login', 'remember'));
        }

        if (!$user->backoffice_access) {
            $this->writeSessionLogSafe(
                request: $request,
                user: $user,
                status: 'failed',
                type: 'login',
                failureReason: 'Akun tidak memiliki akses ke Back Office.',
                loginInput: $loginInput,
                loginField: $loginField
            );

            return back()
                ->with('error', 'Akun Anda tidak memiliki akses ke Back Office. Silakan hubungi administrator.')
                ->withInput($request->only('login', 'remember'));
        }

        if (!Auth::attempt(
            [$loginField => $loginInput, 'password' => $password],
            $request->boolean('remember')
        )) {
            $this->writeSessionLogSafe(
                request: $request,
                user: $user,
                status: 'failed',
                type: 'login',
                failureReason: 'Gagal masuk. Coba lagi.',
                loginInput: $loginInput,
                loginField: $loginField
            );

            return back()
                ->withErrors(['login' => 'Gagal masuk. Coba lagi.'])
                ->withInput($request->only('login', 'remember'));
        }

        $request->session()->regenerate();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        /** @var \App\Models\User $authed */
        $authed = Auth::user();
        $authed->loadMissing('roles.permissions');

        $roleNames = $authed->getRoleNames()->sort()->values()->all();
        $permissionNames = $authed->getAllPermissions()
            ->pluck('name')
            ->unique()
            ->sort()
            ->values()
            ->all();

        session([
            'user_role_name'   => $roleNames[0] ?? null,
            'user_role_names'  => $roleNames,
            'user_permissions' => $permissionNames,
        ]);

        $sessionLogId = $this->writeSessionLogSafe(
            request: $request,
            user: $authed,
            status: 'success',
            type: 'login',
            failureReason: null,
            loginInput: $loginInput,
            loginField: $loginField
        );

        if ($sessionLogId) {
            $request->session()->put('current_session_log_id', $sessionLogId);
        }

        return redirect()->intended(route('dashboard-analytics'));
    }

    public function showRegister()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard-analytics');
        }

        return view('content.authentications.auth-register-basic');
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name'     => ['required', 'string', 'max:150'],
            'username' => ['required', 'string', 'max:100', 'unique:pgsql.users,username'],
            'email'    => ['required', 'email', 'max:191', 'unique:pgsql.users,email'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $data += [
            'ecommerce_access'  => false,
            'wms_access'        => false,
            'backoffice_access' => false,
            'store_access'      => false,
        ];

        User::create($data);

        return redirect()
            ->route('auth-login-basic')
            ->with('success', 'Registrasi berhasil. Silakan login.')
            ->withInput(['login' => $data['email']]);
    }

    public function logout(Request $request)
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        $now = now();

        $currentSessionLogId = $request->session()->get('current_session_log_id');

        // 1. simpan log logout
        if ($user) {
            $this->writeSessionLogSafe(
                request: $request,
                user: $user,
                status: 'success',
                type: 'logout',
                failureReason: null,
                loginInput: $user->username ?? $user->email,
                loginField: 'username',
                extraDetails: [
                    'action' => 'manual logout',
                ]
            );
        }

        // 2. update log login aktif
        if ($currentSessionLogId) {
            try {
                DB::table('session_logs')
                    ->where('id', $currentSessionLogId)
                    ->update([
                        'last_activity_at' => $now,
                        'expired_at'       => $now,
                    ]);
            } catch (\Throwable $e) {
                Log::error('Gagal update session_logs login aktif saat logout', [
                    'message'        => $e->getMessage(),
                    'session_log_id' => $currentSessionLogId,
                    'user_id'        => $user?->id,
                ]);
            }
        }

        // 3. proses logout tetap jalan
        $request->session()->forget([
            'user_role_name',
            'user_role_names',
            'user_permissions',
            'current_session_log_id',
        ]);

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('auth-login-basic');
    }

    protected function writeSessionLogSafe(
        Request $request,
        ?User $user,
        string $status,
        string $type,
        ?string $failureReason = null,
        ?string $loginInput = null,
        ?string $loginField = null,
        array $extraDetails = []
    ): ?string {
        try {
            return $this->writeSessionLog(
                request: $request,
                user: $user,
                status: $status,
                type: $type,
                failureReason: $failureReason,
                loginInput: $loginInput,
                loginField: $loginField,
                extraDetails: $extraDetails
            );
        } catch (\Throwable $e) {
            Log::error('Gagal simpan session_logs', [
                'message'  => $e->getMessage(),
                'status'   => $status,
                'type'     => $type,
                'user_id'  => $user?->id,
                'login'    => $loginInput,
            ]);

            return null;
        }
    }

    protected function writeSessionLog(
        Request $request,
        ?User $user,
        string $status,
        string $type,
        ?string $failureReason = null,
        ?string $loginInput = null,
        ?string $loginField = null,
        array $extraDetails = []
    ): string {
        $now = now();
        $sessionLifetimeMinutes = (int) config('session.lifetime', 120);
        $sessionId = (string) $request->session()->getId();
        $id = (string) Str::uuid();
        $sessionLogToken = $sessionId . ':' . $type . ':' . $id;

        $details = array_merge([
            'username'    => $user?->username ?? $loginInput,
            'email'       => $user?->email,
            'login_field' => $loginField,
            'remember'    => $request->boolean('remember'),
            'ip_info'     => [
                'ip' => $request->ip(),
            ],
            'gps' => [
                'lat' => null,
                'lon' => null,
            ],
        ], $extraDetails);

        DB::table('session_logs')->insert([
            'id'               => $id,
            'user_id'          => $user?->id,
            'ip_address'       => substr((string) $request->ip(), 0, 100),
            'session_token'    => $sessionLogToken,
            'user_agent'       => (string) $request->userAgent(),
            'device'           => $this->detectDevice($request->userAgent()),
            'platform'         => $this->detectPlatform($request->userAgent()),
            'last_activity_at' => $now,
            'expired_at'       => $type === 'logout'
                ? $now
                : $now->copy()->addMinutes($sessionLifetimeMinutes),
            'status'           => $status,
            'type'             => $type,
            'failure_reason'   => $failureReason,
            'app_type'         => 'backoffice',
            'details'          => json_encode($details, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'logged_at'        => $now,
        ]);

        return $id;
    }

    protected function detectDevice(?string $userAgent): string
    {
        $ua = strtolower($userAgent ?? '');

        if (str_contains($ua, 'tablet') || str_contains($ua, 'ipad')) {
            return 'Tablet';
        }

        if (
            str_contains($ua, 'mobile') ||
            str_contains($ua, 'android') ||
            str_contains($ua, 'iphone')
        ) {
            return 'Mobile';
        }

        return 'Desktop';
    }

    protected function detectPlatform(?string $userAgent): string
    {
        $ua = strtolower($userAgent ?? '');

        return match (true) {
            str_contains($ua, 'windows')   => 'Windows',
            str_contains($ua, 'android')   => 'Android',
            str_contains($ua, 'iphone'),
            str_contains($ua, 'ipad'),
            str_contains($ua, 'ios')       => 'iOS',
            str_contains($ua, 'mac os'),
            str_contains($ua, 'macintosh') => 'macOS',
            str_contains($ua, 'linux')     => 'Linux',
            default                        => 'Unknown',
        };
    }
}
