<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    // Show all users.
    public function index(Request $request)
    {
        // Add search support.
        $search = trim((string) $request->input('search', ''));

        $users = User::with('roles')
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->paginate(10) // Paginate users.
            ->withQueryString();

        $roles = Role::whereIn('name', ['pemilik', 'pegawai', 'penyewa'])->get();

        return view('users.index', compact('users', 'roles', 'search'));
    }

    // Update a user's role.
    public function updateRole(Request $request, User $user)
    {
        $request->validate([
            'role' => ['required', Rule::in(['pemilik', 'pegawai', 'penyewa'])],
        ], [
            'role.required' => 'Peran pengguna wajib dipilih.',
            'role.in' => 'Peran yang dipilih tidak diizinkan.',
        ]);

        $user->syncRoles([$request->role]);

        return redirect()->route('pemilik.users.index')->with('success', 'Peran berhasil diubah!');
    }
}
