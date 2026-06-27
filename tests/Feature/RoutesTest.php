<?php

use Athwari\LaravelZktecoAdms\Tests\TestCase;
use Illuminate\Support\Facades\Route;

/** @var TestCase $this */
test('routes are registered', function () {
    expect(Route::has('zkteco-adms.cdata'))->toBeTrue()
        ->and(Route::has('zkteco-adms.registry'))->toBeTrue()
        ->and(Route::has('zkteco-adms.getrequest'))->toBeTrue()
        ->and(Route::has('zkteco-adms.devicecmd'))->toBeTrue()
        ->and(Route::has('zkteco-adms.inspect'))->toBeTrue()
        ->and(Route::has('zkteco-adms.test'))->toBeTrue();
});

test('test route endpoint works', function () {
    $this->call('GET', route('zkteco-adms.test'))
        ->assertStatus(200)
        ->assertSee('OK');

    $this->call('POST', route('zkteco-adms.test'))
        ->assertStatus(200)
        ->assertSee('OK');
});

test('routes have correct path structure', function () {
    expect(route('zkteco-adms.test'))->toContain('/iclock/test')
        ->and(route('zkteco-adms.cdata'))->toContain('/iclock/cdata')
        ->and(route('zkteco-adms.registry'))->toContain('/iclock/registry')
        ->and(route('zkteco-adms.getrequest'))->toContain('/iclock/getrequest')
        ->and(route('zkteco-adms.devicecmd'))->toContain('/iclock/devicecmd')
        ->and(route('zkteco-adms.inspect'))->toContain('/iclock/inspect');
});
