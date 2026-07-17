<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Department;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Domain\Users\Actions\GetUsersAction;
use App\Domain\Users\Actions\CreateUserAction;
use App\Domain\Users\Actions\UpdateUserAction;
use App\Domain\Users\Actions\DeleteUserAction;
use App\Domain\Users\DTOs\UserDTO;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public function index(Request $request, GetUsersAction $action)
    {
        $users = $action->execute([
            'search' => $request->search,
            'per_page' => $request->per_page,
        ]);
        
        $roles = Role::whereNotIn('name', ['Developer'])->get();
        if (!auth()->user()->hasRole('Developer')) {
             if (auth()->user()->hasRole('Superadmin')) {
                 // Superadmin can assign anything except Developer
             } else {
                 // Admin can only assign Encoder, Secretary, Admin
                 $roles = Role::whereIn('name', ['Admin', 'Encoder', 'Secretary'])->get();
             }
        } else {
             $roles = Role::all();
        }

        $departments = Department::all();

        return Inertia::render('Users/Index', [
            'users' => $users,
            'filters' => $request->only(['search', 'per_page']),
            'roles' => $roles,
            'departments' => $departments,
        ]);
    }

    public function store(Request $request, CreateUserAction $action)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users',
            'email' => 'required|string|email|max:255|unique:users',
            'contact_number' => 'nullable|string|max:20',
            'department_id' => 'nullable|exists:departments,id',
            'role' => 'required|exists:roles,name',
            'password' => 'required|string|min:6',
        ]);

        DB::transaction(function () use ($validated, $action) {
            $action->execute(UserDTO::fromArray($validated));
        });

        return redirect()->back()->with('success', 'User created successfully.');
    }

    public function update(Request $request, User $user, UpdateUserAction $action)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users,username,' . $user->id,
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'contact_number' => 'nullable|string|max:20',
            'department_id' => 'nullable|exists:departments,id',
            'role' => 'required|exists:roles,name',
            'password' => 'nullable|string|min:6',
        ]);

        DB::transaction(function () use ($user, $validated, $action) {
            $action->execute($user, UserDTO::fromArray($validated));
        });

        return redirect()->back()->with('success', 'User updated successfully.');
    }

    public function destroy(User $user, DeleteUserAction $action)
    {
        DB::transaction(function () use ($user, $action) {
            $action->execute($user);
        });

        return redirect()->back()->with('success', 'User deleted successfully.');
    }
}
