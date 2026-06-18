<?php
namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::where('user_id', Auth::id())
            ->latest()->paginate(15);
        return view('products.index', compact('products'));
    }

    public function create() { return view('products.create'); }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'               => 'required|string|max:255',
            'barcode'            => ['nullable','string',
                \Illuminate\Validation\Rule::unique('products')->where(fn($q) => $q->where('user_id', Auth::id()))
            ],
            'category'           => 'nullable|string',
            'price'              => 'required|numeric|min:0',
            'cost_price'         => 'nullable|numeric|min:0',
            'quantity'           => 'required|integer|min:0',
            'min_quantity'       => 'nullable|integer|min:0',
            'expiry_date'        => 'nullable|date',
            'manufacturer'       => 'nullable|string',
            'description'        => 'nullable|string',
            'image'              => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'unit_name'          => 'nullable|string|max:50',
            'sub_unit_name'      => 'nullable|string|max:50',
            'smallest_unit_name' => 'nullable|string|max:50',
            'units_per_pack'     => 'nullable|integer|min:1',
            'sub_units_per_unit' => 'nullable|integer|min:1',
        ], [
            'name.required'     => 'اسم المنتج مطلوب',
            'price.required'    => 'سعر البيع مطلوب',
            'quantity.required' => 'الكمية مطلوبة',
            'barcode.unique'    => 'الباركود موجود قبل كده',
        ]);

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('products', 'public');
        }

        $data['unit_name']          = $data['unit_name']          ?? 'علبة';
        $data['units_per_pack']     = $data['units_per_pack']     ?? 1;
        $data['sub_units_per_unit'] = $data['sub_units_per_unit'] ?? 1;
        $data['user_id']            = Auth::id();

        Product::create($data);
        return redirect()->route('products.index')->with('success', 'تم إضافة المنتج بنجاح!');
    }

    public function edit(Product $product)
    {
        abort_if($product->user_id !== Auth::id(), 403);
        return view('products.edit', compact('product'));
    }

    public function update(Request $request, Product $product)
    {
        abort_if($product->user_id !== Auth::id(), 403);

        $data = $request->validate([
            'name'               => 'required|string|max:255',
            'price'              => 'required|numeric|min:0',
            'cost_price'         => 'nullable|numeric|min:0',
            'quantity'           => 'required|integer|min:0',
            'min_quantity'       => 'nullable|integer|min:0',
            'expiry_date'        => 'nullable|date',
            'category'           => 'nullable|string',
            'manufacturer'       => 'nullable|string',
            'description'        => 'nullable|string',
            'image'              => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'barcode'            => ['nullable','string',
                \Illuminate\Validation\Rule::unique('products')->ignore($product->id)
                    ->where(fn($q) => $q->where('user_id', Auth::id()))
            ],
            'unit_name'          => 'nullable|string|max:50',
            'sub_unit_name'      => 'nullable|string|max:50',
            'smallest_unit_name' => 'nullable|string|max:50',
            'units_per_pack'     => 'nullable|integer|min:1',
            'sub_units_per_unit' => 'nullable|integer|min:1',
        ]);

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('products', 'public');
        }

        $product->update($data);
        return redirect()->route('products.index')->with('success', 'تم تحديث المنتج بنجاح!');
    }

    public function destroy(Product $product)
    {
        abort_if($product->user_id !== Auth::id(), 403);
        $product->delete();
        return back()->with('success', 'تم حذف المنتج!');
    }

    public function search(Request $request)
    {
        $q        = $request->get('q', '');
        $category = $request->get('category', '');
        $barcode  = $request->get('barcode', '');

        $products = Product::where('user_id', Auth::id())
            ->when($barcode, fn($query) => $query->where('barcode', $barcode))
            ->when(!$barcode && $q, function ($query) use ($q) {
                $query->where(function ($q2) use ($q) {
                    $q2->where('name',    'like', "%{$q}%")
                       ->orWhere('barcode','like', "%{$q}%");
                });
            })
            ->when($category, fn($query) => $query->where('category', $category))
            ->select([
                'id','name','barcode','category',
                'price','cost_price',
                'quantity','min_quantity','expiry_date',
                'unit_name','sub_unit_name','smallest_unit_name',
                'units_per_pack','sub_units_per_unit',
            ])
            ->orderBy('name')
            ->limit(30)
            ->get()
            ->map(function ($p) {
                $unitsPerPack    = max(1, (int)$p->units_per_pack);
                $subUnitsPerUnit = max(1, (int)$p->sub_units_per_unit);
                $totalSmallest   = $unitsPerPack * $subUnitsPerUnit;

                $p->sub_unit_price      = $unitsPerPack > 1
                    ? round($p->price / $unitsPerPack, 2) : null;

                $p->smallest_unit_price = $totalSmallest > 1
                    ? round($p->price / $totalSmallest, 2) : null;

                $units = [[
                    'key'        => 'pack',
                    'name'       => $p->unit_name ?? 'علبة',
                    'price'      => (float)$p->price,
                    'qty_factor' => 1,
                ]];

                if ($p->sub_unit_name && $unitsPerPack > 1) {
                    $units[] = [
                        'key'        => 'sub',
                        'name'       => $p->sub_unit_name,
                        'price'      => $p->sub_unit_price,
                        'qty_factor' => 1 / $unitsPerPack,
                    ];
                }

                if ($p->smallest_unit_name && $subUnitsPerUnit > 1) {
                    $units[] = [
                        'key'        => 'smallest',
                        'name'       => $p->smallest_unit_name,
                        'price'      => $p->smallest_unit_price,
                        'qty_factor' => 1 / $totalSmallest,
                    ];
                }

                $p->available_units = $units;
                return $p;
            });

        return response()->json($products);
    }
}