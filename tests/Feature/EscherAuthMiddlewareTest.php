<?php

namespace Middleware\Auth\Escher\Tests\Feature;

use Escher\Escher;
use Illuminate\Support\Facades\Event;
use Middleware\Auth\Escher\Events\EshcerAuthFailure;

class EscherAuthMiddlewareTest extends BaseTestCase
{
    /**
     * @test
     */
    public function unprotectedEndpointReturnSuccessfulResponseWhenRequestDoesNotHaveEscherSignature(): void
    {
        $response = $this->get('api/unprotected');

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function protectedEndpointReturnWithStatus401WhenRequestDoesNotHaveEscherSignature(): void
    {
        $response = $this->get('api/protected');

        $this->assertEquals(401, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function protectedEndpointReturnWithStatus401WhenRequestHasInValidEscherSignature(): void
    {
        $escher = app()->get(Escher::class);
        $headers = $escher->signRequest('invalid_key', 'invalid_secret', 'get', route('api.protected'), '');
        $response = $this->get('api/protected', $headers);

        $this->assertEquals(401, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function protectedEndpointDispatchEscherAuthFailureEventWhenRequestHasInValidEscherSignature(): void
    {
        Event::fake();

        $escher = app()->get(Escher::class);
        $headers = $escher->signRequest('invalid_key', 'invalid_secret', 'get', route('api.protected'), '');
        $this->get('api/protected', $headers);

        Event::assertDispatched(EshcerAuthFailure::class);
    }

    /**
     * @test
     */
    public function protectedEndpointDispatchEscherAuthFailureEventWithActualRequestWhenRequestHasInValidEscherSignature(): void
    {
        Event::fake();

        $escher = app()->get(Escher::class);
        $headers = $escher->signRequest('invalid_key', 'invalid_secret', 'get', route('api.protected'), '');
        $this->get('api/protected', $headers);

        Event::assertDispatched(EshcerAuthFailure::class, function(EshcerAuthFailure $event) {
            return $event->getRequest() === request();
        });
    }

    /**
     * @test
     */
    public function protectedEndpointReturnSuccessfulResponseWhenRequestHasValidEscherSignature(): void
    {
        $escher = app()->get(Escher::class);
        $headers = $escher->signRequest('testKey', 'testSecret', 'get', route('api.protected'), '');
        $response = $this->get('api/protected', $headers);

        $this->assertEquals(200, $response->getStatusCode());
    }
}
