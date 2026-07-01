<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreManicureRequest;
use App\Http\Requests\UpdateManicureRequest;
use App\Models\Manicure;
use App\Models\Salao;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class ManicureController extends Controller
{
    public function index()
    {
        $manicures = Manicure::with('salao')->paginate(20);
        return view('admin.manicures.index', compact('manicures'));
    }

    public function create()
    {
        $saloes = Salao::where('ativo', true)->orderBy('nome')->get();
        return view('admin.manicures.create', compact('saloes'));
    }

    public function store(StoreManicureRequest $request)
    {
        $data = $request->validated();

        $user = User::create([
            'name'     => $data['nome'],
            'email'    => $data['email'],
            'password' => Hash::make($data['password']),
            'role'     => UserRole::Manicure->value,
            'salao_id' => $data['salao_id'],
            'phone'    => $data['telefone'] ?? null,
        ]);

        $foto = null;
        if ($request->hasFile('foto')) {
            $foto = $request->file('foto')->store('manicures', 'public');
            $user->update(['avatar' => $foto]);
        }

        Manicure::create([
            'user_id'  => $user->id,
            'salao_id' => $data['salao_id'],
            'nome'     => $data['nome'],
            'email'    => $data['email'],
            'telefone' => $data['telefone'] ?? null,
            'bio'      => $data['bio'] ?? null,
            'comissao' => $data['comissao'] ?? 40,
            'foto'     => $foto,
        ]);

        return redirect()->route('admin.manicures.index')
            ->with('success', 'Manicure cadastrada com sucesso!');
    }

    public function edit(Manicure $manicure)
    {
        $saloes = Salao::where('ativo', true)->orderBy('nome')->get();
        return view('admin.manicures.edit', compact('manicure', 'saloes'));
    }

    public function update(UpdateManicureRequest $request, Manicure $manicure)
    {
        $data = $request->validated();

        $fotoPath = $manicure->foto;
        if ($request->hasFile('foto')) {
            if ($manicure->foto && Storage::disk('public')->exists($manicure->foto)) {
                Storage::disk('public')->delete($manicure->foto);
            }
            $fotoPath = $request->file('foto')->store('manicures', 'public');
        }

        $manicure->update([
            'salao_id' => $data['salao_id'],
            'nome'     => $data['nome'],
            'email'    => $data['email'] ?? null,
            'telefone' => $data['telefone'] ?? null,
            'bio'      => $data['bio'] ?? null,
            'comissao' => $data['comissao'] ?? $manicure->comissao,
            'ativo'    => $request->boolean('ativo'),
            'foto'     => $fotoPath,
        ]);

        if ($manicure->user) {
            $userUpdate = [
                'name'     => $data['nome'],
                'email'    => $data['email'] ?? $manicure->user->email,
                'salao_id' => $data['salao_id'],
                'phone'    => $data['telefone'] ?? null,
                'ativo'    => $request->boolean('ativo'),
            ];
            if (!empty($data['password'])) {
                $userUpdate['password'] = Hash::make($data['password']);
            }
            $manicure->user->update($userUpdate);
        }

        return redirect()->route('admin.manicures.index')
            ->with('success', 'Manicure atualizada com sucesso!');
    }

    public function destroy(Manicure $manicure)
    {
        if ($manicure->user) {
            $manicure->user->update(['ativo' => false]);
        }
        $manicure->update(['ativo' => false]);

        return back()->with('success', 'Manicure desativada.');
    }
}
