<?php

namespace App\Ai\Security;

class PiiRedactor
{
    private const REDACTED = '[disembunyikan]';

    /**
     * @param  array<string, mixed>  $result
     * @param  array<string, mixed>  $arguments
     * @return array<string, mixed>
     */
    public function redactToolResult(string $tool, array $result, array $arguments = []): array
    {
        return match ($tool) {
            'master_data' => $this->redactMasterData($result, $arguments),
            'customer_insight' => $this->redactCustomerInsight($result),
            'query_data' => $this->redactQueryData($result, $arguments),
            default => $this->redactGeneric($result),
        };
    }

    /**
     * @param  array<string, mixed>  $result
     * @return array<string, mixed>
     */
    private function redactMasterData(array $result, array $arguments): array
    {
        $entity = (string) ($arguments['entity'] ?? '');
        $isCustomer = $entity === 'customers';
        $isUser = $entity === 'users';

        if (! $isCustomer && ! $isUser) {
            return $this->redactGeneric($result);
        }

        $limit = $isCustomer
            ? max(1, (int) config('assistant.limits.max_customer_rows', 5))
            : max(1, (int) config('assistant.limits.max_pii_rows', 10));

        if (isset($result['rows']) && is_array($result['rows'])) {
            $result['rows'] = array_map(
                fn ($row) => is_array($row)
                    ? $this->redactRow($row, $this->piiContext($isCustomer))
                    : $row,
                array_slice($result['rows'], 0, $limit)
            );
            $result['row_limit'] = $limit;
        }

        $result['pii_redaction'] = true;
        $result['note'] = $isCustomer
            ? 'Data pribadi pelanggan dimasking. Tampilkan hanya pelanggan yang disebut eksplisit; data massal tidak ditampilkan.'
            : 'Data karyawan dimasking dan dibatasi. Jangan tampilkan daftar massal.';

        return $this->redactGeneric($result);
    }

    /**
     * @param  array<string, mixed>  $result
     * @return array<string, mixed>
     */
    private function redactCustomerInsight(array $result): array
    {
        $limit = max(1, (int) config('assistant.limits.max_customer_rows', 5));

        foreach (['customers', 'birthday_customers'] as $key) {
            if (isset($result[$key]) && is_array($result[$key])) {
                $result[$key] = array_map(
                    fn ($row) => is_array($row)
                        ? $this->redactRow($row, $this->piiContext(true))
                        : $row,
                    array_slice($result[$key], 0, $limit)
                );
            }
        }

        $result['pii_redaction'] = true;

        return $this->redactGeneric($result);
    }

    /**
     * @param  array<string, mixed>  $result
     * @param  array<string, mixed>  $arguments
     * @return array<string, mixed>
     */
    private function redactQueryData(array $result, array $arguments): array
    {
        $sql = (string) ($result['sql'] ?? $arguments['sql'] ?? '');
        $touchesCustomers = $this->touchesTable($sql, 'customers');
        $touchesUsers = $this->touchesTable($sql, 'users');

        if (! $touchesCustomers && ! $touchesUsers) {
            return $this->redactGeneric($result);
        }

        $limit = max(1, (int) config('assistant.limits.max_pii_rows', 10));

        if (isset($result['rows']) && is_array($result['rows'])) {
            $result['rows'] = array_map(
                fn ($row) => is_array($row)
                    ? $this->redactRow($row, $this->piiContext($touchesCustomers))
                    : $row,
                array_slice($result['rows'], 0, $limit)
            );
            $result['row_limit'] = $limit;
        }

        $result['pii_redaction'] = true;
        $result['note'] = 'Hasil yang menyentuh data pelanggan/karyawan dimasking dan dibatasi.';

        return $this->redactGeneric($result);
    }

    /**
     * @param  array<string, mixed>  $result
     * @return array<string, mixed>
     */
    private function redactGeneric(array $result): array
    {
        return $this->redactArray($result);
    }

    /**
     * @param  array<mixed>  $data
     * @return array<mixed>
     */
    private function redactArray(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->redactArray($value);

                continue;
            }

