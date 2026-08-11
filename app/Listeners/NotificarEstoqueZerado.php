<?php

namespace App\Listeners;

use App\Enums\UserRole;
use App\Events\EstoqueZerado;
use App\Models\User;
use App\Notifications\EstoqueZerado as EstoqueZeradoNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotificarEstoqueZerado implements ShouldQueue
{
    public function handle(EstoqueZerado $event): void
    {
        if (! config('manicure.estoque.notificar_zerado', true)) {
            return;
        }

        $produto = $event->produto;
        $notification = new EstoqueZeradoNotification($produto);

        User::query()
            ->where('salao_id', $produto->salao_id)
            ->where('role', UserRole::Dono->value)
            ->where('ativo', true)
            ->whereNotNull('email')
            ->each(fn (User $user) => $user->notify($notification));
    }
}
