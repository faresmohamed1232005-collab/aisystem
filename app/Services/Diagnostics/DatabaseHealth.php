<?php

namespace App\Services\Diagnostics;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class DatabaseHealth
{
    public function report(): array
    {
        $connection = DB::connection();
        $driver = $connection->getDriverName();
        $checks = [
            'connection' => $this->connectionCheck(),
            'integrity' => $this->integrityCheck($driver),
            'foreign_keys' => $this->foreignKeyCheck($driver),
            'schema' => $this->schemaCheck(),
            'owner' => $this->ownerCheck(),
        ];

        return [
            'status' => $this->status($checks),
            'driver' => $driver,
            'checks' => $checks,
        ];
    }

    private function connectionCheck(): array
    {
        try {
            DB::select('SELECT 1');
            return ['status' => 'healthy'];
        } catch (Throwable $e) {
            return ['status' => 'failed', 'error' => $this->safeError($e)];
        }
    }

    private function integrityCheck(string $driver): array
    {
        if ($driver !== 'sqlite') {
            return ['status' => 'not_applicable', 'method' => 'connection'];
        }

        try {
            $result = DB::selectOne('PRAGMA quick_check');
            $value = strtolower((string) current((array) $result));
            return ['status' => $value === 'ok' ? 'healthy' : 'failed', 'result' => $value];
        } catch (Throwable $e) {
            return ['status' => 'failed', 'error' => $this->safeError($e)];
        }
    }

    private function foreignKeyCheck(string $driver): array
    {
        try {
            if ($driver === 'sqlite') {
                $enabled = (int) current((array) DB::selectOne('PRAGMA foreign_keys')) === 1;
                $violations = count(DB::select('PRAGMA foreign_key_check'));

                return [
                    'status' => $enabled && $violations === 0 ? 'healthy' : 'failed',
                    'enabled' => $enabled,
                    'violations' => $violations,
                ];
            }

            if ($driver === 'mysql') {
                $enabled = (int) DB::scalar('SELECT @@FOREIGN_KEY_CHECKS') === 1;
                return ['status' => $enabled ? 'healthy' : 'warning', 'enabled' => $enabled];
            }

            return ['status' => 'not_applicable'];
        } catch (Throwable $e) {
            return ['status' => 'failed', 'error' => $this->safeError($e)];
        }
    }

    private function schemaCheck(): array
    {
        $required = ['migrations', 'users'];
        $missing = array_values(array_filter($required, fn (string $table) => !Schema::hasTable($table)));

        return [
            'status' => $missing === [] ? 'healthy' : 'failed',
            'required_tables' => $required,
            'missing_tables' => $missing,
        ];
    }

    private function ownerCheck(): array
    {
        try {
            if (!Schema::hasTable('users')) {
                return ['status' => 'missing', 'present' => false, 'count' => 0];
            }

            $count = DB::table('users')->count();
            return [
                'status' => $count > 0 ? 'healthy' : 'missing',
                'present' => $count > 0,
                'count' => $count,
            ];
        } catch (Throwable $e) {
            return ['status' => 'failed', 'present' => false, 'error' => $this->safeError($e)];
        }
    }

    private function status(array $checks): string
    {
        $statuses = array_column($checks, 'status');
        if (array_intersect($statuses, ['failed', 'missing'])) {
            return 'unhealthy';
        }

        return in_array('warning', $statuses, true) ? 'warning' : 'healthy';
    }

    private function safeError(Throwable $e): string
    {
        $message = preg_replace('/\b(password|token|secret|key)\s*[=:]\s*[^\s;,]+/i', '$1=[redacted]', $e->getMessage());

        return mb_substr($e::class . ': ' . $message, 0, 500);
    }
}
