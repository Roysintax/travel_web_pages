<?php

namespace App\Http\Controllers;

use App\Http\Middleware\AdminAuthentication;
use App\Http\Requests\AdminLoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AdminSessionController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        return AdminAuthentication::allowsLocalAccess($request) || Auth::user()?->is_admin ? redirect()->route('admin.dashboard') : view('admin.login');
    }

    public function store(AdminLoginRequest $request): RedirectResponse
    {
        if (! Auth::attempt($request->safe()->only(['email', 'password']) + ['is_admin' => true])) {
            throw ValidationException::withMessages(['email' => 'Email atau password admin tidak sesuai.']);
        }
        $request->session()->regenerate();

        return redirect()->route('admin.dashboard');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
