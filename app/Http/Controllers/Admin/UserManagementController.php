<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class UserManagementController extends Controller
{
    public function index()
    {
        $users = User::query()
            ->with('roles')
            ->orderBy('name')
            ->paginate(15);

        return view('admin.users.index', compact('users'));
    }

    public function edit(User $user)
    {
        $roles = Role::query()
            ->orderBy('name')
            ->get();

        return view('admin.users.edit', compact('user', 'roles'));
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => [
                'required',
                'string',
                'max:255',
                Rule::unique('users', 'username')->ignore($user->id),
            ],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'role' => ['nullable', 'exists:roles,name'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $user->name = $validated['name'];
        $user->username = $validated['username'];
        $user->email = $validated['email'];
        $user->is_active = (bool) ($validated['is_active'] ?? false);

        if (!empty($validated['password'])) {
            $user->password = $validated['password'];
        }

        $user->save();

        if (!empty($validated['role'])) {
            if (auth()->id() === $user->id && $validated['role'] !== 'admin') {
                return back()->with('error', 'Du kannst dir die Admin-Rolle nicht selbst entfernen.');
            }

            $user->syncRoles([$validated['role']]);
        }

        return redirect()
            ->route('admin.users.edit', $user)
            ->with('success', 'Benutzer wurde erfolgreich aktualisiert.');
    }

    public function toggleActive(User $user)
    {
        if (auth()->id() === $user->id && $user->is_active) {
            return back()->with('error', 'Du kannst deinen eigenen Account nicht deaktivieren.');
        }

        $user->is_active = ! $user->is_active;
        $user->save();

        return back()->with('success', 'Benutzerstatus wurde aktualisiert.');
    }

    public function destroy(User $user)
    {
        if (auth()->id() === $user->id) {
            return back()->with('error', 'Du kannst deinen eigenen Account nicht löschen.');
        }

        $user->delete();

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Benutzer wurde gelöscht.');
    }
}