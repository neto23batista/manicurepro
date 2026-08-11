<?php

use App\Http\Middleware\RoleMiddleware;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

uses(RefreshDatabase::class);

function passRole(User $user, string ...$roles): bool
{
    $request = Request::create('/test', 'GET');
    $request->setUserResolver(fn () => $user);

    try {
        (new RoleMiddleware())->handle($request, fn () => response('ok'), ...$roles);
        return true;
    } catch (HttpException $e) {
        if ($e->getStatusCode() === 403) {
            return false;
        }
        throw $e;
    }
}

test('admin herda dono e atendente, mas NÃO cliente nem manicure', function () {
    $admin = User::factory()->admin()->create();

    expect(passRole($admin, 'admin'))->toBeTrue();
    expect(passRole($admin, 'dono'))->toBeTrue();
    expect(passRole($admin, 'atendente'))->toBeTrue();
    expect(passRole($admin, 'cliente'))->toBeFalse();
    expect(passRole($admin, 'manicure'))->toBeFalse();
});

test('dono herda atendente, mas NÃO admin, cliente nem manicure', function () {
    $dono = User::factory()->dono()->create();

    expect(passRole($dono, 'dono'))->toBeTrue();
    expect(passRole($dono, 'atendente'))->toBeTrue();
    expect(passRole($dono, 'admin'))->toBeFalse();
    expect(passRole($dono, 'cliente'))->toBeFalse();
    expect(passRole($dono, 'manicure'))->toBeFalse();
});

test('atendente, manicure e cliente são correspondência exata', function () {
    $atendente = User::factory()->create(['role' => 'atendente']);
    $manicure = User::factory()->manicure()->create();
    $cliente = User::factory()->cliente()->create();

    expect(passRole($atendente, 'atendente'))->toBeTrue();
    expect(passRole($atendente, 'dono'))->toBeFalse();
    expect(passRole($atendente, 'cliente'))->toBeFalse();

    expect(passRole($manicure, 'manicure'))->toBeTrue();
    expect(passRole($manicure, 'cliente'))->toBeFalse();
    expect(passRole($manicure, 'dono'))->toBeFalse();

    expect(passRole($cliente, 'cliente'))->toBeTrue();
    expect(passRole($cliente, 'manicure'))->toBeFalse();
    expect(passRole($cliente, 'dono'))->toBeFalse();
});

test('role composto: admin passa em role:dono,atendente', function () {
    $admin = User::factory()->admin()->create();
    expect(passRole($admin, 'dono', 'atendente'))->toBeTrue();
});

test('cliente não passa em role:dono,atendente', function () {
    $cliente = User::factory()->cliente()->create();
    expect(passRole($cliente, 'dono', 'atendente'))->toBeFalse();
});
