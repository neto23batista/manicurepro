<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUsuarioRequest;
use App\Http\Requests\UpdateUsuarioRequest;
use App\Models\Salao;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UsuarioController extends Controller
{
    public function index(Request $request)
    {
        $users = User::with('salao')
            ->when($request->role, fn($q) => $q->where('role', $request->role))
            ->when($request->search, function ($q) use ($request) {
                $q->where(function ($q2) use ($request) {
                    $q2->where('name', 'like', '%' . $request->search . '%')
                       ->orWhere('email', 'like', '%' . $request->search . '%');
                });
            })
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.usuarios.index', compact('users'));
    }

    public function create()
    {
        return view('admin.usuarios.create', [
            'saloes' => Salao::where('ativo', true)->orderBy('nome')->get(),
        ]);
    }

    public function store(StoreUsuarioRequest $request)
    {
        User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'role'     => $request->role,
            'salao_id' => $request->salao_id,
            'phone'    => $request->phone,
            'ativo'    => $request->boolean('ativo', true),
        ]);

        return redirect()
            ->route('admin.usuarios.index')
            ->with('success', 'Usuário criado com sucesso!');
    }

    public function edit(User $usuario)
    {
        return view('admin.usuarios.edit', [
            'usuario' => $usuario,
            'saloes'  => Salao::where('ativo', true)->orderBy('nome')->get(),
        ]);
    }

    public function update(UpdateUsuarioRequest $request, User $usuario)
    {
        $data = [
            'name'     => $request->name,
            'email'    => $request->email,
            'role'     => $request->role,
            'salao_id' => $request->salao_id,
            'phone'    => $request->phone,
            'ativo'    => $request->boolean('ativo', true),
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $usuario->update($data);

        return redirect()
            ->route('admin.usuarios.index')
            ->with('success', 'Usuário atualizado com sucesso!');
    }

    public function destroy(User $usuario)
    {
        if ($usuario->id === auth()->id()) {
            return back()->withErrors(['error' => 'Você não pode excluir sua própria conta.']);
        }

        $usuario->update(['ativo' => false]);

        return back()->with('success', 'Usuário desativado.');
    }
}
