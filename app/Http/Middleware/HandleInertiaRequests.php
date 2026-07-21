<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user() ? [
                    'id' => $request->user()->id,
                    'first_name' => $request->user()->first_name,
                    'last_name' => $request->user()->last_name,
                    'username' => $request->user()->username,
                    'email' => $request->user()->email,
                    'division_id' => $request->user()->division_id,
                    'area_id' => $request->user()->area_id,
                    'roles' => $request->user()->getRoleNames(),
                    'permissions' => $request->user()->hasRole('Developer') 
                        ? \Spatie\Permission\Models\Permission::pluck('name') 
                        : $request->user()->getAllPermissions()->pluck('name'),
                    'settings' => $request->user()->settings,
                ] : null,
                'is_impersonating' => $request->session()->has('impersonator_id'),
                'impersonator_name' => $request->session()->get('impersonator_name'),
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'message' => fn () => $request->session()->get('message'),
            ],
        ];
    }
}
