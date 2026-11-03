<?php

namespace App\Ai\Mcp\Tools;

use App\Ai\Mcp\AssistantTool;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DataSchemaTool extends AssistantTool
{
    private const CONNECTIONS = [
        'operational' => 'pgsql',
        'backoffice' => 'immostore_backoffice',
    ];

    public function name(): string
    {
        return 'data_schema';
    }

    public function description(): string
    {
        return 'Lihat skema database VeeStore: daftar tabel, kolom, tipe data, foreign key, nilai enum, dan jumlah baris. Selalu cek skema sebelum menulis query_data agar nama kolom benar.';
    }

    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'connection' => [
                    'type' => 'string',
                    'enum' => ['operational', 'backoffice', 'all'],
                    'default' => 'operational',
                ],
                'table' => [
                    'type' => 'string',
                    'description' => 'Nama tabel yang ingin dijelaskan lengkap.',
                ],
                'search' => [
                    'type' => 'string',
                    'description' => 'Cari tabel atau kolom berdasarkan kata kunci.',
                ],
                'include_counts' => [
                    'type' => 'boolean',
                    'default' => false,
                    'description' => 'Hitung jumlah baris setiap tabel. Bisa lambat untuk tabel besar.',
                ],
            ],
        ];
    }

    protected function execute(array $arguments): array
    {
        $connectionKey = $arguments['connection'] ?? 'operational';
        $connectionKey = in_array($connectionKey, ['operational', 'backoffice', 'all'], true)
            ? $connectionKey
            : 'operational';

        $connections = $connectionKey === 'all'
            ? array_keys(self::CONNECTIONS)
            : [$connectionKey];

        $table = trim((string) ($arguments['table'] ?? ''));
        $search = strtolower(trim((string) ($arguments['search'] ?? '')));
        $includeCounts = filter_var($arguments['include_counts'] ?? false, FILTER_VALIDATE_BOOLEAN);

        if ($table !== '') {
            return $this->describeTable($table, $connections, $includeCounts);
        }

        $tables = [];

        foreach ($connections as $key) {
            $tables = array_merge($tables, $this->tableList($key, $search, $includeCounts));
        }

        return [
            'connection' => $connectionKey,
            'search' => $search,
            'table_count' => count($tables),
            'tables' => $tables,
            'hint' => 'Panggil lagi dengan parameter table untuk melihat kolom dan relasi secara lengkap.',
        ];
    }

    /**
     * @param  array<int, string>  $connections
     */
    private function describeTable(string $table, array $connections, bool $includeCounts): array
    {
        if ($this->isBlocked($table)) {
            return ['error' => "Tabel [{$table}] tidak dapat dibaca oleh asisten."];
        }

        foreach ($connections as $connectionKey) {
            $db = DB::connection(self::CONNECTIONS[$connectionKey]);

            if (! $this->hasTable($db, $table)) {
                continue;
            }

            $columns = $db->select(
                'select column_name, data_type, udt_name, is_nullable, column_default
                 from information_schema.columns
                 where table_schema = current_schema() and table_name = ?
                 order by ordinal_position',
                [$table]
            );

            $primary = $db->select(
                'select kcu.column_name
                 from information_schema.table_constraints tc
                 join information_schema.key_column_usage kcu
                   on kcu.constraint_name = tc.constraint_name
                  and kcu.table_schema = tc.table_schema
                 where tc.table_schema = current_schema()
                   and tc.table_name = ?
                   and tc.constraint_type = ?',
                [$table, 'PRIMARY KEY']
            );

            $foreign = $db->select(
                'select kcu.column_name, ccu.table_name as foreign_table, ccu.column_name as foreign_column
                 from information_schema.table_constraints tc
                 join information_schema.key_column_usage kcu
                   on kcu.constraint_name = tc.constraint_name
                  and kcu.table_schema = tc.table_schema
                 join information_schema.constraint_column_usage ccu
                   on ccu.constraint_name = tc.constraint_name
                  and ccu.table_schema = tc.table_schema
                 where tc.table_schema = current_schema()
                   and tc.table_name = ?
                   and tc.constraint_type = ?',
                [$table, 'FOREIGN KEY']
            );

            return [
                'connection' => $connectionKey,
                'table' => $table,
                'description' => config("assistant.table_descriptions.{$table}"),
                'row_count' => $includeCounts ? (int) $db->table($table)->count() : null,
                'primary_key' => array_column($primary, 'column_name'),
                'columns' => array_map(fn ($column) => [
                    'name' => $column->column_name,
                    'type' => $column->data_type,
                    'postgres_type' => $column->udt_name,
                    'nullable' => $column->is_nullable === 'YES',
                    'default' => $this->truncate($column->column_default, 120),
                    'enum_values' => $this->enumValues($db, $column->udt_name),
                    'sensitive' => $this->isSensitive((string) $column->column_name),
                ], $columns),
                'foreign_keys' => array_map(fn ($row) => [
                    'column' => $row->column_name,
                    'references_table' => $row->foreign_table,
                    'references_column' => $row->foreign_column,
                ], $foreign),
            ];
        }

        return ['error' => "Tabel [{$table}] tidak ditemukan pada koneksi {$connections[0]}."];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function tableList(string $connectionKey, string $search, bool $includeCounts): array
    {
        $db = DB::connection(self::CONNECTIONS[$connectionKey]);
        $blocked = config('assistant.blocked_tables', []);

        $rows = Cache::remember(
            "assistant:schema:tables:{$connectionKey}",
            now()->addMinutes(10),
            fn () => $db->select(
                "select t.table_name,
                        (select count(*) from information_schema.columns c
                          where c.table_schema = t.table_schema and c.table_name = t.table_name) as column_count
                 from information_schema.tables t
                 where t.table_schema = current_schema() and t.table_type = 'BASE TABLE'
                 order by t.table_name"
            )
        );

        $tables = [];

        foreach ($rows as $row) {
            if (in_array($row->table_name, $blocked, true)) {
                continue;
            }

            $description = config("assistant.table_descriptions.{$row->table_name}");
            $haystack = strtolower($row->table_name.' '.$description);

            if ($search !== '' && ! str_contains($haystack, $search)) {
                continue;
            }

            $tables[] = [
                'table' => $row->table_name,
                'connection' => $connectionKey,
                'columns' => (int) $row->column_count,
                'description' => $description,
                'row_count' => $includeCounts ? (int) $db->table($row->table_name)->count() : null,
            ];
        }

        return $tables;
    }

    private function hasTable($db, string $table): bool
    {
        return (bool) $db->selectOne(
            'select 1 from information_schema.tables where table_schema = current_schema() and table_name = ? limit 1',
            [$table]
        );
    }

    /**
     * @return list<string>|null
     */
    private function enumValues($db, string $type): ?array
    {
        if ($type === '' || $type === 'varchar' || $type === 'text' || $type === 'uuid') {
            return null;
        }

        $rows = $db->select(
            'select e.enumlabel
             from pg_type t
             join pg_enum e on e.enumtypid = t.oid
             where t.typname = ?
             order by e.enumsortorder',
            [$type]
        );

        return $rows === [] ? null : array_column($rows, 'enumlabel');
    }

    private function isBlocked(string $table): bool
    {
        return in_array($table, config('assistant.blocked_tables', []), true);
    }

    private function isSensitive(string $column): bool
    {
        $column = strtolower($column);

        foreach (config('assistant.sensitive_columns', []) as $needle) {
            if (str_contains($column, $needle)) {
                return true;
            }
        }

        return false;
    }

    private function truncate(?string $value, int $length): ?string
    {
        if ($value === null) {
            return null;
        }

        return strlen($value) > $length ? substr($value, 0, $length).'...' : $value;
    }
}
