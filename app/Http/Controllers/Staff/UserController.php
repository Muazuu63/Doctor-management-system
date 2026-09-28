<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        return view('staff.users', [
            'rows' => User::query()->orderBy('id')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:191'],
            'name_bn' => ['nullable', 'string', 'max:191'],
            'email' => ['required', 'email', 'max:191', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:20'],
            'password' => ['required', 'string', 'min:8', 'max:100'],
            'role' => ['required', Rule::in(['super_admin', 'admin', 'manager'])],
        ]);
        $phone = normalize_phone($data['phone']);
        if (! valid_bd_phone($phone)) {
            return back()->with('err', 'সঠিক বাংলাদেশি মোবাইল দিন।')->withInput();
        }
        if (User::query()->where('phone', $phone)->exists()) {
            return back()->with('err', 'এই ফোন আগেই ব্যবহৃত।')->withInput();
        }
        if ($data['role'] === 'super_admin' && ! $request->user()->isSuperAdmin()) {
            abort(403);
        }
        User::create([
            'name' => $data['name'],
            'name_bn' => $data['name_bn'] ?? '',
            'email' => $data['email'],
            'phone' => $phone,
            'password' => Hash::make($data['password']),
            'role' => $data['role'],
            'is_active' => true,
            'two_factor_enabled' => true,
            'two_factor_method' => 'sms',
        ]);

        return redirect()->route('staff.users')->with('ok', 'ইউজার তৈরি হয়েছে।');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:191'],
            'role' => ['required', Rule::in(['super_admin', 'admin', 'manager'])],
            'is_active' => ['nullable'],
            'password' => ['nullable', 'string', 'min:8', 'max:100'],
        ]);
        if ($user->isSuperAdmin() && $user->id === $request->user()->id && $data['role'] !== 'super_admin') {
            return back()->with('err', 'নিজের সুপার অ্যাডমিন রোল সরাতে পারবেন না।');
        }
        $payload = [
            'name' => $data['name'],
            'role' => $data['role'],
            'is_active' => $request->boolean('is_active'),
        ];
        if (! empty($data['password'])) {
            $payload['password'] = Hash::make($data['password']);
        }
        $user->update($payload);

        return redirect()->route('staff.users')->with('ok', 'ইউজার আপডেট হয়েছে।');
    }
}
