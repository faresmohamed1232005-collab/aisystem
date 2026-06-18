@extends('layouts.app')
@section('title', 'الموردين')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <h2 class="text-xl font-bold text-gray-800">الموردين</h2>
            <span class="bg-indigo-100 text-indigo-700 text-sm px-3 py-1 rounded-full font-semibold">{{ $suppliers->total() }} مورد</span>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('purchases.create') }}"
               class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-xl text-sm font-semibold transition flex items-center gap-2">
                <i class="fas fa-file-invoice"></i> فاتورة شراء
            </a>
            <a href="{{ route('suppliers.create') }}"
               class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-xl text-sm font-semibold transition flex items-center gap-2">
                <i class="fas fa-plus"></i> إضافة مورد
            </a>
        </div>
    </div>

    @if(session('success'))
    <div class="bg-green-50 border border-green-200 rounded-xl p-4 text-green-700 flex items-center gap-2">
        <i class="fas fa-check-circle"></i> {{ session('success') }}
    </div>
    @endif

    <form method="GET" class="relative">
        <i class="fas fa-search absolute right-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
        <input type="text" name="q" value="{{ $q }}"
               placeholder="ابحث بالاسم أو الكود أو الشركة..."
               class="w-full border border-gray-200 rounded-xl pr-10 pl-4 py-3 focus:outline-none focus:border-indigo-400 text-right bg-white shadow-sm">
    </form>

    <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-right">
                <tr>
                    <th class="px-4 py-3 font-medium">الكود</th>
                    <th class="px-4 py-3 font-medium">المورد</th>
                    <th class="px-4 py-3 font-medium">الشركة</th>
                    <th class="px-4 py-3 font-medium">التليفون</th>
                    <th class="px-4 py-3 font-medium">الفواتير</th>
                    <th class="px-4 py-3 font-medium">الرصيد المستحق</th>
                    <th class="px-4 py-3 font-medium">إجراءات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($suppliers as $supplier)
                <tr class="hover:bg-gray-50 transition">
                    <td class="px-4 py-3">
                        <span class="font-mono text-xs bg-green-50 text-green-700 px-2 py-1 rounded-lg">{{ $supplier->code }}</span>
                    </td>
                    <td class="px-4 py-3">
                        <a href="{{ route('suppliers.show', $supplier) }}"
                           class="font-semibold text-gray-800 hover:text-indigo-600 transition">{{ $supplier->name }}</a>
                        @if($supplier->email)
                        <div class="text-xs text-gray-400">{{ $supplier->email }}</div>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-gray-500">{{ $supplier->company ?? '—' }}</td>
                    <td class="px-4 py-3 text-gray-500">{{ $supplier->phone ?? '—' }}</td>
                    <td class="px-4 py-3">
                        <span class="bg-gray-100 text-gray-600 text-xs px-2 py-1 rounded-full">
                            {{ $supplier->purchase_invoices_count }} فاتورة
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        @if($supplier->balance > 0)
                            <span class="font-bold text-red-600">{{ number_format($supplier->balance, 2) }} ج.م</span>
                        @else
                            <span class="text-green-600 font-semibold text-xs">✅ مسدد</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-2">
                            <a href="{{ route('suppliers.show', $supplier) }}"
                               class="text-indigo-500 hover:text-indigo-700 text-xs px-2 py-1 bg-indigo-50 rounded-lg transition">
                                <i class="fas fa-eye"></i>
                            </a>
                            <form action="{{ route('suppliers.destroy', $supplier) }}" method="POST"
                                  onsubmit="return confirm('هتحذف المورد ده؟')">
                                @csrf @method('DELETE')
                                <button class="text-red-500 hover:text-red-700 text-xs px-2 py-1 bg-red-50 rounded-lg transition">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center py-12 text-gray-300">
                        <i class="fas fa-truck text-4xl block mb-2"></i>مفيش موردين لحد دلوقتي
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        @if($suppliers->hasPages())
        <div class="p-4 border-t">{{ $suppliers->links() }}</div>
        @endif
    </div>
</div>
@endsection