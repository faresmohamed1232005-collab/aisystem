<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\InsuredPatient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InsuredPatientController extends Controller
{
    public function index(Request $request)
    {
        $q          = $request->get('q', '');
        $contractId = $request->get('contract_id', '');

        $patients = InsuredPatient::where('user_id', Auth::id())
            ->with('contract:id,name,code')
            ->when($q, fn($query) => $query->where(function ($q2) use ($q) {
                $q2->where('name', 'like', "%$q%")
                    ->orWhere('card_number', 'like', "%$q%")
                    ->orWhere('membership_number', 'like', "%$q%");
            }))
            ->when($contractId, fn($query) => $query->where('contract_id', $contractId))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        // كل عقود التأمين (للفلتر أعلى الشاشة).
        $contracts = Contract::where('user_id', Auth::id())
            ->where('type', 'insurance')
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        // العقود النشطة فقط (للـ panel/modal الجديدين — كانت في create/edit).
        $activeContracts = $this->insuranceContracts();

        return view('insured-patients.index', compact('patients', 'contracts', 'activeContracts', 'q', 'contractId'));
    }

    public function store(Request $request)
    {
        $data = $this->validatePatient($request);
        $data['user_id'] = Auth::id();

        InsuredPatient::create($data);

        return redirect()->route('insured-patients.index')
            ->with('success', 'تم إضافة المريض المؤمّن عليه!');
    }

    public function update(Request $request, InsuredPatient $insuredPatient)
    {
        abort_if($insuredPatient->user_id !== Auth::id(), 403);
        $insuredPatient->update($this->validatePatient($request));

        return redirect()->route('insured-patients.index')
            ->with('success', 'تم تحديث بيانات المريض!');
    }

    public function destroy(InsuredPatient $insuredPatient)
    {
        abort_if($insuredPatient->user_id !== Auth::id(), 403);
        $insuredPatient->delete();
        return back()->with('success', 'تم حذف المريض!');
    }

    // بحث AJAX لاختيار مريض في شاشة البيع (مقيّد بالعقد المختار)
    public function search(Request $request)
    {
        $q          = $request->get('q', '');
        $contractId = $request->get('contract_id');

        $patients = InsuredPatient::where('user_id', Auth::id())
            ->when($contractId, fn($query) => $query->where('contract_id', $contractId))
            ->when($q, fn($query) => $query->where(function ($q2) use ($q) {
                $q2->where('name', 'like', "%$q%")
                    ->orWhere('card_number', 'like', "%$q%")
                    ->orWhere('membership_number', 'like', "%$q%");
            }))
            ->limit(10)
            ->get(['id', 'contract_id', 'name', 'card_number', 'membership_number', 'coverage_end_date']);

        return response()->json($patients);
    }

    private function insuranceContracts()
    {
        return Contract::where('user_id', Auth::id())
            ->where('type', 'insurance')
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'code']);
    }

    private function validatePatient(Request $request): array
    {
        return $request->validate([
            'contract_id'       => 'required|integer|exists:contracts,id',
            'name'              => 'required|string|max:255',
            'card_number'       => 'nullable|string|max:50',
            'membership_number' => 'nullable|string|max:50',
            'coverage_end_date' => 'nullable|date',
        ], [
            'name.required'        => 'اسم المريض مطلوب',
            'contract_id.required' => 'العقد مطلوب',
        ]);
    }
}
