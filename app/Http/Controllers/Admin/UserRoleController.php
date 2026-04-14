<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class UserRoleController extends Controller
{
    public function index()
    {
        $users = User::query()
            ->with('roles')
            ->orderBy('name')
            ->get();

        $roles = Role::orderBy('name')->get();

        return view('admin.users.roles', compact('users', 'roles'));
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'role' => ['required', 'exists:roles,name'],
        ]);

        // Optional: verhindern, dass man sich selbst den Admin nimmt
        if (auth()->id() === $user->id && $validated['role'] !== 'admin') {
            return back()->with('error', 'Du kannst dir die Admin-Rolle nicht selbst entfernen.');
        }

        $user->syncRoles([$validated['role']]);

        return back()->with('success', 'Rolle erfolgreich aktualisiert.');
    }
}