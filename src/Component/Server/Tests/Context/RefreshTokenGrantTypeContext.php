<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2017 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\Component\Server\Tests\Context;

use Behat\Behat\Context\Context;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;

final class RefreshTokenGrantTypeContext implements Context
{
    /**
     * @var ResponseContext
     */
    private $responseContext;

    /**
     * @var ApplicationContext
     */
    private $applicationContext;

    /**
     * @BeforeScenario
     *
     * @param BeforeScenarioScope $scope
     */
    public function gatherContexts(BeforeScenarioScope $scope)
    {
        $environment = $scope->getEnvironment();

        $this->responseContext = $environment->getContext(ResponseContext::class);
        $this->applicationContext = $environment->getContext(ApplicationContext::class);
    }

    /**
     * @Given A client sends a Refresh Token Grant Type request without refresh_token parameter
     */
    public function aClientSendsARefreshTokenGrantTypeRequestWithoutRefreshTokenParameter()
    {
        $request = $this->applicationContext->getServerRequestFactory()->createServerRequest([]);
        $request = $request->withMethod('POST');
        $request = $request->withParsedBody([
            'grant_type' => 'refresh_token',
        ]);
        $request = $request->withHeader('Authorization', 'Basic '.base64_encode('client1:secret'));
        $request = $request->withHeader('Content-Type', 'application/x-www-form-urlencoded');

        $this->responseContext->setResponse($this->applicationContext->getApplication()->getTokenEndpointPipe()->dispatch($request));
    }

    /**
     * @Given a client sends a Refresh Token Grant Type request with an expired refresh token
     */
    public function aClientSendsARefreshTokenGrantTypeRequestWithAnExpiredRefreshToken()
    {
        $request = $this->applicationContext->getServerRequestFactory()->createServerRequest([]);
        $request = $request->withMethod('POST');
        $request = $request->withParsedBody([
            'grant_type' => 'refresh_token',
            'refresh_token' => 'EXPIRED_REFRESH_TOKEN',
        ]);
        $request = $request->withHeader('Authorization', 'Basic '.base64_encode('client1:secret'));
        $request = $request->withHeader('Content-Type', 'application/x-www-form-urlencoded');

        $this->responseContext->setResponse($this->applicationContext->getApplication()->getTokenEndpointPipe()->dispatch($request));
    }

    /**
     * @Given a client sends a Refresh Token Grant Type request with a revoked refresh token
     */
    public function aClientSendsARefreshTokenGrantTypeRequestWithARevokedRefreshToken()
    {
        $request = $this->applicationContext->getServerRequestFactory()->createServerRequest([]);
        $request = $request->withMethod('POST');
        $request = $request->withParsedBody([
            'grant_type' => 'refresh_token',
            'refresh_token' => 'REVOKED_REFRESH_TOKEN',
        ]);
        $request = $request->withHeader('Authorization', 'Basic '.base64_encode('client1:secret'));
        $request = $request->withHeader('Content-Type', 'application/x-www-form-urlencoded');

        $this->responseContext->setResponse($this->applicationContext->getApplication()->getTokenEndpointPipe()->dispatch($request));
    }

    /**
     * @Given A client sends a valid Refresh Token Grant Type request
     */
    public function aClientSendsAValidRefreshTokenGrantTypeRequest()
    {
        $request = $this->applicationContext->getServerRequestFactory()->createServerRequest([]);
        $request = $request->withMethod('POST');
        $request = $request->withParsedBody([
            'grant_type' => 'refresh_token',
            'refresh_token' => 'VALID_REFRESH_TOKEN',
        ]);
        $request = $request->withHeader('Authorization', 'Basic '.base64_encode('client1:secret'));
        $request = $request->withHeader('Content-Type', 'application/x-www-form-urlencoded');

        $this->responseContext->setResponse($this->applicationContext->getApplication()->getTokenEndpointPipe()->dispatch($request));
    }

    /**
     * @Given A client sends a valid Refresh Token Grant Type request but the grant type is not allowed
     */
    public function aClientSendsAValidRefreshTokenGrantTypeRequestButTheGrantTypeIsNotAllowed()
    {
        $request = $this->applicationContext->getServerRequestFactory()->createServerRequest([]);
        $request = $request->withMethod('POST');
        $request = $request->withParsedBody([
            'grant_type' => 'refresh_token',
            'refresh_token' => 'VALID_REFRESH_TOKEN',
            'client_id' => 'client2',
        ]);
        $request = $request->withHeader('Content-Type', 'application/x-www-form-urlencoded');

        $this->responseContext->setResponse($this->applicationContext->getApplication()->getTokenEndpointPipe()->dispatch($request));
    }
}
