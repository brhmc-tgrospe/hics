<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): Response
    {
        $user = $request->user()->load(['division', 'area', 'roles']);

        $showArea = !$user->hasAnyRole(['Admin', 'Superadmin', 'Developer']);

        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => session('status'),
            'division_area_name' => (function() use ($user) {
                $div = $user->division ? "({$user->division_id}) {$user->division->div_name}" : 'No Division';
                if ($user->area) {
                    $area = "({$user->area_id}) {$user->area->area_name}";
                    return "{$div} - {$area}";
                }
                return $div;
            })(),
            'role_name' => $user->roles->first() ? $user->roles->first()->name : 'No Role',
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $user = $request->user();

        // If username was already changed, prevent it from being updated
        if ($user->username_changed && isset($validated['username'])) {
            unset($validated['username']);
        } elseif (isset($validated['username']) && $validated['username'] !== $user->username) {
            $user->username_changed = true;
        }

        if ($request->has('settings')) {
            $validated['settings'] = array_merge($user->settings ?? [], $request->input('settings'));
        }

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        return Redirect::route('profile.edit');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
