<?php

namespace App\Services\Sync;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Builds the authoritative, branch-scoped query used by both pull and manifest. */
class SyncPullQuery
{
    /** @return array<int, string> */
    public function tables(): array
    {
        return array_values(array_unique(array_merge(
            config('sync.pull', []),
            array_keys(config('sync.pull_scoped', []))
        )));
    }

    public function ownerId(?string $branchId): ?int
    {
        if (! $branchId || ! Schema::hasTable('branches')) {
            return null;
        }

        $ownerId = DB::table('branches')->where('branch_id', $branchId)->value('user_id');

        return $ownerId === null ? null : (int) $ownerId;
    }

    public function scoped(string $table, ?string $branchId, ?int $ownerId = null): Builder
    {
        $query = DB::table($table);
        $ownerId ??= $this->ownerId($branchId);
        $pullScoped = config('sync.pull_scoped', []);

        if ($table === 'users') {
            $query->where('id', $ownerId ?? -1);
        } elseif ($table === 'sub_users') {
            $query->where('owner_id', $ownerId ?? -1);
        } elseif (isset($pullScoped[$table])) {
            $this->applyStrategy($query, $pullScoped[$table], $ownerId, $branchId);
        } elseif (in_array($table, config('sync.pull_owner_scoped', []), true)) {
            $query->where('user_id', $ownerId ?? -1);
        }

        return $query;
    }

    private function applyStrategy(Builder $query, string $strategy, ?int $ownerId, ?string $branchId): void
    {
        match ($strategy) {
            'branch_party' => $query->where(function ($nested) use ($branchId) {
                $nested->where('from_branch_id', $branchId)->orWhere('to_branch_id', $branchId);
            }),
            'via_parent' => $query->whereIn('transfer_id', function ($nested) use ($branchId) {
                $nested->select('id')->from('stock_transfers')->where(function ($parties) use ($branchId) {
                    $parties->where('from_branch_id', $branchId)->orWhere('to_branch_id', $branchId);
                });
            }),
            'owner_other_branches' => $query->where('user_id', $ownerId ?? -1)
                ->where('snapshot_branch_id', '!=', (string) $branchId),
            default => null,
        };
    }
}
