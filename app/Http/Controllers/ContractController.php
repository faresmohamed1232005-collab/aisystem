<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\InsuranceRule;
use App\Models\PricingRule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ContractController extends Controller
{
    public function index(Request $request)
    {
        $q    = $request->get('q', '');
        $type = $request->get('type', '');

        $contracts = Contract::where('user_id', Auth::id())
            ->when($q, fn($query) => $query->where(function ($q2) use ($q) {
                $q2->where('name', 'like', "%$q%")
                    ->orWhere('code', 'like', "%$q%")
                    ->orWhere('tax_number', 'like', "%$q%");
            }))
            ->when($type, fn($query) => $query->where('type', $type))
            ->withCount(['insuredPatients', 'claims'])
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('contracts.index', compact('contracts', 'q', 'type'));
    }

    public function create()
    {
        return view('contracts.create');
    }

    public function store(Request $request)
    {
        $data = $this->validateContract($request);

        $data['user_id'] = Auth::id();
        $data['code']    = Contract::generateCode(Auth::id());

        if ($request->hasFile('contract_pdf')) {
            $data['contract_pdf'] = $request->file('contract_pdf')->store('contracts', 'public');
        }

        $contract = Contract::create($data);

        // عقد تأمين: أنشئ قاعدة تأمين افتراضية جاهزة للتعديل.
        if ($contract->isInsurance()) {
            InsuranceRule::create([
                'user_id'                      => Auth::id(),
                'contract_id'                  => $contract->id,
                'coverage_percent'             => 0,
                'patient_contribution_percent' => 100,
                'approval_required'            => false,
            ]);
        }

        return redirect()->route('contracts.show', $contract)
            ->with('success', 'تم إضافة العقد بنجاح!');
    }

    public function show(Contract $contract)
    {
        abort_if($contract->user_id !== Auth::id(), 403);

        $contract->load(['insuranceRule', 'pricingRules', 'insuredPatients', 'claims']);

        return view('contracts.show', [
            'contract'   => $contract,
            'categories' => PricingRule::CATEGORIES,
        ]);
    }

    public function edit(Contract $contract)
    {
        abort_if($contract->user_id !== Auth::id(), 403);
        return view('contracts.edit', compact('contract'));
    }

    public function update(Request $request, Contract $contract)
    {
        abort_if($contract->user_id !== Auth::id(), 403);

        $data = $this->validateContract($request);

        if ($request->hasFile('contract_pdf')) {
            if ($contract->contract_pdf) {
                Storage::disk('public')->delete($contract->contract_pdf);
            }
            $data['contract_pdf'] = $request->file('contract_pdf')->store('contracts', 'public');
        }

        $contract->update($data);

        return redirect()->route('contracts.show', $contract)
            ->with('success', 'تم تحديث بيانات العقد!');
    }

    public function destroy(Contract $contract)
    {
        abort_if($contract->user_id !== Auth::id(), 403);
        $contract->delete();
        return redirect()->route('contracts.index')
            ->with('success', 'تم حذف العقد!');
    }

    // ===== قاعدة التأمين (1:1) — upsert =====
    public function saveInsuranceRule(Request $request, Contract $contract)
    {
        abort_if($contract->user_id !== Auth::id(), 403);
        abort_unless($contract->isInsurance(), 422, 'العقد ليس عقد تأمين');

        $data = $request->validate([
            'coverage_percent'             => 'required|numeric|min:0|max:100',
            'patient_contribution_percent' => 'required|numeric|min:0|max:100',
            'max_per_prescription'         => 'nullable|numeric|min:0',
            'approval_required'            => 'nullable|boolean',
            'approval_amount_limit'        => 'nullable|numeric|min:0',
        ]);

        $data['approval_required'] = $request->boolean('approval_required');

        $contract->insuranceRule()->updateOrCreate(
            ['contract_id' => $contract->id],
            $data + ['user_id' => Auth::id()],
        );

        return back()->with('success', 'تم حفظ قواعد التأمين!');
    }

    // ===== قواعد التسعير (n) =====
    public function addPricingRule(Request $request, Contract $contract)
    {
        abort_if($contract->user_id !== Auth::id(), 403);

        $data = $request->validate([
            'product_category' => 'required|in:' . implode(',', PricingRule::CATEGORIES),
            'discount_type'    => 'required|in:percentage,amount',
            'discount_value'   => 'required|numeric|min:0',
        ]);

        $contract->pricingRules()->updateOrCreate(
            ['product_category' => $data['product_category']],
            $data + ['user_id' => Auth::id()],
        );

        return back()->with('success', 'تم حفظ قاعدة التسعير!');
    }

    public function deletePricingRule(Contract $contract, PricingRule $pricingRule)
    {
        abort_if($contract->user_id !== Auth::id(), 403);
        abort_if($pricingRule->contract_id !== $contract->id, 404);
        $pricingRule->delete();
        return back()->with('success', 'تم حذف قاعدة التسعير!');
    }

    private function validateContract(Request $request): array
    {
        return $request->validate([
            'type'           => 'required|in:' . implode(',', Contract::TYPES),
            'name'           => 'required|string|max:255',
            'tax_number'     => 'nullable|string|max:50',
            'contact_person' => 'nullable|string|max:255',
            'mobile'         => 'nullable|string|max:20',
            'email'          => 'nullable|email|max:255',
            'address'        => 'nullable|string|max:500',
            'start_date'     => 'nullable|date',
            'end_date'       => 'nullable|date|after_or_equal:start_date',
            'status'         => 'required|in:active,suspended,expired',
            'contract_pdf'   => 'nullable|file|mimes:pdf|max:10240',
            'notes'          => 'nullable|string|max:1000',
        ], [
            'name.required' => 'اسم الجهة مطلوب',
            'type.required' => 'نوع العقد مطلوب',
        ]);
    }
}
