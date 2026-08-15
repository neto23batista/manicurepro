<?php

use App\Models\Company;
use App\Models\Salao;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('company pode ter vários salões', function () {
    $company = Company::factory()->create(['nome' => 'Grupo Unhas SP']);

    $a = Salao::factory()->create(['company_id' => $company->id, 'ativo' => true]);
    $b = Salao::factory()->create(['company_id' => $company->id, 'ativo' => true]);

    expect($company->saloes)->toHaveCount(2);
    expect($a->fresh()->company->id)->toBe($company->id);
    expect($b->company_id)->toBe($company->id);
});

test('principal ainda retorna o primeiro salão ativo sem company', function () {
    Salao::factory()->create(['ativo' => false, 'nome' => 'Inativo']);
    $ativo = Salao::factory()->create(['ativo' => true, 'nome' => 'Ativo Principal']);
    Salao::factory()->create(['ativo' => true, 'nome' => 'Outro Ativo']);

    $principal = Salao::principal();

    expect($principal)->not->toBeNull();
    expect($principal->id)->toBe($ativo->id);
    expect($principal->company_id)->toBeNull();
});

test('salão com company não quebra principal', function () {
    $company = Company::factory()->create();
    $salao = Salao::factory()->create([
        'company_id' => $company->id,
        'ativo'      => true,
    ]);

    expect(Salao::principal()?->id)->toBe($salao->id);
    expect($salao->company->nome)->toBe($company->nome);
});
