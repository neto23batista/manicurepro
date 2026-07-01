<?php

namespace App\Notifications\Messages;

/**
 * Mensagem de WhatsApp (Meta Cloud API).
 *
 * Suporta dois modos:
 *  - texto livre  → ->text('...')        (válido só dentro da janela de 24h)
 *  - template     → ->template($nome, $params)  (necessário para mensagens
 *    iniciadas pelo negócio, ex. lembretes — template precisa estar aprovado)
 */
class WhatsAppMessage
{
    public ?string $body = null;
    public ?string $templateName = null;
    public string $templateLanguage = 'pt_BR';
    public array $templateParams = [];

    public static function create(string $body = ''): static
    {
        return (new static)->text($body);
    }

    public function text(string $body): static
    {
        $this->body = $body;
        $this->templateName = null;

        return $this;
    }

    public function template(string $name, array $params = [], string $language = 'pt_BR'): static
    {
        $this->templateName = $name;
        $this->templateParams = $params;
        $this->templateLanguage = $language;

        return $this;
    }

    public function isTemplate(): bool
    {
        return $this->templateName !== null;
    }

    /**
     * Monta o payload da Cloud API para o destinatário informado.
     */
    public function toPayload(string $to): array
    {
        if ($this->isTemplate()) {
            return [
                'messaging_product' => 'whatsapp',
                'to'                => $to,
                'type'              => 'template',
                'template' => [
                    'name'     => $this->templateName,
                    'language' => ['code' => $this->templateLanguage],
                    'components' => $this->templateParams ? [[
                        'type' => 'body',
                        'parameters' => array_map(
                            fn ($p) => ['type' => 'text', 'text' => (string) $p],
                            $this->templateParams
                        ),
                    ]] : [],
                ],
            ];
        }

        return [
            'messaging_product' => 'whatsapp',
            'to'                => $to,
            'type'              => 'text',
            'text'              => ['body' => $this->body ?? ''],
        ];
    }
}
