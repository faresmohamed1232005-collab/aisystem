<?php
namespace App\Http\Controllers\Auth;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class RegisterController extends Controller
{
    public function showForm() { return view('auth.register'); }

    public function register(Request $request)
    {
        $request->validate([
            'name'           => 'required|string|max:255',
            'email'          => 'required|email|unique:users',
            'password'       => 'required|min:8|confirmed',
            'phone'          => 'required|string|max:20',
            'pharmacy_name'  => 'required|string|max:255',
            'address'        => 'required|string',
            'governorate'    => 'required|string',
            'city'           => 'required|string',
            'syndicate_card' => 'required|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        $cardPath = $request->file('syndicate_card')->store('syndicate_cards', 'public');

        $user = User::create([
            'name'           => $request->name,
            'email'          => $request->email,
            'password'       => Hash::make($request->password),
            'phone'          => $request->phone,
            'pharmacy_name'  => $request->pharmacy_name,
            'address'        => $request->address,
            'governorate'    => $request->governorate,
            'city'           => $request->city,
            'syndicate_card' => $cardPath,
            'is_approved'    => true, // يمكن تغييره لـ false لو عايز موافقة
        ]);

        Auth::login($user);
        return redirect()->route('dashboard')->with('success', 'تم التسجيل بنجاح!');
    }
}