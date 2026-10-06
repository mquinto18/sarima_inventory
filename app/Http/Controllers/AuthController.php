<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();

            // Login is a full-page POST, so an overlay shown on submit dies at
            // the document swap. Flashing a marker lets the destination page
            // play the splash instead.
            //
            // Always the dashboard, not intended(): a session that times out
            // (or a laptop that sleeps) while someone's on, say, /inventory
            // redirects them to /login, which stores that as the "intended"
            // URL - logging back in would otherwise drop them right back
            // where they were instead of the dashboard.
            return redirect('/')->with('justLoggedIn', true);
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
