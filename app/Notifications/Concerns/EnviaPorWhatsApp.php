<?php

namespace App\Notifications\Concerns;

use App\Notifications\Channels\WhatsAppChannel;

trait EnviaPorWhatsApp
{
    /**
     * Acrescenta o canal de WhatsApp aos canais informados,
     * caso a integração esteja habilitada.
     */
    protected function comWhatsApp(array $canais): array
    {
        if (config('manicure.whatsapp.enabled')) {
            $canais[] = WhatsAppChannel::class;
        }

        return $canais;
    }
}
