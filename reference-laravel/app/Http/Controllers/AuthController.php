<?php
namespace App\Http\Controllers;

use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function show() { return view('login'); }

    public function login(Request $r)
    {
        $c = $r->validate(['email' => 'required|email', 'password' => 'required']);

        if (Auth::attempt($c)) {
            $r->session()->regenerate();
            Audit::log('Signed in');
            return redirect('/');
        }

        Audit::log('Failed sign-in', $c['email']);
        return back()->withErrors(['email' => 'Email or password is incorrect.'])->onlyInput('email');
    }

    public function logout(Request $r)
    {
        Audit::log('Signed out');
        Auth::logout();
        $r->session()->invalidate();
        $r->session()->regenerateToken();
        return redirect('/login');
    }
}
