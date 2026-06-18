{{-- resources/views/drawer-lock/_row.blade.php --}}

<tr class="hover:bg-gray-50/50 transition group" id="lock-row-{{ $lock->id }}">
    <td class="px-4 py-4 text-gray-400 text-xs">{{ $index }}</td>

    {{-- التاريخ --}}
    <td class="px-4 py-4 whitespace-nowrap">
        <div class="text-xs font-semibold text-gray-700">{{ $lock->created_at->format('Y/m/d') }}</div>
        <div class="text-xs text-gray-400">{{ $lock->created_at->format('H:i') }}</div>
    </td>

    {{-- المسجِّل --}}
    <td class="px-4 py-4">
        <div class="flex items-center gap-2">
            <div class="w-7 h-7 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center text-xs font-bold flex-shrink-0">
                {{ mb_substr($lock->locked_by_name, 0, 1) }}
            </div>
            <div>
                <div class="font-semibold text-gray-700 text-xs">{{ $lock->locked_by_name }}</div>
                <div class="text-gray-400 text-xs" dir="ltr">{{ $lock->locked_by_email }}</div>
            </div>
        </div>
    </td>

    {{-- البائع --}}
    <td class="px-4 py-4 font-semibold text-gray-700 text-xs">{{ $lock->seller_name }}</td>

    {{-- الفعلي --}}
    <td class="px-4 py-4 font-bold text-green-700 text-xs" dir="ltr">
        {{ number_format($lock->cash_amount, 2) }}
        <span class="text-gray-400 font-normal"></span>
    </td>

    {{-- المتوقع --}}
    <td class="px-4 py-4 font-bold text-blue-700 text-xs" dir="ltr">
        {{ number_format($lock->expected_amount, 2) }}
        <span class="text-gray-400 font-normal"></span>
    </td>

    {{-- المتروك --}}
    <td class="px-4 py-4 font-bold text-amber-700 text-xs" dir="ltr">
        {{ number_format($lock->opening_amount ?? 0, 2) }}
        <span class="text-gray-400 font-normal"></span>
    </td>

    {{-- للإيداع --}}
    <td class="px-4 py-4 font-bold text-purple-700 text-xs" dir="ltr">
        @php $deposit = max(0, $lock->cash_amount - ($lock->opening_amount ?? 0)); @endphp
        {{ number_format($deposit, 2) }}
        <span class="text-gray-400 font-normal"></span>
    </td>

    {{-- الفرق --}}
    <td class="px-4 py-4 text-xs" dir="ltr">
        @php $diff = $lock->difference; @endphp
        <span class="font-bold px-2 py-1 rounded-lg
            {{ $diff > 0 ? 'bg-green-50 text-green-700' :
               ($diff < 0 ? 'bg-red-50 text-red-600' : 'bg-gray-100 text-gray-500') }}">
            {{ $diff >= 0 ? '+' : '' }}{{ number_format($diff, 2) }} 
        </span>
    </td>

    {{-- الملاحظات --}}
    <td class="px-4 py-4 text-gray-500 text-xs max-w-[130px] truncate">
        {{ $lock->notes ?? '—' }}
    </td>

    {{-- حذف --}}
    <td class="px-4 py-4 text-center">
        <button onclick="openDeleteModal({{ $lock->id }})"
                class="opacity-0 group-hover:opacity-100 transition w-8 h-8 bg-red-50 hover:bg-red-100 text-red-500 hover:text-red-700 rounded-lg flex items-center justify-center mx-auto"
                title="حذف">
            <i class="fas fa-trash text-xs"></i>
        </button>
    </td>
</tr>