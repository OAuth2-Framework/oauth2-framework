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

use Base64Url\Base64Url;
use Behat\Behat\Context\Context;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use OAuth2Framework\Component\Server\Model\Client\ClientId;

final class ClientCredentialsGrantTypeContext implements Context
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
     * @Given An unauthenticated client sends a Client Credentials Grant Type request
     */
    public function anUnauthenticatedClientSendsAClientCredentialsGrantTypeRequest()
    {
        $request = $this->applicationContext->getServerRequestFactory()->createServerRequest([]);
        $request = $request->withMethod('POST');
        $request = $request->withParsedBody([
            'grant_type' => 'client_credentials',
        ]);
        $request = $request->withHeader('Content-Type', 'application/x-www-form-urlencoded');

        $this->responseContext->setResponse($this->applicationContext->getApplication()->getTokenEndpointPipe()->dispatch($request));
    }

    /**
     * @Given An public client sends a Client Credentials Grant Type request
     */
    public function anPublicClientSendsAClientCredentialsGrantTypeRequest()
    {
        $request = $this->applicationContext->getServerRequestFactory()->createServerRequest([]);
        $request = $request->withMethod('POST');
        $request = $request->withParsedBody([
            'grant_type' => 'client_credentials',
            'client_id' => 'client2',
        ]);
        $request = $request->withHeader('Content-Type', 'application/x-www-form-urlencoded');

        $this->responseContext->setResponse($this->applicationContext->getApplication()->getTokenEndpointPipe()->dispatch($request));
    }

    /**
     * @Given A client sends a Client Credentials Grant Type request but credentials expired
     */
    public function aClientSendsAClientCredentialsGrantTypeRequestButCredentialsExpired()
    {
        $request = $this->applicationContext->getServerRequestFactory()->createServerRequest([]);
        $request = $request->withMethod('POST');
        $request = $request->withParsedBody([
            'grant_type' => 'client_credentials',
            'scope' => 'email phone address',
        ]);
        $request = $request->withHeader('Authorization', 'Basic '.base64_encode('client5:secret'));
        $request = $request->withHeader('Content-Type', 'application/x-www-form-urlencoded');

        $this->responseContext->setResponse($this->applicationContext->getApplication()->getTokenEndpointPipe()->dispatch($request));
    }

    /**
     * @Given A client sends a valid Client Credentials Grant Type request
     */
    public function aClientSendsAValidClientCredentialsGrantTypeRequest()
    {
        $request = $this->applicationContext->getServerRequestFactory()->createServerRequest([]);
        $request = $request->withMethod('POST');
        $request = $request->withParsedBody([
            'grant_type' => 'client_credentials',
            'scope' => 'email phone address',
        ]);
        $request = $request->withHeader('Authorization', 'Basic '.base64_encode('client1:secret'));
        $request = $request->withHeader('Content-Type', 'application/x-www-form-urlencoded');

        $this->responseContext->setResponse($this->applicationContext->getApplication()->getTokenEndpointPipe()->dispatch($request));
    }

    /**
     * @Given A deleted client sends a Client Credentials Grant Type request
     */
    public function aDeletedClientSendsAClientCredentialsGrantTypeRequest()
    {
        $request = $this->applicationContext->getServerRequestFactory()->createServerRequest([]);
        $request = $request->withMethod('POST');
        $request = $request->withParsedBody([
            'grant_type' => 'client_credentials',
            'scope' => 'email phone address',
        ]);
        $request = $request->withHeader('Authorization', 'Basic '.base64_encode('DISABLED_CLIENT:secret'));
        $request = $request->withHeader('Content-Type', 'application/x-www-form-urlencoded');

        $this->responseContext->setResponse($this->applicationContext->getApplication()->getTokenEndpointPipe()->dispatch($request));
    }

    /**
     * @Given A client authenticated with a JWT assertion sends a valid Client Credentials Grant Type request
     */
    public function aClientAuthenticatedWithAJwtAssertionSendsAValidClientCredentialsGrantTypeRequest()
    {
        $request = $this->applicationContext->getServerRequestFactory()->createServerRequest([]);
        $request = $request->withMethod('POST');
        $request = $request->withParsedBody([
            'grant_type' => 'client_credentials',
            'client_assertion_type' => 'urn:ietf:params:oauth:client-assertion-type:jwt-bearer',
            'client_assertion' => $this->generateValidClientAssertion(),
            'scope' => 'email phone address',
        ]);
        $request = $request->withHeader('Content-Type', 'application/x-www-form-urlencoded');

        $this->responseContext->setResponse($this->applicationContext->getApplication()->getTokenEndpointPipe()->dispatch($request));
    }

    /**
     * @Given A client sends a valid Client Credentials Grant Type request but the grant type is not allowed
     */
    public function aClientSendsAValidClientCredentialsGrantTypeRequestButTheGrantTypeIsNotAllowed()
    {
        $request = $this->applicationContext->getServerRequestFactory()->createServerRequest([]);
        $request = $request->withMethod('POST');
        $request = $request->withParsedBody([
            'grant_type' => 'client_credentials',
            'client_id' => 'client4',
            'client_secret' => 'secret',
        ]);
        $request = $request->withHeader('Content-Type', 'application/x-www-form-urlencoded');

        $this->responseContext->setResponse($this->applicationContext->getApplication()->getTokenEndpointPipe()->dispatch($request));
    }

    private function generateValidClientAssertion()
    {
        $claims = [
            'iss' => 'client3',
            'sub' => 'client3',
            'aud' => 'My Server',
            'jti' => Base64Url::encode(random_bytes(64)),
            'exp' => (new \DateTimeImmutable('now + 1 day'))->getTimestamp(),
        ];
        $headers = [
            'alg' => 'HS256',
        ];
        $client = $this->applicationContext->getApplication()->getClientRepository()->find(ClientId::create('client3'));

        return $this->applicationContext->getApplication()->getJwTCreator()->sign($claims, $headers, $client->getPublicKeySet()->getKey(0));
    }
}
