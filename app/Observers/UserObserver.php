<?php

namespace App\Observers;

use App\Models\User;

/**
 * Mantém os registros Manicure/Cliente sincronizados com o User
 * quando nome/email/telefone/avatar são atualizados.
 *
 * Esses três modelos historicamente armazenam dados denormalizados
 * (nome, email, telefone, foto) — antes esse trabalho ficava espalhado
 * em PerfilController e ManicureController.
 */
class UserObserver
{
    public function updated(User $user): void
    {
        if (!$user->wasChanged(['name', 'email', 'phone', 'avatar'])) {
            return;
        }

        if ($user->manicure) {
            $dados = $this->dadosBasicos($user);
            if ($user->wasChanged('avatar') && $user->avatar) {
                $dados['foto'] = $user->avatar;
            }
            $user->manicure->update($dados);
        }

        if ($user->cliente) {
            $user->cliente->update($this->dadosBasicos($user));
        }
    }

    private function dadosBasicos(User $user): array
    {
        return [
            'nome'     => $user->name,
            'email'    => $user->email,
            'telefone' => $user->phone,
        ];
    }
}
