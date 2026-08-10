<?php

namespace App\Http\Controllers;

use App\Services\Sync\SyncManifestService;
use App\Services\Sync\SyncPullService;
use App\Support\Branch;
use App\Support\Settings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SetupSyncController extends Controller
{
    public function show(SyncPullService $service, SyncManifestService $manifests)
    {
        if (! Branch::isRegistered()) {
            return redirect()->route('setup.show');
        }

        $tables = $service->pullableTables();
        $manifestError = null;
        try {
            $progress = $manifests->initialize($tables);
        } catch (\Throwable $e) {
            $progress = $manifests->progress($tables);
            $manifestError = $e->getMessage();
            Log::warning('Initial sync manifest failed', ['error' => $e->getMessage()]);
        }

        return view('setup-sync', [
            'tables' => $tables,
            'progressRows' => $progress,
            'manifestError' => $manifestError,
            'ownerExists' => \Illuminate\Support\Facades\DB::table('users')->exists(),
            'branchCode' => Settings::get('branch.code', Branch::code()),
        ]);
    }

    public function step(Request $request, SyncPullService $service): JsonResponse
    {
        $data = $request->validate([
            'table' => ['required', 'string'],
        ]);

        try {
            $result = $service->pullStep($data['table']);

            $ownerExists = \Illuminate\Support\Facades\DB::table('users')->exists();

            return response()->json([
                'success' => true,
                'owner_exists' => $ownerExists,
                ...$result,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Initial sync step failed', [
                'table' => $data['table'],
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'تعذّر تنزيل بيانات ' . $data['table'] . '. يمكنك إعادة المحاولة دون فقد ما تم تنزيله.',
            ], 422);
        }
    }
}
