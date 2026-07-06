@extends('layouts.app')
@section('title', 'استلام تحويل — '.$transfer->transfer_number)

@section('content')
<div class="max-w-3xl mx-auto space-y-6">

    <div class="flex items-center gap-3">
        <a href="{{ route('stock-transfers.show', $transfer) }}" class="text-gray-400 hover:text-gray-600"><i class="fas fa-arrow-right"></i></a>
        <h2 class="text-xl font-bold text-gray-800">استلام التحويل {{ $transfer->transfer_number }}</h2>
    </div>

    @if($errors->any())
    <div class="bg-red-50 border border-red-200 rounded-xl p-4 text-red-600 text-sm">
        @foreach($errors->all() as $e) <div>• {{ $e }}</div> @endforeach
    </div>
    @endif

    <div class="bg-blue-50 border border-blue-100 rounded-xl p-3 text-sm text-blue-700">
        <i class="fas fa-circle-info"></i> راجع الكميات المستلمة فعلاً. الكمية المستلمة تُضاف لمخزون فرعك؛ التالف يُسجَّل ولا يُضاف.
    </div>

    <form action="{{ route('stock-transfers.receive.confirm', $transfer) }}" method="POST">
        @csrf

        <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-500 text-right">
                    <tr>
                        <th class="px-4 py-3 font-medium">الدواء</th>
                        <th class="px-4 py-3 font-medium">الصلاحية</th>
                        <th class="px-4 py-3 font-medium">المُرسل</th>
                        <th class="px-4 py-3 font-medium">المستلم فعلاً</th>
                        <th class="px-4 py-3 font-medium">تالف</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($transfer->items as $item)
                    <tr>
                        <td class="px-4 py-3 font-semibold text-gray-800">{{ $item->drug->name_ar ?? $item->drug->name_en ?? '—' }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ optional($item->expiry_date)->format('Y-m-d') ?? '—' }}</td>
                        <td class="px-4 py-3 text-gray-700">{{ number_format($item->quantity, 0) }}</td>
                        <td class="px-4 py-3">
                            <input type="number" name="items[{{ $item->id }}][received]" value="{{ (float) $item->quantity }}"
                                   min="0" step="any"
                                   class="w-24 border border-gray-200 rounded-lg px-2 py-1.5 text-sm text-right focus:outline-none focus:border-green-400">
                        </td>
                        <td class="px-4 py-3">
                            <input type="number" name="items[{{ $item->id }}][damaged]" value="0" min="0" step="any"
                                   class="w-20 border border-gray-200 rounded-lg px-2 py-1.5 text-sm text-right focus:outline-none focus:border-red-400">
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="bg-white rounded-2xl shadow-sm p-6 mt-6 space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">ملاحظات الاستلام</label>
                <textarea name="notes" rows="2" class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:border-indigo-400 text-right"></textarea>
            </div>
            <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-bold px-6 py-3 rounded-xl transition flex items-center gap-2">
                <i class="fas fa-check"></i> تأكيد الاستلام وإضافة للمخزون
            </button>
        </div>
    </form>
</div>
@endsection
