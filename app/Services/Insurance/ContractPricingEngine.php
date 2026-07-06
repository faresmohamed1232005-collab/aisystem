<?php

namespace App\Services\Insurance;

use App\Models\Contract;

/**
 * Contract Pricing Engine — يطبّق قواعد العقد تلقائياً أثناء البيع.
 *
 * يحسب تقسيم الفاتورة بين ما يتحمّله التأمين (covered) وما يدفعه المريض (patient)
 * من نسبة التغطية، مع احترام السقف الأقصى للروشتة، ويحدّد إن كانت تحتاج موافقة مسبقة.
 *
 * مثال: فاتورة 1000، تغطية 80% → التأمين 800، المريض 200.
 */
class ContractPricingEngine
{
    /**
     * قسّم مبلغاً إجمالياً حسب قواعد عقد التأمين.
     *
     * @return array{covered: float, patient: float, needs_approval: bool, capped: bool}
     */
    public function split(Contract $contract, float $grossAmount): array
    {
        $grossAmount = max(0.0, round($grossAmount, 2));

        // عقد غير تأميني (حكومي/شركة...) بلا قاعدة تأمين: لا تغطية — المريض/الجهة يدفع الكل.
        $rule = $contract->relationLoaded('insuranceRule')
            ? $contract->insuranceRule
            : $contract->insuranceRule()->first();

        if (! $contract->isInsurance() || $rule === null) {
            return ['covered' => 0.0, 'patient' => $grossAmount, 'needs_approval' => false, 'capped' => false];
        }

        $coveragePercent = (float) $rule->coverage_percent;
        $covered = round($grossAmount * $coveragePercent / 100, 2);

        // السقف الأقصى لكل روشتة يقصّ تغطية التأمين.
        $capped = false;
        $max = $rule->max_per_prescription;
        if ($max !== null && $covered > (float) $max) {
            $covered = round((float) $max, 2);
            $capped = true;
        }

        $patient = round($grossAmount - $covered, 2);

        // تحتاج موافقة مسبقة لو مفعّلة وتجاوز المبلغ الحد.
        $needsApproval = (bool) $rule->approval_required
            && $rule->approval_amount_limit !== null
            && $grossAmount > (float) $rule->approval_amount_limit;

        return [
            'covered'        => $covered,
            'patient'        => $patient,
            'needs_approval' => $needsApproval,
            'capped'         => $capped,
        ];
    }

    /**
     * خصم قاعدة تسعير لفئة منتج (إن وُجدت قاعدة للعقد بهذه الفئة).
     * يرجّع السعر بعد الخصم.
     */
    public function applyCategoryDiscount(Contract $contract, string $category, float $unitPrice): float
    {
        $rule = ($contract->relationLoaded('pricingRules') ? $contract->pricingRules : $contract->pricingRules()->get())
            ->firstWhere('product_category', $category);

        if ($rule === null) {
            return round($unitPrice, 2);
        }

        $discounted = $rule->discount_type === 'percentage'
            ? $unitPrice - ($unitPrice * (float) $rule->discount_value / 100)
            : $unitPrice - (float) $rule->discount_value;

        return round(max(0.0, $discounted), 2);
    }
}
