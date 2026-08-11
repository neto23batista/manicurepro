<?php

namespace App\Http\Requests;

use App\Models\Manicure;
use App\Models\Servico;
use App\Support\WhatsApp;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreGuestAgendamentoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'manicure_id'      => ['required', 'integer', 'exists:manicures,id'],
            'servico_ids'      => ['required', 'array', 'min:1'],
            'servico_ids.*'    => ['integer', 'exists:servicos,id'],
            'servico_variacoes'   => ['nullable', 'array'],
            'servico_variacoes.*' => ['nullable', 'integer', 'exists:servico_variacoes,id'],
            'data_hora_inicio' => ['required', 'date', 'after:now'],
            'nome'             => ['required', 'string', 'max:255'],
            'telefone'         => ['required', 'string', 'max:20'],
            'email'            => ['nullable', 'email', 'max:255'],
            'observacoes'      => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'manicure_id.required'      => 'Selecione a manicure.',
            'servico_ids.required'      => 'Selecione pelo menos um serviço.',
            'servico_ids.min'           => 'Selecione pelo menos um serviço.',
            'data_hora_inicio.required' => 'Informe a data e horário.',
            'data_hora_inicio.after'    => 'O horário deve ser futuro.',
            'nome.required'             => 'Informe o seu nome.',
            'telefone.required'         => 'Informe o telefone para contato.',
            'email.email'               => 'Informe um e-mail válido.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $telefone = (string) $this->input('telefone', '');
            $digits = preg_replace('/\D+/', '', $telefone) ?: '';

            if (WhatsApp::normalizarTelefone($telefone) === null
                || (strlen($digits) < 10 || strlen($digits) > 13)
            ) {
                $validator->errors()->add(
                    'telefone',
                    'Informe um telefone válido com DDD (10 ou 11 dígitos).',
                );
            }

            $salao = $this->route('salao');
            if (! $salao) {
                return;
            }

            $manicureId = (int) $this->input('manicure_id');
            if ($manicureId && ! Manicure::where('id', $manicureId)->where('salao_id', $salao->id)->exists()) {
                $validator->errors()->add('manicure_id', 'Manicure inválida para este salão.');
            }

            $servicoIds = array_map('intval', (array) $this->input('servico_ids', []));
            if ($servicoIds !== []) {
                $validos = Servico::where('salao_id', $salao->id)
                    ->where('disponivel_online', true)
                    ->whereIn('id', $servicoIds)
                    ->count();

                if ($validos !== count(array_unique($servicoIds))) {
                    $validator->errors()->add('servico_ids', 'Um ou mais serviços são inválidos.');
                }
            }
        });
    }

    public function telefoneFormatado(): string
    {
        $digits = preg_replace('/\D+/', '', (string) $this->input('telefone')) ?: '';

        // Remove DDI 55 se veio junto, para armazenar no formato local mascarado.
        if (str_starts_with($digits, '55') && strlen($digits) >= 12) {
            $digits = substr($digits, 2);
        }

        if (strlen($digits) === 11) {
            return sprintf('(%s) %s-%s', substr($digits, 0, 2), substr($digits, 2, 5), substr($digits, 7));
        }

        if (strlen($digits) === 10) {
            return sprintf('(%s) %s-%s', substr($digits, 0, 2), substr($digits, 2, 4), substr($digits, 6));
        }

        return (string) $this->input('telefone');
    }
}
