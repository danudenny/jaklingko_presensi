<?php

namespace App\Http\Controllers;

use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $users = User::all();

        if ($request->ajax()) {
            if ($request->header('X-Requested-With') === 'XMLHttpRequest') {
                return view('modules.admin.users.index', compact('users'))->render();
            }

            return response()->json([
                'success' => true,
                'data' => $users
            ]);
        }

        return view('modules.admin.users.index', compact('users'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'is_superadmin' => ['sometimes', 'boolean'],
        ]);

        try {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'is_superadmin' => $request->has('is_superadmin'),
            ]);

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'User created successfully.',
                    'data' => $user
                ]);
            }

            return redirect()->route('users.index')
                ->with('success', 'User created successfully.');
        } catch (Exception $e) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create user.',
                    'error' => $e->getMessage()
                ], 500);
            }

            return redirect()->back()
                ->with('error', 'Failed to create user: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'password' => ['nullable', 'confirmed', Rules\Password::defaults()],
            'is_superadmin' => ['sometimes', 'boolean'],
        ]);

        try {
            $userData = [
                'name' => $validated['name'],
                'email' => $validated['email'],
                'is_superadmin' => $request->has('is_superadmin'),
            ];

            // Only update password if provided
            if ($request->filled('password')) {
                $userData['password'] = Hash::make($validated['password']);
            }

            $user->update($userData);

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'User updated successfully.',
                    'data' => $user
                ]);
            }

            return redirect()->route('users.index')
                ->with('success', 'User updated successfully.');
        } catch (Exception $e) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update user.',
                    'error' => $e->getMessage()
                ], 500);
            }

            return redirect()->back()
                ->with('error', 'Failed to update user: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, User $user)
    {
        // Prevent deleting yourself
        if ($user->id === Auth::id()) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You cannot delete your own account.'
                ]);
            }

            return redirect()->route('users.index')
                ->with('error', 'You cannot delete your own account.');
        }

        try {
            $user->delete();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'User deleted successfully.'
                ]);
            }

            return redirect()->route('users.index')
                ->with('success', 'User deleted successfully.');
        } catch (Exception $e) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete user.',
                    'error' => $e->getMessage()
                ], 500);
            }

            return redirect()->back()
                ->with('error', 'Failed to delete user: ' . $e->getMessage());
        }
    }
}
