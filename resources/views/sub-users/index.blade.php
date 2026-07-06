@extends('layouts.app')

@section('title', '⚙️ إدارة المستخدمين والصلاحيات')

@section('content')

<div class="max-w-5xl mx-auto space-y-8">

    {{-- ══════════════════════════════════════════ --}}
    {{--  فورم إضافة مستخدم جديد --}}
    {{-- ══════════════════════════════════════════ --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="bg-gradient-to-l from-indigo-50 to-white px-6 py-5 border-b border-gray-100">
            <h2 class="text-base font-bold text-gray-800 flex items-center gap-2">
                <span class="w-8 h-8 bg-indigo-100 rounded-lg flex items-center justify-center text-indigo-600">
                    <i class="fas fa-user-plus text-sm"></i>
                </span>
                إضافة مستخدم جديد
            </h2>
            <p class="text-xs text-gray-500 mt-1 mr-10">المستخدمون المضافون سيتمكنون من الوصول للنظام بدون رؤية قسم الصلاحيات</p>
        </div>

        <form action="{{ route('sub-users.store') }}" method="POST" class="p-6">
            @csrf

            @if(session('success'))
            <div class="mb-4 flex items-center gap-2 bg-green-50 border border-green-200 text-green-700 rounded-xl px-4 py-3 text-sm">
                <i class="fas fa-check-circle text-green-500"></i>
                {{ session('success') }}
            </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

                {{-- الاسم --}}
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">الاسم الكامل <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name') }}" placeholder="مثال: أحمد محمد"
                           class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-300 focus:border-indigo-400 outline-none transition
                                  @error('name') border-red-400 @enderror">
                    @error('name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                {{-- الوظيفة --}}
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">الوظيفة <span class="text-red-500">*</span></label>
                    <select name="role"
                            class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-300 focus:border-indigo-400 outline-none transition bg-white
                                   @error('role') border-red-400 @enderror">
                        <option value="">— اختر الوظيفة —</option>
                        @foreach(\App\Support\Roles::ASSIGNABLE as $r)
                        <option value="{{ $r }}" {{ old('role') == $r ? 'selected' : '' }}>{{ \App\Support\Roles::label($r) }}</option>
                        @endforeach
                    </select>
                    @error('role')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                {{-- الإيميل --}}
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">البريد الإلكتروني <span class="text-red-500">*</span></label>
                    <input type="email" name="email" value="{{ old('email') }}" placeholder="example@email.com" dir="ltr"
                           class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-300 focus:border-indigo-400 outline-none transition
                                  @error('email') border-red-400 @enderror">
                    @error('email')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                {{-- كلمة المرور --}}
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">كلمة المرور <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <input type="password" name="password" id="new_password" placeholder="٦ أحرف على الأقل" dir="ltr"
                               class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-300 focus:border-indigo-400 outline-none transition
                                      @error('password') border-red-400 @enderror">
                        <button type="button" onclick="togglePass('new_password','eye_new')"
                                class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 transition">
                            <i id="eye_new" class="fas fa-eye text-sm"></i>
                        </button>
                    </div>
                    @error('password')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="flex justify-end mt-6">
                <button type="submit"
                        class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2.5 rounded-xl text-sm font-semibold transition flex items-center gap-2 shadow-sm">
                    <i class="fas fa-plus"></i> إضافة المستخدم
                </button>
            </div>
        </form>
    </div>

    {{-- ══════════════════════════════════════════ --}}
    {{--  جدول المستخدمين --}}
    {{-- ══════════════════════════════════════════ --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-100 flex items-center justify-between">
            <h2 class="text-base font-bold text-gray-800 flex items-center gap-2">
                <span class="w-8 h-8 bg-slate-100 rounded-lg flex items-center justify-center text-slate-600">
                    <i class="fas fa-users text-sm"></i>
                </span>
                المستخدمون المضافون
            </h2>
            <span class="bg-indigo-50 text-indigo-600 text-xs font-bold px-3 py-1 rounded-full">
                {{ $subUsers->count() }} مستخدم
            </span>
        </div>

        @if($subUsers->isEmpty())
        <div class="py-16 text-center text-gray-400">
            <i class="fas fa-user-slash text-4xl mb-3 block opacity-30"></i>
            <p class="text-sm">لم تقم بإضافة أي مستخدمين بعد</p>
        </div>
        @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-100">
                        <th class="text-right px-5 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wider">#</th>
                        <th class="text-right px-5 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wider">الاسم</th>
                        <th class="text-right px-5 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wider">الوظيفة</th>
                        <th class="text-right px-5 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wider">البريد الإلكتروني</th>
                        <th class="text-right px-5 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wider">الحالة</th>
                        <th class="text-right px-5 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wider">تاريخ الإضافة</th>
                        <th class="text-center px-5 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wider">الإجراءات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50" id="sub-users-tbody">
                    @foreach($subUsers as $i => $sub)
                    <tr class="hover:bg-gray-50/50 transition group" id="sub-row-{{ $sub->id }}">
                        <td class="px-5 py-4 text-gray-400 text-xs">{{ $i + 1 }}</td>

                        {{-- الاسم --}}
                        <td class="px-5 py-4">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center font-bold text-sm flex-shrink-0">
                                    {{ mb_substr($sub->name, 0, 1) }}
                                </div>
                                <span class="font-semibold text-gray-700">{{ $sub->name }}</span>
                            </div>
                        </td>

                        {{-- الوظيفة --}}
                        <td class="px-5 py-4">
                            <span class="text-xs font-semibold px-2.5 py-1 rounded-full {{ $sub->role_badge }}">
                                {{ $sub->role_name }}
                            </span>
                        </td>

                        {{-- الإيميل --}}
                        <td class="px-5 py-4 text-gray-600 text-xs font-mono" dir="ltr">{{ $sub->email }}</td>

                        {{-- الحالة --}}
                        <td class="px-5 py-4">
                            @if($sub->is_active)
                                <span class="text-xs font-semibold text-green-700 bg-green-50 px-2.5 py-1 rounded-full">
                                    <i class="fas fa-circle text-[8px] ml-1"></i> نشط
                                </span>
                            @else
                                <span class="text-xs font-semibold text-gray-500 bg-gray-100 px-2.5 py-1 rounded-full">
                                    <i class="fas fa-circle text-[8px] ml-1"></i> معطّل
                                </span>
                            @endif
                        </td>

                        {{-- التاريخ --}}
                        <td class="px-5 py-4 text-gray-500 text-xs">
                            {{ $sub->created_at->format('Y/m/d') }}
                        </td>

                        {{-- الإجراءات --}}
                        <td class="px-5 py-4">
                            <div class="flex items-center justify-center gap-2 opacity-0 group-hover:opacity-100 transition">

                                {{-- تعديل --}}
                                <button onclick="openEdit({{ $sub->id }}, '{{ addslashes($sub->name) }}', '{{ $sub->email }}', '{{ $sub->role }}')"
                                        class="w-8 h-8 bg-blue-50 hover:bg-blue-100 text-blue-600 rounded-lg flex items-center justify-center transition" title="تعديل">
                                    <i class="fas fa-pen text-xs"></i>
                                </button>

                                {{-- تفعيل/تعطيل --}}
                                <form action="{{ route('sub-users.toggle', $sub) }}" method="POST">
                                    @csrf @method('PATCH')
                                    <button type="submit"
                                            class="w-8 h-8 {{ $sub->is_active ? 'bg-orange-50 hover:bg-orange-100 text-orange-600' : 'bg-green-50 hover:bg-green-100 text-green-600' }} rounded-lg flex items-center justify-center transition"
                                            title="{{ $sub->is_active ? 'تعطيل' : 'تفعيل' }}">
                                        <i class="fas {{ $sub->is_active ? 'fa-ban' : 'fa-check' }} text-xs"></i>
                                    </button>
                                </form>

                                {{-- حذف --}}
                                <button onclick="openDeleteModal({{ $sub->id }}, '{{ addslashes($sub->name) }}')"
                                        class="w-8 h-8 bg-red-50 hover:bg-red-100 text-red-600 rounded-lg flex items-center justify-center transition" title="حذف">
                                    <i class="fas fa-trash text-xs"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</div>

{{-- ══════════════════════════════════════════ --}}
{{--  مودال تأكيد الحذف --}}
{{-- ══════════════════════════════════════════ --}}
<div id="deleteModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm p-6 border border-gray-100">
        <div class="text-center mb-5">
            <div class="w-14 h-14 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-3">
                <i class="fas fa-user-minus text-red-500 text-xl"></i>
            </div>
            <h3 class="font-bold text-gray-800 text-base">تأكيد حذف المستخدم</h3>
            <p class="text-sm text-gray-500 mt-1">
                هل أنت متأكد من حذف المستخدم
                <span id="deleteUserName" class="font-bold text-gray-800"></span>؟
                <br>
                <span class="text-red-500 text-xs">لن يتمكن من الدخول للنظام بعد الحذف.</span>
            </p>
        </div>
        <input type="hidden" id="deleteUserId">
        <div class="flex gap-3">
            <button id="confirmDeleteBtn"
                    class="flex-1 bg-red-600 hover:bg-red-700 text-white py-2.5 rounded-xl text-sm font-bold transition flex items-center justify-center gap-2">
                <i class="fas fa-trash"></i> نعم، احذف
            </button>
            <button onclick="closeDeleteModal()"
                    class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 py-2.5 rounded-xl text-sm font-bold transition">
                إلغاء
            </button>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════ --}}
{{--  مودال التعديل --}}
{{-- ══════════════════════════════════════════ --}}
<div id="editModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-6 border border-gray-100">
        <div class="flex items-center justify-between mb-5">
            <h3 class="font-bold text-gray-800 flex items-center gap-2">
                <i class="fas fa-pen-to-square text-indigo-500"></i> تعديل بيانات المستخدم
            </h3>
            <button onclick="closeEdit()" class="w-8 h-8 rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-500 flex items-center justify-center transition">
                <i class="fas fa-times text-xs"></i>
            </button>
        </div>

        <form id="editForm" method="POST" class="space-y-4">
            @csrf @method('PUT')

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">الاسم الكامل</label>
                <input type="text" name="name" id="edit_name"
                       class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-300 outline-none transition">
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">البريد الإلكتروني</label>
                <input type="email" name="email" id="edit_email" dir="ltr"
                       class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-300 outline-none transition">
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">الوظيفة</label>
                <select name="role" id="edit_role"
                        class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-300 outline-none transition bg-white">
                    @foreach(\App\Support\Roles::ASSIGNABLE as $r)
                    <option value="{{ $r }}">{{ \App\Support\Roles::label($r) }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                    كلمة مرور جديدة
                    <span class="text-gray-400 font-normal">(اتركها فارغة إن لم ترد تغييرها)</span>
                </label>
                <div class="relative">
                    <input type="password" name="password" id="edit_password" placeholder="كلمة مرور جديدة" dir="ltr"
                           class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-300 outline-none transition">
                    <button type="button" onclick="togglePass('edit_password','eye_edit')"
                            class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 transition">
                        <i id="eye_edit" class="fas fa-eye text-sm"></i>
                    </button>
                </div>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit" class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white py-2.5 rounded-xl text-sm font-semibold transition">
                    حفظ التغييرات
                </button>
                <button type="button" onclick="closeEdit()"
                        class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 py-2.5 rounded-xl text-sm font-semibold transition">
                    إلغاء
                </button>
            </div>
        </form>
    </div>
</div>

@endsection

@section('scripts')
<script>
const CSRF = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

// ══════════════════════════════════════
//  إظهار/إخفاء الباسورد
// ══════════════════════════════════════
function togglePass(inputId, eyeId) {
    const input = document.getElementById(inputId);
    const eye   = document.getElementById(eyeId);
    if (input.type === 'password') {
        input.type = 'text';
        eye.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        input.type = 'password';
        eye.classList.replace('fa-eye-slash', 'fa-eye');
    }
}

// ══════════════════════════════════════
//  مودال التعديل
// ══════════════════════════════════════
function openEdit(id, name, email, role) {
    document.getElementById('edit_name').value     = name;
    document.getElementById('edit_email').value    = email;
    document.getElementById('edit_role').value     = role;
    document.getElementById('edit_password').value = '';
    document.getElementById('editForm').action     = `/sub-users/${id}`;
    document.getElementById('editModal').classList.remove('hidden');
}

function closeEdit() {
    document.getElementById('editModal').classList.add('hidden');
}

document.getElementById('editModal').addEventListener('click', function(e) {
    if (e.target === this) closeEdit();
});

// ══════════════════════════════════════
//  مودال الحذف
// ══════════════════════════════════════
function openDeleteModal(id, name) {
    document.getElementById('deleteUserId').value     = id;
    document.getElementById('deleteUserName').textContent = name;
    document.getElementById('deleteModal').classList.remove('hidden');
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.add('hidden');
}

document.getElementById('deleteModal').addEventListener('click', function(e) {
    if (e.target === this) closeDeleteModal();
});

document.getElementById('confirmDeleteBtn').addEventListener('click', async function () {
    const id  = document.getElementById('deleteUserId').value;
    const btn = this;

    btn.disabled     = true;
    btn.innerHTML    = '<i class="fas fa-spinner fa-spin"></i> جارٍ الحذف...';

    try {
        const res  = await fetch(`/sub-users/${id}`, {
            method:  'DELETE',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        });
        const data = await res.json();

        if (res.ok && data.success) {
            // إخفاء الصف بأنيميشن
            const row = document.getElementById(`sub-row-${id}`);
            if (row) {
                row.style.transition = 'opacity 0.3s, transform 0.3s';
                row.style.opacity    = '0';
                row.style.transform  = 'translateX(20px)';
                setTimeout(() => row.remove(), 300);
            }
            closeDeleteModal();
        } else {
            closeDeleteModal();
            alert(data.message ?? 'حدث خطأ أثناء الحذف');
        }
    } catch (e) {
        closeDeleteModal();
        alert('حدث خطأ في الاتصال');
    } finally {
        btn.disabled  = false;
        btn.innerHTML = '<i class="fas fa-trash"></i> نعم، احذف';
    }
});
</script>
@endsection