            if (! is_string($value)) {
                continue;
            }

            if (is_string($key) && $this->isSensitiveKey($key)) {
                $data[$key] = '[REDACTED]';

                continue;
            }

            $data[$key] = $this->redactText($value);
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array{mask_names: bool, mask_address: bool}  $context
     * @return array<string, mixed>
     */
    public function redactRow(array $row, array $context = []): array
    {
        $maskNames = (bool) ($context['mask_names'] ?? false);
        $maskAddress = (bool) ($context['mask_address'] ?? false);

        foreach ($row as $key => $value) {
            $normalized = $this->normalizeKey((string) $key);

            if ($this->isSensitiveKey($normalized)) {
                $row[$key] = '[REDACTED]';

                continue;
            }

            if ($value === null) {
                continue;
            }

            if (is_array($value)) {
                $row[$key] = $this->redactRow($value, $context);

                continue;
            }

            if (! is_string($value)) {
                continue;
            }

            if ($this->isEmailKey($normalized)) {
                $row[$key] = $this->maskEmail($value);

                continue;
            }

            if ($this->isPhoneKey($normalized)) {
                $row[$key] = $this->maskPhone($value);

                continue;
            }

            if ($this->isBirthKey($normalized)) {
                $row[$key] = self::REDACTED;

                continue;
            }

            if ($maskAddress && $this->isAddressKey($normalized)) {
                $row[$key] = self::REDACTED;

                continue;
            }

            if ($this->shouldMaskName($normalized, $maskNames)) {
                $row[$key] = $this->maskName($value);

                continue;
            }

            $row[$key] = $this->redactText($value);
        }

        return $row;
    }

    public function redactText(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        $value = preg_replace_callback(
            '/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/iu',
            fn (array $match): string => (string) $this->maskEmail($match[0]),
            $value
        ) ?? $value;

        $value = preg_replace_callback(
            '/(?<!\d)(?:\+?62|0)8[1-9][0-9]{6,11}(?!\d)/',
            fn (array $match): string => (string) $this->maskPhone($match[0]),
            $value
        ) ?? $value;

        return preg_replace('/\b\d{16}\b/', '[NIK disembunyikan]', $value) ?? $value;
    }

    public function maskEmail(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return $value;
        }

        $value = trim($value);

        if (str_contains($value, '*') || $value === self::REDACTED) {
            return $value;
        }

        if (! str_contains($value, '@')) {
            return self::REDACTED;
        }

        [$local, $domain] = explode('@', $value, 2);
        $local = $local === '' ? '*' : mb_substr($local, 0, 1).str_repeat('*', 3);

