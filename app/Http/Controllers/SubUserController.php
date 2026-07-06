<?php

namespace App\Http\Controllers;

use App\Models\SubUser;
use App\Models\User;
use App\Support\Roles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class SubUserController extends Controller
{
    // ✅ كل user عادي مسجّل هو أدمن صيدليته — الـ sub-user بس هو اللي ممنوع
    private function ensureIsAdmin(): void
    {
        if (session()->has('sub_user')) {
            abort(403, 'غير مصرح.');
        }
    }

    public function index()
    {
        $this->ensureIsAdmin();

        // كل أدمن يشوف الـ sub-users بتوعه بس
        $subUsers = SubUser::where('owner_id', Auth::id())->latest()->get();

        return view('sub-users.index', compact('subUsers'));
    }

    public function store(Request $request)
    {
        $this->ensureIsAdmin();

        $request->validate([
            'name'     => 'required|string|max:100',
            'email'    => 'required|email|unique:sub_users,email',
            'password' => 'required|string|min:6',
            'role'     => ['required', Rule::in(Roles::ASSIGNABLE)],
        ], [
            'name.required'     => 'الاسم مطلوب',
            'email.required'    => 'البريد الإلكتروني مطلوب',
            'email.email'       => 'البريد الإلكتروني غير صالح',
            'email.unique'      => 'البريد الإلكتروني مستخدم بالفعل',
            'password.required' => 'كلمة المرور مطلوبة',
            'password.min'      => 'كلمة المرور 6 أحرف على الأقل',
            'role.required'     => 'الوظيفة مطلوبة',
        ]);

        SubUser::create([
            'owner_id' => Auth::id(),
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'role'     => $request->role,
        ]);

        return back()->with('success', 'تم إضافة المستخدم بنجاح ✅');
    }

    public function update(Request $request, SubUser $subUser)
    {
        $this->ensureIsAdmin();

        // تأكد إن الـ sub-user ده تابع للأدمن الحالي
        abort_if($subUser->owner_id !== Auth::id(), 403);

        $request->validate([
            'name'  => 'required|string|max:100',
            'email' => 'required|email|unique:sub_users,email,' . $subUser->id,
            'role'  => ['required', Rule::in(Roles::ASSIGNABLE)],
        ], [
            'name.required'  => 'الاسم مطلوب',
            'email.required' => 'البريد الإلكتروني مطلوب',
            'email.email'    => 'البريد الإلكتروني غير صالح',
            'email.unique'   => 'البريد الإلكتروني مستخدم بالفعل',
            'role.required'  => 'الوظيفة مطلوبة',
        ]);

        $data = [
            'name'  => $request->name,
            'email' => $request->email,
            'role'  => $request->role,
        ];

        if ($request->filled('password')) {
            $request->validate(['password' => 'min:6'], ['password.min' => 'كلمة المرور 6 أحرف على الأقل']);
            $data['password'] = Hash::make($request->password);
        }

        $subUser->update($data);

        return back()->with('success', 'تم تحديث البيانات ✅');
    }

    // ── حذف — يرجع JSON عشان الـ AJAX في الـ view ──
    public function destroy(SubUser $subUser)
    {
        $this->ensureIsAdmin();

        // تأكد إن الـ sub-user ده تابع للأدمن الحالي
        abort_if($subUser->owner_id !== Auth::id(), 403);

        $subUser->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف المستخدم بنجاح',
        ]);
    }

    public function toggleActive(SubUser $subUser)
    {
        $this->ensureIsAdmin();

        // تأكد إن الـ sub-user ده تابع للأدمن الحالي
        abort_if($subUser->owner_id !== Auth::id(), 403);

        $subUser->update(['is_active' => !$subUser->is_active]);

        return back()->with('success', $subUser->is_active ? 'تم تفعيل المستخدم ✅' : 'تم تعطيل المستخدم ✅');
    }
}