<?php

namespace App\Services\Diagnostics;

use App\Support\Runtime;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class DiagnosticsReport
{
    public function __construct(
        private readonly DatabaseHealth $databaseHealth,
        private readonly SyncHealth $syncHealth,
    ) {}

    public function report(): array
    {
        return [
            'generated_at' => now()->toIso8601String(),
            'application' => [
                'name' => (string) config('app.name'),
                'environment' => (string) app()->environment(),
                'version' => (string) config('nativephp.version', config('app.version', 'unknown')),
                'runtime' => Runtime::isDesktop() ? 'desktop' : 'web',
                'php_version' => PHP_VERSION,
                'laravel_version' => app()->version(),
            ],
            'registration' => $this->registration(),
            'database' => $this->databaseHealth->report(),
            'sync' => $this->syncHealth->report(),
        ];
    }

    private function registration(): array
    {
        try {
            $settingsReady = Schema::hasTable('app_settings');
            $values = $settingsReady
                ? DB::table('app_settings')
                    ->whereIn('key', ['branch.id', 'branch.code', 'sync.server_url', 'sync.token'])
                    ->pluck('value', 'key')
                    ->all()
                : [];

            $branchId = config('sync.branch_id') ?: ($values['branch.id'] ?? null);
            $serverUrl = config('sync.server_url') ?: ($values['sync.server_url'] ?? null);
            $tokenPresent = !empty(config('sync.token')) || !empty($values['sync.token']);
            $registered = ($branchId === config('sync.server_branch_id', 'server'))
                || (!empty($branchId) && !empty($serverUrl) && $tokenPresent);

            return [
                'status' => $registered ? 'registered' : 'not_registered',
                'registered' => $registered,
                'branch_id' => $branchId,
                'branch_code' => config('sync.branch_code') ?: ($values['branch.code'] ?? null),
                'server_configured' => !empty($serverUrl),
                'token_present' => $tokenPresent,
            ];
        } catch (Throwable) {
            return [
                'status' => 'unknown',
                'registered' => false,
                'branch_id' => null,
                'branch_code' => null,
                'server_configured' => false,
                'token_present' => false,
            ];
        }
    }
}
