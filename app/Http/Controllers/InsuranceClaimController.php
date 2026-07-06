<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\InsuranceClaim;
use App\Models\InsuranceClaimItem;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InsuranceClaimController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->get('status', '');

        $claims = InsuranceClaim::where('user_id', Auth::id())
            ->with('contract:id,name,code')
            ->withCount('items')
            ->when($status, fn($q) => $q->where('status', $status))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('insurance-claims.index', compact('claims', 'status'));
    }

    /**
     * شاشة إنشاء مطالبة: اختيار عقد تأمين ثم عرض فواتير التأمين غير المطالَب بها لتجميعها.
     */
    public function create(Request $request)
    {
        $contracts = Contract::where('user_id', Auth::id())
            ->where('type', 'insurance')
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        $contract    = null;
        $unclaimed   = collect();
        $contractId  = $request->get('contract_id');

        if ($contractId) {
            $contract = Contract::where('user_id', Auth::id())->findOrFail($contractId);
            $unclaimed = $this->unclaimedSales($contract);
        }

        return view('insurance-claims.create', compact('contracts', 'contract', 'unclaimed', 'contractId'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'contract_id' => 'required|integer|exists:contracts,id',
            'sale_ids'    => 'required|array|min:1',
            'sale_ids.*'  => 'integer|exists:sales,id',
            'claim_date'  => 'nullable|date',
            'notes'       => 'nullable|string|max:1000',
        ], [
            'sale_ids.required' => 'اختر فاتورة واحدة على الأقل',
        ]);

        $contract = Contract::where('user_id', Auth::id())->findOrFail($data['contract_id']);

        $claim = DB::transaction(function () use ($contract, $data) {
            // اقفل على الفواتير المؤهّلة فقط (تأمين، لنفس العقد، غير مطالَب بها).
            $sales = Sale::where('user_id', Auth::id())
                ->where('contract_id', $contract->id)
                ->where('payment_method', 'insurance')
                ->whereIn('id', $data['sale_ids'])
                ->whereNotExists(function ($q) {
                    $q->select(DB::raw(1))
                        ->from('insurance_claim_items')
                        ->whereColumn('insurance_claim_items.sale_id', 'sales.id');
                })
                ->get();

            abort_if($sales->isEmpty(), 422, 'لا توجد فواتير مؤهّلة');

            $claim = InsuranceClaim::create([
                'user_id'      => Auth::id(),
                'contract_id'  => $contract->id,
                'claim_number' => InsuranceClaim::generateNumber(Auth::id()),
                'amount'       => $sales->sum('covered_amount'),
                'status'       => 'draft',
                'claim_date'   => $data['claim_date'] ?? now()->toDateString(),
                'notes'        => $data['notes'] ?? null,
            ]);

            foreach ($sales as $sale) {
                InsuranceClaimItem::create([
                    'user_id'        => Auth::id(),
                    'claim_id'       => $claim->id,
                    'sale_id'        => $sale->id,
                    'covered_amount' => $sale->covered_amount,
                ]);
            }

            return $claim;
        });

        return redirect()->route('insurance-claims.show', $claim)
            ->with('success', 'تم إنشاء المطالبة بنجاح!');
    }

    public function show(InsuranceClaim $insuranceClaim)
    {
        abort_if($insuranceClaim->user_id !== Auth::id(), 403);

        $insuranceClaim->load([
            'contract:id,name,code',
            'items.sale:id,invoice_number,total,covered_amount,patient_amount,created_at',
        ]);

        return view('insurance-claims.show', ['claim' => $insuranceClaim]);
    }

    public function updateStatus(Request $request, InsuranceClaim $insuranceClaim)
    {
        abort_if($insuranceClaim->user_id !== Auth::id(), 403);

        $data = $request->validate([
            'status'           => 'required|in:draft,submitted,paid,rejected',
            'rejection_reason' => 'nullable|string|max:500',
        ]);

        if ($data['status'] !== 'rejected') {
            $data['rejection_reason'] = null;
        }

        $insuranceClaim->update($data);

        return back()->with('success', 'تم تحديث حالة المطالبة!');
    }

    public function destroy(InsuranceClaim $insuranceClaim)
    {
        abort_if($insuranceClaim->user_id !== Auth::id(), 403);
        abort_if($insuranceClaim->status === 'paid', 422, 'لا يمكن حذف مطالبة مسددة');

        DB::transaction(function () use ($insuranceClaim) {
            $insuranceClaim->items()->delete();
            $insuranceClaim->delete();
        });

        return redirect()->route('insurance-claims.index')
            ->with('success', 'تم حذف المطالبة!');
    }

    /** فواتير تأمين لعقد لم تدخل أي مطالبة بعد. */
    private function unclaimedSales(Contract $contract)
    {
        return Sale::where('user_id', Auth::id())
            ->where('contract_id', $contract->id)
            ->where('payment_method', 'insurance')
            ->where('covered_amount', '>', 0)
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('insurance_claim_items')
                    ->whereColumn('insurance_claim_items.sale_id', 'sales.id');
            })
            ->with('insuredPatient:id,name')
            ->latest()
            ->get(['id', 'invoice_number', 'insured_patient_id', 'total', 'covered_amount', 'patient_amount', 'created_at']);
    }
}
