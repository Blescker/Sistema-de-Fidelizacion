<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends Controller
{
    /** "/": cada tipo de usuario va a su inicio */
    public function redirect(Request $request): RedirectResponse
    {
        return $request->user()->isSuperAdmin()
            ? redirect()->route('super.businesses.index')
            : redirect()->route('dashboard');
    }

    public function dashboard(): View
    {
        return view('dashboard', [
            'branchesCount' => Branch::where('is_active', true)->count(),
            'usersCount' => User::where('is_active', true)->count(),
        ]);
    }
}
