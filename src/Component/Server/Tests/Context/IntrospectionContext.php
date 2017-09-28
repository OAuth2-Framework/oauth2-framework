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

final class IntrospectionContext implements Context
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
     * @Given An unauthenticated protected resource tries to get information about a token
     */
    public function anUnauthenticatedProtectedResourceTriesToGetInformationAboutAToken()
    {
        $request = $this->applicationContext->getServerRequestFactory()->createServerRequestFromArray(['REMOTE_ADDR' => '127.0.0.1']);
        $request = $request->withMethod('POST');
        $request = $request->withParsedBody([]);
        $request = $request->withHeader('Content-Type', 'application/x-www-form-urlencoded');

        $this->responseContext->setResponse($this->applicationContext->getApplication()->getTokenIntrospectionPipe()->dispatch($request));
    }

    /**
     * @Given A protected resource sends an invalid introspection request
     */
    public function aProtectedResourceSendsAnInvalidIntrospectionRequest()
    {
        $request = $this->applicationContext->getServerRequestFactory()->createServerRequestFromArray(['REMOTE_ADDR' => '127.0.0.1']);
        $request = $request->withMethod('POST');
        $request = $request->withParsedBody([]);
        $request = $request->withHeader('Content-Type', 'application/x-www-form-urlencoded');
        $request = $request->withHeader('X-Resource-Server-Id', 'ResourceServer1');

        $this->responseContext->setResponse($this->applicationContext->getApplication()->getTokenIntrospectionPipe()->dispatch($request));
    }

    /**
     * @Given A protected resource tries to get information of a token that owns another protected resource
     */
    public function aProtectedResourceTriesToGetInformationOfATokenThatOwnsAnotherProtectedResource()
    {
        $request = $this->applicationContext->getServerRequestFactory()->createServerRequestFromArray(['REMOTE_ADDR' => '127.0.0.1']);
        $request = $request->withMethod('POST');
        $request = $request->withParsedBody([
            'token' => 'ACCESS_TOKEN_#1',
        ]);
        $request = $request->withHeader('Content-Type', 'application/x-www-form-urlencoded');
        $request = $request->withHeader('X-Resource-Server-Id', 'ResourceServer2');

        $this->responseContext->setResponse($this->applicationContext->getApplication()->getTokenIntrospectionPipe()->dispatch($request));
    }

    /**
     * @Given A protected resource tries to get information of a token
     */
    public function aProtectedResourceTriesToGetInformationOfAToken()
    {
        $request = $this->applicationContext->getServerRequestFactory()->createServerRequestFromArray(['REMOTE_ADDR' => '127.0.0.1']);
        $request = $request->withMethod('POST');
        $request = $request->withParsedBody([
            'token' => 'ACCESS_TOKEN_#1',
        ]);
        $request = $request->withHeader('Content-Type', 'application/x-www-form-urlencoded');
        $request = $request->withHeader('X-Resource-Server-Id', 'ResourceServer1');

        $this->responseContext->setResponse($this->applicationContext->getApplication()->getTokenIntrospectionPipe()->dispatch($request));
    }

    /**
     * @Given A protected resource tries to get information of a revoked token
     */
    public function aProtectedResourceTriesToGetInformationOfARevokedToken()
    {
        $request = $this->applicationContext->getServerRequestFactory()->createServerRequestFromArray(['REMOTE_ADDR' => '127.0.0.1']);
        $request = $request->withMethod('POST');
        $request = $request->withParsedBody([
            'token' => 'REVOKED_ACCESS_TOKEN',
        ]);
        $request = $request->withHeader('Content-Type', 'application/x-www-form-urlencoded');
        $request = $request->withHeader('X-Resource-Server-Id', 'ResourceServer1');

        $this->responseContext->setResponse($this->applicationContext->getApplication()->getTokenIntrospectionPipe()->dispatch($request));
    }
}
