<?php

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->admin()->create(['ativo' => true]);
});

test('admin não pode desativar a própria conta quando há outro admin', function () {
    User::factory()->admin()->create(['ativo' => true]);

    $this->actingAs($this->admin)
        ->from(route('admin.usuarios.edit', $this->admin))
        ->delete(route('admin.usuarios.destroy', $this->admin))
        ->assertRedirect(route('admin.usuarios.edit', $this->admin))
        ->assertSessionHasErrors('error');

    expect($this->admin->fresh()->ativo)->toBeTrue();
});

test('não permite desativar o último admin ativo', function () {
    $outroAdmin = User::factory()->admin()->create(['ativo' => true]);

    $this->actingAs($this->admin)
        ->from(route('admin.usuarios.edit', $outroAdmin))
        ->delete(route('admin.usuarios.destroy', $outroAdmin))
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($outroAdmin->fresh()->ativo)->toBeFalse();

    $this->actingAs($this->admin)
        ->from(route('admin.usuarios.edit', $this->admin))
        ->delete(route('admin.usuarios.destroy', $this->admin))
        ->assertSessionHasErrors('error');

    expect($this->admin->fresh()->ativo)->toBeTrue()
        ->and(User::where('role', 'admin')->where('ativo', true)->count())->toBe(1);
});

test('destroy faz soft-deactivate e não apaga o registro', function () {
    $alvo = User::factory()->create(['role' => 'dono', 'ativo' => true]);

    $this->actingAs($this->admin)
        ->from(route('admin.usuarios.edit', $alvo))
        ->delete(route('admin.usuarios.destroy', $alvo))
        ->assertRedirect()
        ->assertSessionHas('success');

    expect(User::find($alvo->id))->not->toBeNull()
        ->and($alvo->fresh()->ativo)->toBeFalse();
});

test('não permite rebaixar ou desativar o último admin via update', function () {
    $this->actingAs($this->admin)
        ->from(route('admin.usuarios.edit', $this->admin))
        ->put(route('admin.usuarios.update', $this->admin), [
            'name'  => $this->admin->name,
            'email' => $this->admin->email,
            'role'  => 'dono',
            'ativo' => '1',
        ])
        ->assertRedirect()
        ->assertSessionHasErrors('error');

    expect($this->admin->fresh()->role)->toBe('admin');

    $this->actingAs($this->admin)
        ->from(route('admin.usuarios.edit', $this->admin))
        ->put(route('admin.usuarios.update', $this->admin), [
            'name'  => $this->admin->name,
            'email' => $this->admin->email,
            'role'  => 'admin',
            'ativo' => '0',
        ])
        ->assertRedirect()
        ->assertSessionHasErrors('error');

    expect($this->admin->fresh()->ativo)->toBeTrue();
});

test('mudança de role grava audit_logs quando a tabela existe', function () {
    expect(Schema::hasTable('audit_logs'))->toBeTrue();

    $alvo = User::factory()->create(['role' => 'dono', 'ativo' => true]);

    $this->actingAs($this->admin)
        ->put(route('admin.usuarios.update', $alvo), [
            'name'  => $alvo->name,
            'email' => $alvo->email,
            'role'  => 'atendente',
            'ativo' => '1',
        ])
        ->assertRedirect(route('admin.usuarios.index'));

    $log = AuditLog::query()->where('action', 'user.role_changed')->first();

    expect($log)->not->toBeNull()
        ->and($log->user_id)->toBe($this->admin->id)
        ->and($log->auditable_id)->toBe($alvo->id)
        ->and($log->meta['from'])->toBe('dono')
        ->and($log->meta['to'])->toBe('atendente');
});

test('update sem mudança de role não grava audit log', function () {
    $alvo = User::factory()->create(['role' => 'dono', 'ativo' => true]);

    $this->actingAs($this->admin)
        ->put(route('admin.usuarios.update', $alvo), [
            'name'  => 'Nome Atualizado',
            'email' => $alvo->email,
            'role'  => 'dono',
            'ativo' => '1',
        ])
        ->assertRedirect(route('admin.usuarios.index'));

    expect(AuditLog::count())->toBe(0);
});
