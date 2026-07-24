<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DemoLoginController extends Controller
{
    public function __invoke(Request $request, string $role)
    {
        abort_unless(in_array($role, ['admin', 'manager', 'employee']), 404);

        $user = User::where('email', $role.'@acme.test')->firstOrFail();

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('home');
    }
}