        return $local.'@'.$domain;
    }

    public function maskPhone(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return $value;
        }

        $value = trim($value);

        if (str_contains($value, '*') || $value === self::REDACTED) {
            return $value;
        }

        $digits = preg_replace('/\D+/', '', $value) ?? '';

        if (strlen($digits) < 6) {
            return self::REDACTED;
        }

        $prefixLength = str_starts_with($digits, '62') ? 2 : 2;
        $maskedLength = max(3, strlen($digits) - $prefixLength - 4);

        return substr($digits, 0, $prefixLength)
            .str_repeat('*', $maskedLength)
            .substr($digits, -4);
    }

    public function maskName(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return $value;
        }

        $value = trim($value);

        if (str_contains($value, '*') || $value === self::REDACTED) {
            return $value;
        }

        $words = preg_split('/\s+/u', $value) ?: [];

        return implode(' ', array_map(function (string $word): string {
            $letters = preg_replace('/[^\p{L}]/u', '', $word) ?? '';

            if ($letters === '') {
                return $word;
            }

            return mb_substr($letters, 0, 1).'***';
        }, $words));
    }

    /**
     * @param  array<string, mixed>  $arguments
     * @return array<string, mixed>
     */
    public function redactArguments(string $tool, array $arguments): array
    {
        $entity = (string) ($arguments['entity'] ?? '');

        foreach ($arguments as $key => $value) {
            if (is_array($value)) {
                $arguments[$key] = $this->redactArguments($tool, $value);

                continue;
            }

            if (! is_string($value)) {
                continue;
            }

            if ($this->isSensitiveKey((string) $key)) {
                $arguments[$key] = self::REDACTED;

                continue;
            }

            if ($tool === 'master_data' && in_array($entity, ['customers', 'users'], true) && $key === 'search') {
                $arguments[$key] = '[nilai pencarian disembunyikan]';

                continue;
            }

            if ($tool === 'query_data' && $key === 'sql') {
                $arguments[$key] = preg_replace("/'(?:''|[^'])*'/", "'?'", $value) ?? '[SQL disembunyikan]';

                continue;
            }

            $arguments[$key] = $this->redactText($value);
        }

        return $arguments;
    }

    /**
     * @return array{mask_names: bool, mask_address: bool}
     */
    private function piiContext(bool $customers): array
    {
        return [
            'mask_names' => (bool) config('assistant.security.mask_customer_names', true),
            'mask_address' => $customers,
        ];
    }

    private function touchesTable(string $sql, string $table): bool
    {
        return (bool) preg_match(
            '/\b(?:from|join)\s+(?:"?[\w]+"?\.)?"?'.preg_quote($table, '/').'"?\b/i',
            $sql
        );
    }

    private function normalizeKey(string $key): string
    {
        return strtolower(preg_replace('/[^a-z0-9]+/i', '_', $key) ?? $key);
    }

    private function isSensitiveKey(string $key): bool
    {
        $normalized = $this->normalizeKey($key);

        foreach ((array) config('assistant.sensitive_columns', []) as $needle) {
            if ($needle !== '' && str_contains($normalized, strtolower((string) $needle))) {
                return true;
            }
        }

        foreach (['password', 'remember_token', 'access_token', 'refresh_token', 'private_key', 'api_key', 'secret'] as $needle) {
            if (str_contains($normalized, $needle)) {
                return true;
            }
        }

        return false;
    }

    private function isEmailKey(string $key): bool
    {
        return str_contains($key, 'email') || str_contains($key, 'surel');
    }

    private function isPhoneKey(string $key): bool
    {
        foreach (['wa_number', 'phone', 'telepon', 'telp', 'no_hp', 'nomor_hp', 'mobile', 'whatsapp', 'handphone', 'kontak'] as $needle) {
            if (str_contains($key, $needle)) {
                return true;
            }
        }

        return false;
    }

    private function isBirthKey(string $key): bool
    {
        foreach (['date_of_birth', 'birth_date', 'tanggal_lahir', 'tgl_lahir', 'dob'] as $needle) {
            if (str_contains($key, $needle)) {
                return true;
            }
        }

        return false;
    }

    private function isAddressKey(string $key): bool
    {
        foreach (['address', 'alamat', 'street', 'postal', 'kode_pos', 'rt_rw'] as $needle) {
            if (str_contains($key, $needle)) {
                return true;
            }
        }

        return false;
    }

    private function shouldMaskName(string $key, bool $maskNames): bool
    {
        $customerName = (str_contains($key, 'customer') || str_contains($key, 'pelanggan')
            || str_contains($key, 'employee') || str_contains($key, 'karyawan')
            || str_contains($key, 'user'))
            && $this->isNameKey($key);

        if ($customerName) {
            return true;
        }

        if (! $maskNames) {
            return false;
        }

        if (! $this->isNameKey($key)) {
            return false;
        }

        foreach ([
            'product', 'store', 'warehouse', 'category', 'brand', 'supplier', 'courier',
            'partner', 'unit', 'membership', 'province', 'regency', 'district', 'village',
            'location', 'status', 'payment', 'variant', 'item', 'company', 'channel',
            'bank', 'method', 'type', 'group',
        ] as $blocked) {
            if (str_contains($key, $blocked)) {
                return false;
            }
        }

        return true;
    }

    private function isNameKey(string $key): bool
    {
        return $key === 'name'
            || $key === 'nama'
            || str_ends_with($key, '_name')
            || str_ends_with($key, '_nama')
            || str_contains($key, 'full_name')
            || str_contains($key, 'nama_lengkap');
    }
}
