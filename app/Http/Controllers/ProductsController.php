<?php

namespace App\Http\Controllers;

use App\Models\Drug;
use App\Models\UserDrugInventory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProductsController extends Controller
{
    // =====================================================
    // Helper: تحويل كسر العلبة لـ "X علبة و Y شريط"
    // =====================================================
    public static function formatBoxStrip(float $qty, int $majorUnits, string $stripName = 'شريط'): string
    {
        if ($majorUnits <= 1 || $qty <= 0) {
            return number_format($qty, 2) . ' علبة';
        }

        $totalStrips = round($qty * $majorUnits);
        $boxes       = (int) floor($totalStrips / $majorUnits);
        $strips      = $totalStrips % $majorUnits;

        if ($boxes > 0 && $strips > 0) {
            return "{$boxes} علبة و {$strips} {$stripName}";
        } elseif ($boxes > 0) {
            return "{$boxes} علبة";
        } else {
            return "{$strips} {$stripName}";
        }
    }

    // =====================================================
    // subquery مشترك: يجمع كل الباتشات لكل دواء
    // يستخدم في index() و search()
    // =====================================================
    private function inventorySubquery(int $userId)
    {
        return DB::table('user_drug_inventory')
            ->select(
                'drug_id',
                DB::raw('SUM(quantity)                              as quantity'),
                DB::raw('MIN(expiry_date)                           as expiry_date'),   // أقرب تاريخ صلاحية
                DB::raw('MAX(cost_price)                            as cost_price'),    // آخر سعر شراء
                DB::raw('MAX(custom_price)                          as custom_price'),
                DB::raw('MAX(COALESCE(min_quantity, 5))             as min_quantity'),
                DB::raw("MAX(COALESCE(strip_unit_name, 'شريط'))    as strip_unit_name"),
                DB::raw("MAX(COALESCE(piece_unit_name, 'حبة'))     as piece_unit_name"),
            )
            ->where('user_id', $userId)
            ->where('branch_id', \App\Support\Branch::id())
            ->where('quantity', '>', 0)          // ← باتشات فاضية تتجاهل
            ->groupBy('drug_id');
    }

    // =====================================================
    // صفحة المخزن
    // =====================================================
    public function index(Request $request)
    {
        $userId = Auth::id();

        $query = DB::table('drugs')
            ->leftJoinSub($this->inventorySubquery($userId), 'inv', function ($join) {
                $join->on('inv.drug_id', '=', 'drugs.id');
            })
            ->select(
                'drugs.id',
                'drugs.name_ar',
                'drugs.name_en',
                'drugs.new_price',
                'drugs.old_price',
                'drugs.category',
                'drugs.dosage_form',
                'drugs.concentration',
                'drugs.company',
                'drugs.barcode',
                'drugs.major_units',
                'drugs.minor_units',
                'drugs.active_ingredient',
                'drugs.popularity',
                DB::raw('COALESCE(inv.quantity, 0)      as quantity'),
                DB::raw('COALESCE(inv.min_quantity, 5)  as min_quantity'),
                'inv.custom_price',
                'inv.cost_price',
                'inv.expiry_date',
                DB::raw("COALESCE(inv.strip_unit_name, 'شريط') as strip_unit_name"),
                DB::raw("COALESCE(inv.piece_unit_name, 'حبة')  as piece_unit_name"),
            );

        if ($request->filled('search')) {
            $term = $request->search;
            $query->where(function ($q) use ($term) {
                $q->where('drugs.name_ar', 'like', "%{$term}%")
                  ->orWhere('drugs.name_en', 'like', "%{$term}%")
                  ->orWhere('drugs.barcode', 'like', "%{$term}%")
                  ->orWhere('drugs.active_ingredient', 'like', "%{$term}%")
                  ->orWhere('drugs.category', 'like', "%{$term}%");
            });
        }

        if ($request->filled('in_stock')) {
            $query->where(DB::raw('COALESCE(inv.quantity, 0)'), '>', 0);
        }

        if ($request->filled('low_stock')) {
            $query->whereRaw('COALESCE(inv.quantity, 0) > 0')
                  ->whereRaw('COALESCE(inv.quantity, 0) <= COALESCE(inv.min_quantity, 5)');
        }

        if ($request->filled('category')) {
            $query->where('drugs.category', 'like', "%{$request->category}%");
        }

        $products = $query
            ->orderByRaw('COALESCE(inv.quantity, 0) DESC')
            ->orderByDesc('drugs.popularity')
            ->paginate(20)
            ->withQueryString();

        $stats = [
            'total_drugs'    => Drug::count(),
            'total_in_stock' => UserDrugInventory::where('user_id', $userId)->currentBranch()->where('quantity', '>', 0)->count(),
            'low_stock'      => UserDrugInventory::where('user_id', $userId)->currentBranch()
                                    ->whereRaw('quantity > 0 AND quantity <= min_quantity')->count(),
        ];

        return view('products.index', compact('products', 'stats'));
    }

    // =====================================================
    // البحث للبيع المباشر
    // =====================================================
    public function search(Request $request)
    {
        $term     = $request->q ?? '';
        $category = $request->category ?? '';
        $barcode  = $request->barcode ?? '';
        $userId   = Auth::id();

        $query = DB::table('drugs')
            ->leftJoinSub($this->inventorySubquery($userId), 'inv', function ($join) {
                $join->on('inv.drug_id', '=', 'drugs.id');
            })
            ->select(
                'drugs.id',
                'drugs.name_ar',
                'drugs.name_en',
                'drugs.new_price',
                'drugs.old_price',
                'drugs.category',
                'drugs.dosage_form',
                'drugs.concentration',
                'drugs.company',
                'drugs.barcode',
                'drugs.major_units',
                'drugs.minor_units',
                'drugs.popularity',
                DB::raw('COALESCE(inv.quantity, 0)      as quantity'),
                DB::raw('COALESCE(inv.min_quantity, 5)  as min_quantity'),
                DB::raw('COALESCE(inv.custom_price, drugs.new_price, drugs.old_price, 0) as price'),
                'inv.cost_price',
                'inv.expiry_date',
                DB::raw("COALESCE(inv.strip_unit_name, 'شريط') as strip_unit_name"),
                DB::raw("COALESCE(inv.piece_unit_name, 'حبة')  as piece_unit_name"),
            );

        if ($barcode !== '') {
            $query->where('drugs.barcode', 'like', "%{$barcode}%");
        } elseif ($term !== '') {
            $query->where(function ($q) use ($term) {
                $q->where('drugs.name_ar', 'like', "%{$term}%")
                  ->orWhere('drugs.name_en', 'like', "%{$term}%")
                  ->orWhere('drugs.barcode', 'like', "%{$term}%")
                  ->orWhere('drugs.active_ingredient', 'like', "%{$term}%")
                  ->orWhere('drugs.category', 'like', "%{$term}%");
            });
        }

        if ($category !== '') {
            $query->where('drugs.category', 'like', "%{$category}%");
        }

        if ($term === '' && $category === '' && $barcode === '') {
            return response()->json([]);
        }

        $rows = $query
            ->orderByDesc(DB::raw('COALESCE(inv.quantity, 0)'))
            ->orderByDesc('drugs.popularity')
            ->limit(30)->get();

        $results = $rows->map(function ($row) {
            $price      = (float) $row->price;
            $majorUnits = max(1, (int) $row->major_units);
            $minorUnits = max(1, (int) $row->minor_units);
            $stripName  = $row->strip_unit_name ?? 'شريط';
            $pieceName  = $row->piece_unit_name ?? 'حبة';
            $qtyBoxes   = (float) $row->quantity;

            // ═══ وحدة العلبة — دايماً موجودة ═══
            $units = [
                [
                    'key'        => 'pack',
                    'name'       => 'علبة',
                    'price'      => $price,
                    'qty_factor' => 1,
                    'label'      => "علبة ({$majorUnits} {$stripName})",
                ],
            ];

            // ═══ وحدة الشريط — لو العلبة فيها أكتر من شريط ═══
            if ($majorUnits > 1) {
                $stripPrice = round($price / $majorUnits, 4);
                $units[] = [
                    'key'        => 'strip',
                    'name'       => $stripName,
                    'price'      => $stripPrice,
                    'qty_factor' => round(1 / $majorUnits, 8),
                    'label'      => "{$stripName} ({$minorUnits} {$pieceName})",
                ];
            }

            // ═══ وحدة الحبة — لو الشريط فيه أكتر من حبة ═══
            if ($minorUnits > 1) {
                $totalPiecesPerBox = $majorUnits * $minorUnits;
                $piecePrice        = round($price / $totalPiecesPerBox, 4);
                $units[] = [
                    'key'        => 'piece',
                    'name'       => $pieceName,
                    'price'      => $piecePrice,
                    'qty_factor' => round(1 / $totalPiecesPerBox, 8),
                    'label'      => $pieceName,
                ];
            }

            return [
                'id'              => $row->id,
                'name'            => $row->name_ar ?? $row->name_en,
                'barcode'         => $row->barcode,
                'category'        => $row->category,
                'dosage_form'     => $row->dosage_form,
                'concentration'   => $row->concentration,
                'company'         => $row->company,
                'price'           => $price,
                'cost_price'      => (float) ($row->cost_price ?? 0),
                'quantity'        => $qtyBoxes,
                'quantity_strips' => round($qtyBoxes * $majorUnits),
                'quantity_pieces' => round($qtyBoxes * $majorUnits * $minorUnits),
                'min_quantity'    => (int) ($row->min_quantity ?? 5),
                'expiry_date'     => $row->expiry_date,   // أقرب تاريخ صلاحية
                'major_units'     => $majorUnits,
                'minor_units'     => $minorUnits,
                'strip_unit_name' => $stripName,
                'piece_unit_name' => $pieceName,
                'available_units' => $units,
                'in_my_inventory' => $qtyBoxes > 0,
            ];
        });

        return response()->json($results);
    }

    // =====================================================
    // تحديث الكمية من صفحة المخزن
    // =====================================================
    public function updateInventory(Request $request, int $drugId)
    {
        $request->validate([
            'quantity'        => 'required|numeric|min:0',
            'min_quantity'    => 'nullable|numeric|min:0',
            'custom_price'    => 'nullable|numeric|min:0',
            'cost_price'      => 'nullable|numeric|min:0',
            'expiry_date'     => 'nullable|date',
            'strip_unit_name' => 'nullable|string|max:30',
            'piece_unit_name' => 'nullable|string|max:30',
        ]);

        $drug = Drug::findOrFail($drugId);

        $data = ['quantity' => $request->quantity];
        if ($request->filled('min_quantity'))    $data['min_quantity']    = $request->min_quantity;
        if ($request->filled('custom_price'))    $data['custom_price']    = $request->custom_price;
        if ($request->filled('cost_price'))      $data['cost_price']      = $request->cost_price;
        if ($request->filled('expiry_date'))     $data['expiry_date']     = $request->expiry_date;
        if ($request->filled('strip_unit_name')) $data['strip_unit_name'] = $request->strip_unit_name;
        if ($request->filled('piece_unit_name')) $data['piece_unit_name'] = $request->piece_unit_name;

        // updateInventory بيحدث أو ينشئ على أساس (user_id + branch_id + drug_id + expiry_date)
        UserDrugInventory::updateOrCreate(
            [
                'user_id'     => Auth::id(),
                'branch_id'   => \App\Support\Branch::id(),
                'drug_id'     => $drug->id,
                'expiry_date' => $request->expiry_date ?? null,
            ],
            $data
        );

        return response()->json(['success' => true, 'message' => 'تم تحديث المخزون ✅']);
    }

    public function addToInventory(Request $request)
    {
        $request->validate([
            'drug_id'     => 'required|exists:drugs,id',
            'quantity'    => 'required|numeric|min:0',
            'expiry_date' => 'nullable|date',
        ]);

        UserDrugInventory::updateOrCreate(
            [
                'user_id'     => Auth::id(),
                'branch_id'   => \App\Support\Branch::id(),
                'drug_id'     => $request->drug_id,
                'expiry_date' => $request->expiry_date ?? null,
            ],
            ['quantity' => $request->quantity]
        );

        $drug = Drug::find($request->drug_id);
        return response()->json(['success' => true, 'message' => "تم إضافة {$drug->name_ar} ✅"]);
    }
}