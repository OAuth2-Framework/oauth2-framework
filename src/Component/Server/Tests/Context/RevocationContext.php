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

use Assert\Assertion;
use Behat\Behat\Context\Context;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;

final class RevocationContext implements Context
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
     * @Given a client sends a POST revocation request but it is not authenticated
     */
    public function aClientSendsAPostRevocationRequestButItIsNotAuthenticated()
    {
        $request = $this->applicationContext->getServerRequestFactory()->createServerRequestFromArray([]);
        $request = $request->withMethod('POST');
        $request = $request->withParsedBody([]);
        $request = $request->withHeader('Content-Type', 'application/x-www-form-urlencoded');

        $this->responseContext->setResponse($this->applicationContext->getApplication()->getTokenRevocationPipe()->dispatch($request));
    }

    /**
     * @Given a client sends a GET revocation request but it is not authenticated
     */
    public function aClientSendsAGetRevocationRequestButItIsNotAuthenticated()
    {
        $request = $this->applicationContext->getServerRequestFactory()->createServerRequestFromArray([]);
        $request = $request->withMethod('GET');
        $request = $request->withQueryParams([]);

        $this->responseContext->setResponse($this->applicationContext->getApplication()->getTokenRevocationPipe()->dispatch($request));
    }

    /**
     * @Given a client sends a POST revocation request without token parameter
     */
    public function aClientSendsAPostRevocationRequestWithoutTokenParameter()
    {
        $request = $this->applicationContext->getServerRequestFactory()->createServerRequestFromArray([]);
        $request = $request->withMethod('POST');
        $request = $request->withParsedBody([
        ]);
        $request = $request->withHeader('Authorization', 'Basic '.base64_encode('client1:secret'));
        $request = $request->withHeader('Content-Type', 'application/x-www-form-urlencoded');

        $this->responseContext->setResponse($this->applicationContext->getApplication()->getTokenRevocationPipe()->dispatch($request));
    }

    /**
     * @Given a client sends a GET revocation request without token parameter
     */
    public function aClientSendsAGetRevocationRequestWithoutTokenParameter()
    {
        $request = $this->applicationContext->getServerRequestFactory()->createServerRequestFromArray([]);
        $request = $request->withMethod('GET');
        $request = $request->withQueryParams([
        ]);
        $request = $request->withHeader('Authorization', 'Basic '.base64_encode('client1:secret'));

        $this->responseContext->setResponse($this->applicationContext->getApplication()->getTokenRevocationPipe()->dispatch($request));
    }

    /**
     * @Given a client sends a POST revocation request without token parameter with a callback parameter
     */
    public function aClientSendsAPostRevocationRequestWithoutTokenParameterWithACallbackParameter()
    {
        $request = $this->applicationContext->getServerRequestFactory()->createServerRequestFromArray([]);
        $request = $request->withMethod('POST');
        $request = $request->withParsedBody([
            'callback' => 'foo',
        ]);
        $request = $request->withHeader('Authorization', 'Basic '.base64_encode('client1:secret'));
        $request = $request->withHeader('Content-Type', 'application/x-www-form-urlencoded');

        $this->responseContext->setResponse($this->applicationContext->getApplication()->getTokenRevocationPipe()->dispatch($request));
    }

    /**
     * @Given a client sends a GET revocation request without token parameter with a callback parameter
     */
    public function aClientSendsAGetRevocationRequestWithoutTokenParameterWithACallbackParameter()
    {
        $request = $this->applicationContext->getServerRequestFactory()->createServerRequestFromArray([]);
        $request = $request->withMethod('GET');
        $request = $request->withQueryParams([
            'callback' => 'foo',
        ]);
        $request = $request->withHeader('Authorization', 'Basic '.base64_encode('client1:secret'));

        $this->responseContext->setResponse($this->applicationContext->getApplication()->getTokenRevocationPipe()->dispatch($request));
    }

    /**
     * @Given a client sends a valid POST revocation request
     */
    public function aClientSendsAValidPostRevocationRequest()
    {
        $request = $this->applicationContext->getServerRequestFactory()->createServerRequestFromArray([]);
        $request = $request->withMethod('POST');
        $request = $request->withParsedBody([
            'token' => 'ACCESS_TOKEN_#1',
        ]);
        $request = $request->withHeader('Authorization', 'Basic '.base64_encode('client1:secret'));
        $request = $request->withHeader('Content-Type', 'application/x-www-form-urlencoded');

        $this->responseContext->setResponse($this->applicationContext->getApplication()->getTokenRevocationPipe()->dispatch($request));
    }

    /**
     * @Given a client sends a valid GET revocation request
     */
    public function aClientSendsAValidGetRevocationRequest()
    {
        $request = $this->applicationContext->getServerRequestFactory()->createServerRequestFromArray([]);
        $request = $request->withMethod('GET');
        $request = $request->withQueryParams([
            'token' => 'ACCESS_TOKEN_#1',
        ]);
        $request = $request->withHeader('Authorization', 'Basic '.base64_encode('client1:secret'));

        $this->responseContext->setResponse($this->applicationContext->getApplication()->getTokenRevocationPipe()->dispatch($request));
    }

    /**
     * @Given a client sends a valid POST revocation request but the token owns to another client
     */
    public function aClientSendsAValidPostRevocationRequestButTheTokenOwnsToAnotherClient()
    {
        $request = $this->applicationContext->getServerRequestFactory()->createServerRequestFromArray([]);
        $request = $request->withMethod('POST');
        $request = $request->withParsedBody([
            'token' => 'ACCESS_TOKEN_#2',
        ]);
        $request = $request->withHeader('Authorization', 'Basic '.base64_encode('client1:secret'));
        $request = $request->withHeader('Content-Type', 'application/x-www-form-urlencoded');

        $this->responseContext->setResponse($this->applicationContext->getApplication()->getTokenRevocationPipe()->dispatch($request));
    }

    /**
     * @Given a client sends a valid GET revocation request but the token owns to another client
     */
    public function aClientSendsAValidGetRevocationRequestButTheTokenOwnsToAnotherClient()
    {
        $request = $this->applicationContext->getServerRequestFactory()->createServerRequestFromArray([]);
        $request = $request->withMethod('GET');
        $request = $request->withQueryParams([
            'token' => 'ACCESS_TOKEN_#2',
        ]);
        $request = $request->withHeader('Authorization', 'Basic '.base64_encode('client1:secret'));

        $this->responseContext->setResponse($this->applicationContext->getApplication()->getTokenRevocationPipe()->dispatch($request));
    }

    /**
     * @Given a client sends a POST revocation request but the token type hint is not supported
     */
    public function aClientSendsAPostRevocationRequestButTheTokenTypeHintIsNotSupported()
    {
        $request = $this->applicationContext->getServerRequestFactory()->createServerRequestFromArray([]);
        $request = $request->withMethod('POST');
        $request = $request->withParsedBody([
            'token' => 'ACCESS_TOKEN_#2',
            'token_type_hint' => 'bad_hint',
        ]);
        $request = $request->withHeader('Authorization', 'Basic '.base64_encode('client1:secret'));
        $request = $request->withHeader('Content-Type', 'application/x-www-form-urlencoded');

        $this->responseContext->setResponse($this->applicationContext->getApplication()->getTokenRevocationPipe()->dispatch($request));
    }

    /**
     * @Given a client sends a GET revocation request but the token type hint is not supported
     */
    public function aClientSendsAGetRevocationRequestButTheTokenTypeHintIsNotSupported()
    {
        $request = $this->applicationContext->getServerRequestFactory()->createServerRequestFromArray([]);
        $request = $request->withMethod('GET');
        $request = $request->withQueryParams([
            'token' => 'ACCESS_TOKEN_#2',
            'token_type_hint' => 'bad_hint',
        ]);
        $request = $request->withHeader('Authorization', 'Basic '.base64_encode('client1:secret'));

        $this->responseContext->setResponse($this->applicationContext->getApplication()->getTokenRevocationPipe()->dispatch($request));
    }

    /**
     * @Given a client sends a POST revocation request but the token does not exist or expired
     */
    public function aClientSendsAPostRevocationRequestButTheTokenDoesNotExistOrExpired()
    {
        $request = $this->applicationContext->getServerRequestFactory()->createServerRequestFromArray([]);
        $request = $request->withMethod('POST');
        $request = $request->withParsedBody([
            'token' => 'UNKNOWN_REFRESH_TOKEN_#2',
            'token_type_hint' => 'refresh_token',
        ]);
        $request = $request->withHeader('Content-Type', 'application/x-www-form-urlencoded');
        $request = $request->withHeader('Authorization', 'Basic '.base64_encode('client1:secret'));

        $this->responseContext->setResponse($this->applicationContext->getApplication()->getTokenRevocationPipe()->dispatch($request));
    }

    /**
     * @Given a client sends a GET revocation request but the token does not exist or expired
     */
    public function aClientSendsAGetRevocationRequestButTheTokenDoesNotExistOrExpired()
    {
        $request = $this->applicationContext->getServerRequestFactory()->createServerRequestFromArray([]);
        $request = $request->withMethod('GET');
        $request = $request->withQueryParams([
            'token' => 'UNKNOWN_REFRESH_TOKEN_#2',
            'token_type_hint' => 'refresh_token',
        ]);
        $request = $request->withHeader('Authorization', 'Basic '.base64_encode('client1:secret'));

        $this->responseContext->setResponse($this->applicationContext->getApplication()->getTokenRevocationPipe()->dispatch($request));
    }

    /**
     * @Given a client sends a GET revocation request with callback but the token does not exist or expired
     */
    public function aClientSendsAGetRevocationRequestWithCallbackButTheTokenDoesNotExistOrExpired()
    {
        $request = $this->applicationContext->getServerRequestFactory()->createServerRequestFromArray([]);
        $request = $request->withMethod('GET');
        $request = $request->withQueryParams([
            'token' => 'UNKNOWN_REFRESH_TOKEN_#2',
            'token_type_hint' => 'refresh_token',
            'callback' => 'callback',
        ]);
        $request = $request->withHeader('Authorization', 'Basic '.base64_encode('client1:secret'));

        $this->responseContext->setResponse($this->applicationContext->getApplication()->getTokenRevocationPipe()->dispatch($request));
    }

    /**
     * @Then no token revocation event is thrown
     */
    public function noTokenRevocationEventIsThrown()
    {
        $events = $this->applicationContext->getApplication()->getAccessTokenRevokedEventHandler()->getEvents();
        Assertion::eq(0, count($events));
        $events = $this->applicationContext->getApplication()->getRefreshTokenRevokedEventHandler()->getEvents();
        Assertion::eq(0, count($events));
    }

    /**
     * @Then a token revocation event is thrown
     */
    public function aTokenRevocationEventIsThrown()
    {
        $events1 = $this->applicationContext->getApplication()->getAccessTokenRevokedEventHandler()->getEvents();
        $events2 = $this->applicationContext->getApplication()->getRefreshTokenRevokedEventHandler()->getEvents();
        Assertion::greaterThan(count($events1) + count($events2), 0);
    }
}
