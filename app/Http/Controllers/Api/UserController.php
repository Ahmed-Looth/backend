<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    // List all users
    public function index(Request $request)
    {
        if (! $request->user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 403);
        }

        $users = User::with('role')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $users,
        ]);
    }

    // Admin -> user only
    // Superadmin -> superadmin, admin, user
    public function store(Request $request)
    {
        $authUser = $request->user();

        if (! $authUser->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'ends_with:@polytechnic.edu.mv', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role_id' => ['required', 'exists:roles,id'],
        ]);

        $role = Role::findOrFail($validated['role_id']);

        // Enforce Roles
        if ($authUser->isAdmin() && ! $authUser->isSuperAdmin()) {
            if (in_array($role->name, ['admin', 'superadmin'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only superadmin can assign admin or superadmin roles, please contact IT section.',
                ], 403);
            }
        }

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => bcrypt($validated['password']),
            'role_id' => $role->id,
            'is_active' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'User created successfully.',
            'data' => $user->load('role'),
        ], 201);
    }

    public function changeRole(User $user, Request $request)
    {
        $authUser = $request->user();

        if (! $authUser->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 403);
        }

        $validated = $request->validate([
            'role_id' => ['required', 'exists:roles,id'],
        ]);

        $role = Role::findOrFail($validated['role_id']);

        // Prevent superadmin from changing their own role
        if ($user->id === $authUser->id && $role->name !== 'superadmin') {
            return response()->json([
                'success' => false,
                'message' => 'No...don\'t do that.',
            ], 422);
        }

        $user->update([
            'role_id' => $role->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'User role updated.',
            'data' => $user->load('role'),
        ]);
    }

    public function deactivate(User $user, Request $request)
    {
        $authUser = $request->user();

        if (! $authUser->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 403);
        }

        if ($user->id === $authUser->id) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot deactivate yourself.',
            ], 422);
        }

        $old = $user->getOriginal();

        $user->update(['is_active' => false]);

        audit(
            'user_deactivated',
            $user,
            $old,
            $user->fresh()->toArray()
        );

        return response()->json([
            'success' => true,
            'message' => 'User deactivated.',
        ]);
    }
}
