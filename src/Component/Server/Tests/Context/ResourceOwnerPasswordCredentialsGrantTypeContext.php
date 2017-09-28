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

final class ResourceOwnerPasswordCredentialsGrantTypeContext implements Context
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
     * @Given A client sends a Resource Owner Password Credentials Grant Type request without username parameter
     */
    public function aClientSendsAResourceOwnerPasswordCredentialsGrantTypeRequestWithoutUsernameParameter()
    {
        $request = $this->applicationContext->getServerRequestFactory()->createServerRequestFromArray([]);
        $request = $request->withMethod('POST');
        $request = $request->withParsedBody([
            'grant_type' => 'password',
        ]);
        $request = $request->withHeader('Authorization', 'Basic '.base64_encode('client1:secret'));
        $request = $request->withHeader('Content-Type', 'application/x-www-form-urlencoded');

        $this->responseContext->setResponse($this->applicationContext->getApplication()->getTokenEndpointPipe()->dispatch($request));
    }

    /**
     * @Given A client sends a Resource Owner Password Credentials Grant Type request without password parameter
     */
    public function aClientSendsAResourceOwnerPasswordCredentialsGrantTypeRequestWithoutPasswordParameter()
    {
        $request = $this->applicationContext->getServerRequestFactory()->createServerRequestFromArray([]);
        $request = $request->withMethod('POST');
        $request = $request->withParsedBody([
            'grant_type' => 'password',
            'username' => 'john.1',
        ]);
        $request = $request->withHeader('Authorization', 'Basic '.base64_encode('client1:secret'));
        $request = $request->withHeader('Content-Type', 'application/x-www-form-urlencoded');

        $this->responseContext->setResponse($this->applicationContext->getApplication()->getTokenEndpointPipe()->dispatch($request));
    }

    /**
     * @Given A client sends a Resource Owner Password Credentials Grant Type request with invalid user credentials
     */
    public function aClientSendsAResourceOwnerPasswordCredentialsGrantTypeRequestWithInvalidUserCredentials()
    {
        $request = $this->applicationContext->getServerRequestFactory()->createServerRequestFromArray([]);
        $request = $request->withMethod('POST');
        $request = $request->withParsedBody([
            'grant_type' => 'password',
            'username' => 'john.1',
            'password' => 'BAD PASSWORD',
        ]);
        $request = $request->withHeader('Authorization', 'Basic '.base64_encode('client1:secret'));
        $request = $request->withHeader('Content-Type', 'application/x-www-form-urlencoded');

        $this->responseContext->setResponse($this->applicationContext->getApplication()->getTokenEndpointPipe()->dispatch($request));
    }

    /**
     * @Given A client sends a valid Resource Owner Password Credentials Grant Type request
     */
    public function aClientSendsAValidResourceOwnerPasswordCredentialsGrantTypeRequest()
    {
        $request = $this->applicationContext->getServerRequestFactory()->createServerRequestFromArray([]);
        $request = $request->withMethod('POST');
        $request = $request->withParsedBody([
            'grant_type' => 'password',
            'username' => 'john.1',
            'password' => 'doe',
            'scope' => 'email phone address',
        ]);
        $request = $request->withHeader('Authorization', 'Basic '.base64_encode('client1:secret'));
        $request = $request->withHeader('Content-Type', 'application/x-www-form-urlencoded');

        $this->responseContext->setResponse($this->applicationContext->getApplication()->getTokenEndpointPipe()->dispatch($request));
    }

    /**
     * @Given A client sends a valid Resource Owner Password Credentials Grant Type request but the grant type is not allowed
     */
    public function aClientSendsAValidResourceOwnerPasswordCredentialsGrantTypeRequestButTheGrantTypeIsNotAllowed()
    {
        $request = $this->applicationContext->getServerRequestFactory()->createServerRequestFromArray([]);
        $request = $request->withMethod('POST');
        $request = $request->withParsedBody([
            'grant_type' => 'password',
            'username' => 'john.1',
            'password' => 'doe',
            'client_id' => 'client2',
        ]);
        $request = $request->withHeader('Content-Type', 'application/x-www-form-urlencoded');

        $this->responseContext->setResponse($this->applicationContext->getApplication()->getTokenEndpointPipe()->dispatch($request));
    }
}
