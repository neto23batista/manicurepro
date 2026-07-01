<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAgendamentoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'salao_id'         => ['sometimes', 'exists:saloes,id'],
            'manicure_id'      => ['required', 'exists:manicures,id'],
            'servico_ids'      => ['required', 'array', 'min:1'],
            'servico_ids.*'    => ['integer', 'exists:servicos,id'],
            'data_hora_inicio' => ['required', 'date', 'after:now'],
            'cliente_id'       => ['nullable', 'exists:clientes,id'],
            'nome_cliente'     => ['nullable', 'string', 'max:255'],
            'telefone_cliente' => ['nullable', 'string', 'max:20'],
            'observacoes'      => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'manicure_id.required'      => 'Selecione a manicure.',
            'manicure_id.exists'        => 'Manicure inválida.',
            'servico_ids.required'      => 'Selecione pelo menos um serviço.',
            'servico_ids.min'           => 'Selecione pelo menos um serviço.',
            'data_hora_inicio.required' => 'Informe a data e horário.',
            'data_hora_inicio.after'    => 'O horário deve ser futuro.',
        ];
    }
}
