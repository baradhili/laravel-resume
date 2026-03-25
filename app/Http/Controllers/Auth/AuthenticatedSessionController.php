<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Illuminate\Support\Facades\Log;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        try {
            // Log what is being sent
            Log::info('Login attempt for:', ['email' => $request->email]);

            $request->authenticate();

            $request->session()->regenerate();
            return redirect()->intended(route('dashboard', absolute: false));

        } catch (\Illuminate\Validation\ValidationException $e) {
            // Log the specific error message
            Log::error('Login failed:', $e->errors());

            // Manual check: Does the user exist at all?
            $userExists = \App\Models\User::where('email', $request->email)->exists();
            Log::info('Does user exist in SQLite?', ['exists' => $userExists]);

            throw $e; // Re-throw so the UI still shows the error
        }
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
