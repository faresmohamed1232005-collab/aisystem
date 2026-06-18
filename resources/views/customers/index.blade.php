@extends('layouts.app')
@section('title', 'العملاء')

@section('content')
<div class="space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <h2 class="text-xl font-bold text-gray-800">العملاء</h2>
            <span class="bg-indigo-100 text-indigo-700 text-sm px-3 py-1 rounded-full font-semibold">
                {{ $customers->total() }} عميل
            </span>
        </div>
        <a href="{{ route('customers.create') }}"
           class="bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2 rounded-xl font-semibold text-sm flex items-center gap-2 transition">
            <i class="fas fa-plus"></i> إضافة عميل
        </a>
    </div>

    {{-- بحث --}}
    <form method="GET" class="relative">
        <i class="fas fa-search absolute right-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
        <input type="text" name="q" value="{{ $q }}"
               placeholder="ابحث بالاسم أو الكود أو التليفون..."
               class="w-full border border-gray-200 rounded-xl pr-10 pl-4 py-3 focus:outline-none focus:border-indigo-400 text-right bg-white shadow-sm">
    </form>

    {{-- جدول العملاء --}}
    <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-right">
                <tr>
                    <th class="px-4 py-3 font-medium">الكود</th>
                    <th class="px-4 py-3 font-medium">العميل</th>
                    <th class="px-4 py-3 font-medium">التليفون</th>
                    <th class="px-4 py-3 font-medium">عدد الفواتير</th>
                    <th class="px-4 py-3 font-medium">الرصيد المديون</th>
                    <th class="px-4 py-3 font-medium">إجراءات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($customers as $customer)
                <tr class="hover:bg-gray-50 transition">
                    <td class="px-4 py-3">
                        <span class="font-mono text-xs bg-indigo-50 text-indigo-600 px-2 py-1 rounded-lg">
                            {{ $customer->code }}
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        <a href="{{ route('customers.show', $customer) }}"
                           class="font-semibold text-gray-800 hover:text-indigo-600 transition">
                            {{ $customer->name }}
                        </a>
                        @if($customer->email)
                        <div class="text-xs text-gray-400">{{ $customer->email }}</div>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-gray-500">{{ $customer->phone ?? '—' }}</td>
                    <td class="px-4 py-3">
                        <span class="bg-gray-100 text-gray-600 text-xs px-2 py-1 rounded-full">
                            {{ $customer->sales_count }} فاتورة
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        @if($customer->balance > 0)
                            <span class="font-bold text-red-600">{{ number_format($customer->balance, 2) }} ج.م</span>
                        @else
                            <span class="text-green-600 font-semibold text-xs">✅ مسدد</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-2">
                            <a href="{{ route('customers.show', $customer) }}"
                               class="text-indigo-500 hover:text-indigo-700 text-xs px-2 py-1 bg-indigo-50 rounded-lg transition">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="{{ route('customers.edit', $customer) }}"
                               class="text-amber-500 hover:text-amber-700 text-xs px-2 py-1 bg-amber-50 rounded-lg transition">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form action="{{ route('customers.destroy', $customer) }}" method="POST"
                                  onsubmit="return confirm('هتحذف العميل ده؟')">
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
                    <td colspan="6" class="text-center py-12 text-gray-300">
                        <i class="fas fa-users text-4xl block mb-2"></i>
                        مفيش عملاء لحد دلوقتي
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        @if($customers->hasPages())
        <div class="p-4 border-t">{{ $customers->links() }}</div>
        @endif
    </div>
</div>
@endsection