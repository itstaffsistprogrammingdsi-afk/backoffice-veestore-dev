<?php

namespace App\Ai\Mcp\Tools;

use App\Ai\Mcp\AssistantTool;
use Illuminate\Support\Facades\DB;
use Throwable;

class QueryDataTool extends AssistantTool
{
    private const CONNECTIONS = [
        'operational' => 'pgsql',
        'backoffice' => 'immostore_backoffice',
    ];

    private const FORBIDDEN = [
        'insert', 'update', 'delete', 'merge', 'drop', 'alter', 'create', 'truncate',
        'grant', 'revoke', 'copy', 'call', 'execute', 'prepare', 'deallocate',
        'vacuum', 'analyze', 'refresh', 'reindex', 'listen', 'notify', 'unlisten',
        'begin', 'commit', 'rollback', 'savepoint', 'release', 'set', 'reset',
        'show', 'do', 'into', 'nextval', 'setval', 'dblink', 'pg_sleep',
        'pg_terminate_backend', 'pg_cancel_backend', 'pg_read_file', 'pg_read_binary_file',
        'lo_import', 'lo_export', 'current_setting', 'set_config',
        'information_schema', 'pg_catalog', 'for update', 'for share', 'for key share',
        'for no key update',
    ];

    public function name(): string
    {
        return 'query_data';
    }

    public function description(): string
    {
        return 'Jalankan satu query SELECT read-only ke database VeeStore. Gunakan data_schema lebih dulu untuk memastikan nama tabel dan kolom. Hasil dibatasi maksimal 100 baris, kolom sensitif disamarkan, dan query dibatasi 8 detik.';
    }

    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'sql' => [
                    'type' => 'string',
                    'description' => 'Satu statement SELECT atau WITH. Tanpa titik koma, tanpa komentar, tanpa operasi tulis.',
                ],
                'connection' => [
                    'type' => 'string',
                    'enum' => ['operational', 'backoffice'],
                    'default' => 'operational',
                ],
                'limit' => [
                    'type' => 'integer',
                    'default' => 50,
                    'min' => 1,
                    'max' => 100,
                ],
            ],
            'required' => ['sql'],
        ];
    }

    protected function execute(array $arguments): array
    {
        $sql = trim((string) ($arguments['sql'] ?? ''));
        $connectionKey = $arguments['connection'] ?? 'operational';
        $connectionKey = array_key_exists($connectionKey, self::CONNECTIONS) ? $connectionKey : 'operational';
        $limit = max(1, min((int) ($arguments['limit'] ?? 50), (int) config('assistant.limits.max_rows', 100)));
        $validator = $this->validateSql($sql);

        if ($validator !== null) {
            return ['error' => $validator, 'sql' => $sql];
        }

        $sql = rtrim($sql, " \t\n\r\0\x0B;");
        $connection = DB::connection(self::CONNECTIONS[$connectionKey]);
        $timeout = (int) config('assistant.limits.statement_timeout_ms', 8000);

        try {
            $rows = $connection->transaction(function () use ($connection, $sql, $limit, $timeout) {
                try {
                    $connection->statement('SET TRANSACTION READ ONLY');
                } catch (Throwable) {
                    // Outer transactions in tests may already be past their first statement.
                }

                $connection->statement('SET LOCAL statement_timeout = '.$timeout);

                return $connection->select(
                    'SELECT * FROM ('.$sql.') AS vee_query LIMIT '.($limit + 1)
                );
            });
        } catch (Throwable $exception) {
            return [
                'error' => 'Query gagal dijalankan: '.$exception->getMessage(),
                'sql' => $sql,
            ];
        }

        $truncated = count($rows) > $limit;
        $rows = array_slice($rows, 0, $limit);
        $data = array_map(fn ($row) => $this->redact((array) $row), $rows);

        return [
            'connection' => $connectionKey,
            'sql' => $sql,
            'row_count' => count($data),
            'truncated' => $truncated,
            'limit' => $limit,
            'columns' => $data === [] ? [] : array_keys($data[0]),
            'rows' => $data,
            'note' => $truncated
                ? 'Hasil dipotong pada batas baris. Gunakan agregasi atau filter yang lebih spesifik.'
                : null,
        ];
    }

    private function validateSql(string $sql): ?string
    {
        if ($sql === '') {
            return 'SQL wajib diisi.';
        }

        if (strlen($sql) > 6000) {
            return 'SQL terlalu panjang. Maksimal 6000 karakter.';
        }

        if (str_contains($sql, ';')) {
            return 'Hanya satu statement SELECT yang diizinkan, tanpa titik koma.';
        }

        if (str_contains($sql, '--') || str_contains($sql, '/*') || str_contains($sql, '*/') || str_contains($sql, '$$')) {
            return 'Komentar dan dollar quoting tidak diizinkan.';
        }

        if (! preg_match('/^\s*(select|with)\b/i', $sql)) {
            return 'Query harus dimulai dengan SELECT atau WITH.';
        }

        $normalized = strtolower(preg_replace('/\s+/', ' ', $sql) ?? $sql);

        if (preg_match('/\bpg_[a-z0-9_]+/', $normalized)) {
            return 'Fungsi dan katalog internal PostgreSQL tidak diizinkan.';
        }

        foreach (self::FORBIDDEN as $keyword) {
            if (preg_match('/\b'.preg_quote($keyword, '/').'\b/', $normalized)) {
                return "Kata kunci [{$keyword}] tidak diizinkan pada query baca-only.";
            }
        }

        if (str_contains($normalized, 'recursive')) {
            return 'CTE rekursif tidak diizinkan.';
        }

        foreach (config('assistant.blocked_tables', []) as $table) {
            if (preg_match('/\b'.preg_quote(strtolower($table), '/').'\b/', $normalized)) {
                return "Tabel [{$table}] tidak dapat dibaca oleh asisten.";
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function redact(array $row): array
    {
        $sensitive = config('assistant.sensitive_columns', []);

        foreach ($row as $key => $value) {
            $normalized = strtolower((string) $key);

            foreach ($sensitive as $needle) {
                if (str_contains($normalized, $needle)) {
                    $row[$key] = '[REDACTED]';

                    continue 2;
                }
            }

            if (is_string($value)) {
                $row[$key] = $this->redactString($value);
            }
        }

        return $row;
    }

    private function redactString(string $value): string
    {
        $trimmed = trim($value);

        if (strlen($trimmed) > 1 && in_array($trimmed[0], ['{', '['], true)) {
            $decoded = json_decode($trimmed, true);

            if (is_array($decoded)) {
                $value = json_encode($this->redact($decoded), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: $value;
            }
        }

        return strlen($value) > 4000 ? substr($value, 0, 4000).'...' : $value;
    }
}
