<?php

use Athwari\LaravelZktecoAdms\Http\Middleware\ValidateDeviceRequest;
use Illuminate\Http\Request;

test('validate device request rejects oversized payloads', function () {
    config()->set('zkteco-adms.max_body_size', 10);

    $request = Request::create('/iclock/cdata', 'POST');
    $request->headers->set('Content-Length', '11');

    $response = (new ValidateDeviceRequest())->handle($request, fn () => response('OK', 200));

    expect($response->getStatusCode())->toBe(413)
        ->and($response->getContent())->toBe('Request body too large');
});

test('validate device request allows acceptable payloads', function () {
    config()->set('zkteco-adms.max_body_size', 10);

    $request = Request::create('/iclock/cdata', 'POST');
    $request->headers->set('Content-Length', '10');

    $response = (new ValidateDeviceRequest())->handle($request, fn () => response('OK', 200));

    expect($response->getStatusCode())->toBe(200)
        ->and($response->getContent())->toBe('OK');
});
