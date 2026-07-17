<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ImpersonationController extends Controller
{
    /**
     * Start impersonating the given user.
     */
    public function start(Request $request, User $user)
    {
        // Only Developers can impersonate
        if (!$request->user()->hasRole('Developer')) {
            abort(403, 'Only developers can impersonate users.');
        }

        // Store the original user's ID in the session
        $request->session()->put('impersonator_id', $request->user()->id);
        $request->session()->put('impersonator_name', $request->user()->first_name . ' ' . $request->user()->last_name);

        // Login as the target user
        Auth::login($user);

        return redirect()->route('dashboard')->with('status', 'You are now impersonating ' . $user->first_name . ' ' . $user->last_name . '.');
    }

    /**
     * Leave impersonation and return to the original user.
     */
    public function leave(Request $request)
    {
        // Check if the user is currently impersonating
        if (!$request->session()->has('impersonator_id')) {
            return redirect()->route('dashboard');
        }

        // Get the original user ID and clear it from session
        $originalId = $request->session()->pull('impersonator_id');
        $request->session()->forget('impersonator_name');

        // Log back in as the original user
        Auth::loginUsingId($originalId);

        return redirect()->route('users.index')->with('status', 'Welcome back! Impersonation ended.');
    }
}
