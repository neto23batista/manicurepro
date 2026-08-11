<?php

namespace App\Http\Requests;

use App\Models\Agendamento;
use App\Models\Salao;
use Illuminate\Foundation\Http\FormRequest;

class StoreAgendamentoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Agendamento::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        // Cliente/API: salão vem do servidor (single-tenant), não do formulário.
        if ($this->user()?->isCliente() && ! $this->filled('salao_id')) {
            $this->merge(['salao_id' => Salao::principalId()]);
        }

        if ($this->has('encaixe')) {
            $this->merge(['encaixe' => filter_var($this->input('encaixe'), FILTER_VALIDATE_BOOLEAN)]);
        }
    }

    public function rules(): array
    {
        $user = $this->user();
        $isStaff = $user && ($user->isDono() || $user->isAtendente() || $user->isSuperAdmin());

        return [
            'salao_id'         => [$isStaff ? 'sometimes' : 'required', 'exists:saloes,id'],
            'manicure_id'      => ['required', 'exists:manicures,id'],
            'servico_ids'      => ['required', 'array', 'min:1'],
            'servico_ids.*'    => ['integer', 'exists:servicos,id'],
            'servico_variacoes'   => ['nullable', 'array'],
            'servico_variacoes.*' => ['nullable', 'integer', 'exists:servico_variacoes,id'],
            'data_hora_inicio' => ['required', 'date', 'after:now'],
            'cliente_id'       => ['nullable', 'exists:clientes,id'],
            'nome_cliente'     => ['nullable', 'string', 'max:255'],
            'telefone_cliente' => ['nullable', 'string', 'max:20'],
            'observacoes'      => ['nullable', 'string', 'max:1000'],
            'recorrencia'      => ['nullable', 'in:nenhuma,semanal,quinzenal,mensal'],
            'ocorrencias'      => ['nullable', 'integer', 'min:1', 'max:12'],
            // Encaixe: apenas staff (dono/atendente). Público/cliente nunca passam.
            'encaixe'          => [$isStaff ? 'sometimes' : 'prohibited', 'boolean'],
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
            'salao_id.required'         => 'Salão não informado.',
        ];
    }
}
