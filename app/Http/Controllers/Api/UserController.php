<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index()
    {
        return response()->json(
            User::where('role', 'petugas')
                ->select('id', 'name', 'username', 'is_active', 'created_at')
                ->orderBy('name')
                ->get()
        );
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'username' => 'required|string|max:191|unique:users',
            'password' => 'required|string|min:6',
        ]);

        $user = User::create([
            'name'      => $request->name,
            'username'  => $request->username,
            'password'  => Hash::make($request->password),
            'role'      => 'petugas',
            'is_active' => true,
        ]);

        return response()->json([
            'message' => 'Akun petugas berhasil dibuat.',
            'user'    => $user->only('id', 'name', 'username', 'role', 'is_active'),
        ], 201);
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'name'     => 'sometimes|string|max:255',
            'username' => 'sometimes|string|max:191|unique:users,username,' . $user->id,
            'password' => 'sometimes|string|min:6',
        ]);

        if ($request->filled('name'))     $user->name     = $request->name;
        if ($request->filled('username')) $user->username = $request->username;
        if ($request->filled('password')) $user->password = Hash::make($request->password);

        $user->save();

        return response()->json([
            'message' => 'Data petugas berhasil diperbarui.',
            'user'    => $user->only('id', 'name', 'username', 'role', 'is_active'),
        ]);
    }

    public function toggle(User $user)
    {
        if ($user->role === 'admin') {
            return response()->json([
                'message' => 'Akun admin tidak bisa dinonaktifkan.'
            ], 403);
        }

        $user->is_active = !$user->is_active;
        $user->save();

        return response()->json([
            'message'   => 'Status akun berhasil diubah.',
            'is_active' => $user->is_active,
        ]);
    }
}
