<?php

namespace App\Http\Requests;

use App\Models\Produto;
use Illuminate\Foundation\Http\FormRequest;

class AplicarInventarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', Produto::class) ?? false;
    }

    public function rules(): array
    {
        $salaoId = (int) ($this->user()?->salao_id ?? 0);

        return [
            'contagens' => [
                'required',
                'array',
                'min:1',
                function (string $attribute, mixed $value, \Closure $fail) use ($salaoId) {
                    if (! is_array($value) || $value === []) {
                        $fail('Informe ao menos uma contagem.');

                        return;
                    }

                    $ids = array_map('intval', array_keys($value));
                    $validos = Produto::query()
                        ->where('salao_id', $salaoId)
                        ->whereIn('id', $ids)
                        ->pluck('id')
                        ->all();

                    if (count($validos) !== count(array_unique($ids))) {
                        $fail('Um ou mais produtos não pertencem ao seu salão.');
                    }
                },
            ],
            'contagens.*' => ['nullable', 'numeric', 'min:0', 'max:999999'],
        ];
    }

    public function messages(): array
    {
        return [
            'contagens.required' => 'Informe as contagens do inventário.',
        ];
    }
}
