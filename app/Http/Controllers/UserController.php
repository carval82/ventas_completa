<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $query = User::query();

        if($request->filled('search')) {
            $search = $request->search;
            $query->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%");
        }

        if($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        $users = $query->latest()->paginate(10);
        return view('configuracion.users.index', compact('users'));
    }

    public function create()
    {
        $roles = Role::where('name', '!=', 'Super Admin')->get();
        return view('configuracion.users.create', compact('roles'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => ['required', 'confirmed', Password::defaults()],
            'role' => 'required|exists:roles,name',
            'estado' => 'required|boolean'
        ]);

        try {
            DB::beginTransaction();

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'estado' => $request->estado
            ]);

            $user->assignRole($request->role);

            DB::commit();
            return redirect()->route('users.index')
                           ->with('success', 'Usuario creado exitosamente');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error al crear el usuario')
                        ->withInput();
        }
    }

    public function show(User $usuario)
    {
        $usuario->load('roles.permissions');
        return view('configuracion.users.show', compact('usuario'));
    }

    public function edit(User $usuario)
    {
        $roles = Role::where('name', '!=', 'Super Admin')->get();
        return view('configuracion.users.edit', compact('usuario', 'roles'));
    }

    public function update(Request $request, User $usuario)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $usuario->id,
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'role' => 'required|exists:roles,name',
            'estado' => 'required|boolean'
        ]);

        try {
            DB::beginTransaction();

            $data = $request->except(['password', 'role']);
            
            if ($request->filled('password')) {
                $data['password'] = Hash::make($request->password);
            }

            $usuario->update($data);
            
            // Actualizar rol
            $usuario->syncRoles([$request->role]);

            DB::commit();
            return redirect()->route('users.index')
                           ->with('success', 'Usuario actualizado exitosamente');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error al actualizar el usuario')
                        ->withInput();
        }
    }

    public function destroy(User $usuario)
    {
        try {
            $usuario->delete();
            return redirect()->route('users.index')
                           ->with('success', 'Usuario eliminado exitosamente');
        } catch (\Exception $e) {
            return back()->with('error', 'Error al eliminar el usuario');
        }
    }

    public function changePassword(Request $request, User $usuario)
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()]
        ]);

        try {
            $usuario->update([
                'password' => Hash::make($request->password)
            ]);

            return back()->with('success', 'Contraseña actualizada exitosamente');
        } catch (\Exception $e) {
            return back()->with('error', 'Error al actualizar la contraseña');
        }
    }

    public function toggleStatus(User $usuario)
    {
        try {
            $usuario->update([
                'estado' => !$usuario->estado
            ]);

            return back()->with('success', 'Estado del usuario actualizado exitosamente');
        } catch (\Exception $e) {
            return back()->with('error', 'Error al actualizar el estado del usuario');
        }
    }
}