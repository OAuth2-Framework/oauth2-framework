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

final class IssuerDiscoveryContext implements Context
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
     * @When a client send an Issuer Discovery request without rel parameter
     */
    public function aClientSendAnIssuerDiscoveryRequestWithoutRelParameter()
    {
        $request = $this->applicationContext->getServerRequestFactory()->createServerRequestFromArray([]);
        $request = $request->withMethod('GET');
        $request = $request->withQueryParams([]);
        $request = $request->withHeader('Content-Type', 'application/x-www-form-urlencoded');

        $this->responseContext->setResponse($this->applicationContext->getApplication()->getIssuerDiscoveryPipe()->dispatch($request));
    }

    /**
     * @When a client send an Issuer Discovery request with an invalid rel parameter
     */
    public function aClientSendAnIssuerDiscoveryRequestWithAnInvalidRelParameter()
    {
        $request = $this->applicationContext->getServerRequestFactory()->createServerRequestFromArray([]);
        $request = $request->withMethod('GET');
        $request = $request->withQueryParams([
            'rel' => 'foo.bar',
        ]);
        $request = $request->withHeader('Content-Type', 'application/x-www-form-urlencoded');

        $this->responseContext->setResponse($this->applicationContext->getApplication()->getIssuerDiscoveryPipe()->dispatch($request));
    }

    /**
     * @When a client send an Issuer Discovery request without resource parameter
     */
    public function aClientSendAnIssuerDiscoveryRequestWithoutResourceParameter()
    {
        $request = $this->applicationContext->getServerRequestFactory()->createServerRequestFromArray([]);
        $request = $request->withMethod('GET');
        $request = $request->withQueryParams([
            'rel' => 'http://openid.net/specs/connect/1.0/issuer',
        ]);
        $request = $request->withHeader('Content-Type', 'application/x-www-form-urlencoded');

        $this->responseContext->setResponse($this->applicationContext->getApplication()->getIssuerDiscoveryPipe()->dispatch($request));
    }

    /**
     * @When a client send an Issuer Discovery request with an invalid resource parameter based on an XRI
     */
    public function aClientSendAnIssuerDiscoveryRequestWithAnInvalidResourceParameterBasedOnAnXRI()
    {
        $request = $this->applicationContext->getServerRequestFactory()->createServerRequestFromArray([]);
        $request = $request->withMethod('GET');
        $request = $request->withQueryParams([
            'rel' => 'http://openid.net/specs/connect/1.0/issuer',
            'resource' => '@foo',
        ]);
        $request = $request->withHeader('Content-Type', 'application/x-www-form-urlencoded');

        $this->responseContext->setResponse($this->applicationContext->getApplication()->getIssuerDiscoveryPipe()->dispatch($request));
    }

    /**
     * @When a client send an Issuer Discovery request with an invalid resource parameter based on an email
     */
    public function aClientSendAnIssuerDiscoveryRequestWithAnInvalidResourceParameterBasedOnAnEmail()
    {
        $request = $this->applicationContext->getServerRequestFactory()->createServerRequestFromArray([]);
        $request = $request->withMethod('GET');
        $request = $request->withQueryParams([
            'rel' => 'http://openid.net/specs/connect/1.0/issuer',
            'resource' => 'acct:john@example.com',
        ]);
        $request = $request->withHeader('Content-Type', 'application/x-www-form-urlencoded');

        $this->responseContext->setResponse($this->applicationContext->getApplication()->getIssuerDiscoveryPipe()->dispatch($request));
    }

    /**
     * @When a client send an Issuer Discovery request with an invalid resource parameter based on an Url
     */
    public function aClientSendAnIssuerDiscoveryRequestWithAnInvalidResourceParameterBasedOnAnUrl()
    {
        $request = $this->applicationContext->getServerRequestFactory()->createServerRequestFromArray([]);
        $request = $request->withMethod('GET');
        $request = $request->withQueryParams([
            'rel' => 'http://openid.net/specs/connect/1.0/issuer',
            'resource' => 'https://example.com:8080/+john',
        ]);
        $request = $request->withHeader('Content-Type', 'application/x-www-form-urlencoded');

        $this->responseContext->setResponse($this->applicationContext->getApplication()->getIssuerDiscoveryPipe()->dispatch($request));
    }

    /**
     * @When a client send an Issuer Discovery request with a valid resource parameter based on an email
     */
    public function aClientSendAnIssuerDiscoveryRequestWithAValidResourceParameterBasedOnAnEmail()
    {
        $request = $this->applicationContext->getServerRequestFactory()->createServerRequestFromArray([]);
        $request = $request->withMethod('GET');
        $request = $request->withQueryParams([
            'rel' => 'http://openid.net/specs/connect/1.0/issuer',
            'resource' => 'acct:john@my-service.com:9000',
        ]);
        $request = $request->withHeader('Content-Type', 'application/x-www-form-urlencoded');

        $this->responseContext->setResponse($this->applicationContext->getApplication()->getIssuerDiscoveryPipe()->dispatch($request));
    }

    /**
     * @When a client send an Issuer Discovery request with a valid resource parameter based on an Url
     */
    public function aClientSendAnIssuerDiscoveryRequestWithAValidResourceParameterBasedOnAnUrl()
    {
        $request = $this->applicationContext->getServerRequestFactory()->createServerRequestFromArray([]);
        $request = $request->withMethod('GET');
        $request = $request->withQueryParams([
            'rel' => 'http://openid.net/specs/connect/1.0/issuer',
            'resource' => 'https://my-service.com:9000/+john',
        ]);
        $request = $request->withHeader('Content-Type', 'application/x-www-form-urlencoded');

        $this->responseContext->setResponse($this->applicationContext->getApplication()->getIssuerDiscoveryPipe()->dispatch($request));
    }

    public function testDomainForEmailResourceInTheRequest()
    {
        $request = $this->createRequest('/?resource=acct:john%40my-service.com:9000&rel=http%3A%2F%2Fopenid.net%2Fspecs%2Fconnect%2F1.0%2Fissuer', 'GET', [], ['HTTPS' => 'on']);

        $response = $this->getIssuerDiscoveryEndpoint()->process($request, $delegate);

        $response->getBody()->rewind();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(['application/jrd+json; charset=UTF-8'], $response->getHeader('Content-Type'));
        $this->assertEquals('{"subject":"acct:john@my-service.com:9000","links":[{"rel":"http:\/\/openid.net\/specs\/connect\/1.0\/issuer","href":"https:\/\/server.example.com"}]}', $response->getBody()->getContents());
    }

    public function testDomainForUriResourceInTheRequest()
    {
        $request = $this->createRequest('/?resource=https%3A%2F%2Fmy-service.com:9000%2F%2Bjohn&rel=http%3A%2F%2Fopenid.net%2Fspecs%2Fconnect%2F1.0%2Fissuer', 'GET', [], ['HTTPS' => 'on']);
        $response = $this->getIssuerDiscoveryEndpoint()->process($request, $delegate);

        $response->getBody()->rewind();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(['application/jrd+json; charset=UTF-8'], $response->getHeader('Content-Type'));
        $this->assertEquals('{"subject":"https:\/\/my-service.com:9000\/+john","links":[{"rel":"http:\/\/openid.net\/specs\/connect\/1.0\/issuer","href":"https:\/\/server.example.com"}]}', $response->getBody()->getContents());
    }
}
