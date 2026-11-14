<?php

namespace App\Ai\Security;

use App\Models\User;

class AssistantAccessPolicy
{
    private const SERVICE_BINDING = 'assistant.service_request';

    public function markServiceRequest(): void
    {
        app()->instance(self::SERVICE_BINDING, true);
    }

    public function markUserRequest(): void
    {
        app()->instance(self::SERVICE_BINDING, false);
    }

    public function isService(): bool
    {
        if ($this->user() !== null) {
            return false;
        }

        if (! app()->bound(self::SERVICE_BINDING)) {
            return true;
        }

        return (bool) app(self::SERVICE_BINDING);
    }

    public function user(): ?User
    {
        $user = auth()->user();

        return $user instanceof User ? $user : null;
    }

    /**
     * @return list<string>
     */
    public function roles(?User $user = null): array
    {
        $user ??= $this->user();

        if ($user === null) {
            return [];
        }

        return $user->getRoleNames()
            ->map(fn ($role): string => (string) $role)
            ->values()
            ->all();
    }

    public function hasAnyRole(array $roles, ?User $user = null): bool
    {
        $user ??= $this->user();

        if ($user === null) {
            return false;
        }

        return $user->hasAnyRole($roles);
    }

    public function can(string $permission, ?User $user = null): bool
    {
        $user ??= $this->user();

        if ($user === null) {
            return false;
        }

        return $user->can($permission);
    }

    public function canUseAssistant(?User $user = null): bool
    {
        if ($this->isService()) {
            return true;
        }

        $user ??= $this->user();

        if ($user === null) {
            return false;
        }

        return $user->can('view ai-analytics') || $user->can('view ai-assistant');
    }

    public function canViewCustomerPii(?User $user = null): bool
    {
        if ($this->isService()) {
            return true;
        }

        $user ??= $this->user();

        if ($user === null) {
            return false;
        }

        if ($this->hasAnyRole((array) config('assistant.security.pii_view_roles', []), $user)) {
            return true;
        }

        foreach ((array) config('assistant.security.pii_view_permissions', []) as $permission) {
            if ($user->can($permission)) {
                return true;
            }
        }

        return false;
    }

    public function canViewFullPii(?User $user = null): bool
    {
        if (! (bool) config('assistant.security.allow_full_pii', false)) {
            return false;
        }

        $user ??= $this->user();

        return $user !== null
            && $this->canViewCustomerPii($user)
            && $this->hasAnyRole((array) config('assistant.security.privileged_roles', []), $user);
    }

    public function canViewEmployeeData(?User $user = null): bool
    {
        if ($this->isService()) {
            return true;
        }

        $user ??= $this->user();

        if ($user === null) {
            return false;
        }

        if ($this->hasAnyRole((array) config('assistant.security.employee_view_roles', []), $user)) {
            return true;
        }

        foreach ((array) config('assistant.security.employee_view_permissions', []) as $permission) {
            if ($user->can($permission)) {
                return true;
            }
        }

        return false;
    }

    public function canViewSchema(?User $user = null): bool
    {
        if ($this->isService()) {
            return true;
        }

        $user ??= $this->user();

        return $user !== null
            && $this->hasAnyRole((array) config('assistant.security.schema_view_roles', []), $user);
    }

    public function canUseTool(string $tool, ?User $user = null): bool
    {
        if ($this->isService()) {
            return true;
        }

        $user ??= $this->user();

        if ($user === null || ! $this->canUseAssistant($user)) {
            return false;
        }

        return match ($tool) {
            'data_schema' => $this->canViewSchema($user),
            default => true,
        };
    }

    /**
     * @param  array<string, mixed>  $arguments
     */
    public function authorizeTool(string $tool, array $arguments, ?User $user = null): ?string
    {
        if ($this->isService()) {
            return null;
        }

        $user ??= $this->user();

        if ($user === null) {
            return 'Anda belum terautentikasi. Silakan masuk kembali untuk memakai VEE.';
        }

        if (! $this->canUseAssistant($user)) {
            return 'Anda tidak berwenang memakai asisten data.';
        }

        if (! $this->canUseTool($tool, $user)) {
            return match ($tool) {
                'data_schema' => 'Skema database hanya dapat dibuka oleh peran administrasi yang berwenang.',
                default => 'Anda tidak berwenang memakai kemampuan ini.',
            };
        }

        if ($tool === 'master_data') {
            $entity = (string) ($arguments['entity'] ?? '');
            $search = trim((string) ($arguments['search'] ?? ''));

            if ($entity === 'customers' && $search !== '' && ! $this->canViewCustomerPii($user)) {
                return 'Anda tidak berwenang melihat detail pelanggan. VEE hanya dapat menampilkan ringkasan agregat.';
            }

            if ($entity === 'users' && ! $this->canViewEmployeeData($user)) {
                return 'Data pengguna dan karyawan hanya dapat dibuka oleh peran yang berwenang.';
            }
        }

        return null;
    }
}
