<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    private function redirectByRole(User $user): string
    {
        return match ($user->role) {
            'admin' => route('admin.index'),
            'teacher' => route('teacher.home'),
            default => route('student.dashboard'),
        };
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6|confirmed',
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => User::query()->count() === 0 ? 'admin' : 'student',
            'password' => Hash::make($data['password']),
        ]);

        Auth::login($user);

        return redirect()->to($this->redirectByRole($user));
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();

            /** @var User $user */
            $user = Auth::user();

            return redirect()->intended($this->redirectByRole($user));
        }

        return back()->withErrors(['email' => 'Неверные данные для входа']);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}


