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

final class CodeResponseTypeContext implements Context
{
    /**
     * @var ResponseContext
     */
    private $responseContext;

    /**
     * @var ResponseTypeContext
     */
    private $responseTypeContext;

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
        $this->responseTypeContext = $environment->getContext(ResponseTypeContext::class);
    }

    /**
     * @Given A client sends an authorization requests with the Authorization Code Response Type and a Code Challenge, but the method is not supported
     */
    public function aClientSendsAnAuthorizationRequestsWithTheAuthorizationCodeResponseTypeAndACodeChallengeButTheMethodIsNotSupported()
    {
        $request = $this->applicationContext->getServerRequestFactory()->createServerRequestFromArray([]);
        $request = $request->withMethod('GET');
        $request = $request->withQueryParams([
            'client_id' => 'client1',
            'redirect_uri' => 'https://example.com/redirection/callback',
            'response_type' => 'code',
            'state' => '0123456789',
            'code_challenge' => 'E9Melhoa2OwvFrEMTJguCHaoeK1t8URWbuGJSstw-cM',
            'code_challenge_method' => 'foo',
        ]);

        $this->responseTypeContext->setAuthorizationRequest($request);
        $this->applicationContext->getApplication()->getAuthorizationEndpointPipe()->dispatch($request);
    }

    /**
     * @Given A client sends an authorization requests with the Authorization Code Response Type
     */
    public function aClientSendsAnAuthorizationRequestsWithTheAuthorizationCodeResponseType()
    {
        $request = $this->applicationContext->getServerRequestFactory()->createServerRequestFromArray([]);
        $request = $request->withMethod('GET');
        $request = $request->withQueryParams([
            'client_id' => 'client1',
            'redirect_uri' => 'https://example.com/redirection/callback',
            'response_type' => 'code',
            'state' => '0123456789',
        ]);

        $this->responseTypeContext->setAuthorizationRequest($request);
        $this->applicationContext->getApplication()->getAuthorizationEndpointPipe()->dispatch($request);
    }

    /**
     * @Given A client sends an authorization requests with the Authorization Code Response Type and a code verifier
     */
    public function aClientSendsAnAuthorizationRequestsWithTheAuthorizationCodeResponseTypeAndACodeVerifier()
    {
        $request = $this->applicationContext->getServerRequestFactory()->createServerRequestFromArray([]);
        $request = $request->withMethod('GET');
        $request = $request->withQueryParams([
            'client_id' => 'client1',
            'redirect_uri' => 'https://example.com/redirection/callback',
            'response_type' => 'code',
            'code_challenge' => 'E9Melhoa2OwvFrEMTJguCHaoeK1t8URWbuGJSstw-cM',
            'code_challenge_method' => 'S256',
            'state' => '0123456789',
        ]);

        $this->responseTypeContext->setAuthorizationRequest($request);
        $this->applicationContext->getApplication()->getAuthorizationEndpointPipe()->dispatch($request);
    }

    /**
     * @Then an authorization code creation event is thrown
     */
    public function anAuthorizationCodeCreationEventIsThrown()
    {
        $events = $this->applicationContext->getApplication()->getAuthCodeCreatedEventHandler()->getEvents();
        Assertion::greaterThan(count($events), 0);
    }

    /**
     * @Then no authorization code creation event is thrown
     */
    public function noAuthorizationCodeCreationEventIsThrown()
    {
        $events = $this->applicationContext->getApplication()->getAuthCodeCreatedEventHandler()->getEvents();
        Assertion::eq(0, count($events));
    }

    /**
     * @Given A client sends an authorization requests with the Authorization Code Response Type, a code verifier and the scope "openid"
     */
    public function aClientSendsAnAuthorizationRequestsWithTheAuthorizationCodeResponseTypeACodeVerifierAndTheScopeOpenId()
    {
        $request = $this->applicationContext->getServerRequestFactory()->createServerRequestFromArray([]);
        $request = $request->withMethod('GET');
        $request = $request->withQueryParams([
            'client_id' => 'client1',
            'redirect_uri' => 'https://example.com/redirection/callback',
            'response_type' => 'code',
            'code_challenge' => 'E9Melhoa2OwvFrEMTJguCHaoeK1t8URWbuGJSstw-cM',
            'code_challenge_method' => 'plain',
            'state' => '0123456789',
            'scope' => 'openid offline_access',
            'prompt' => 'consent',
        ]);

        $this->responseTypeContext->setAuthorizationRequest($request);
        $this->applicationContext->getApplication()->getAuthorizationEndpointPipe()->dispatch($request);
    }

    /**
     * @When the client exchanges the authorization for an access token
     */
    public function theClientExchangesTheAuthorizationForAnAccessToken()
    {
        $code = $this->getAuthorizationCodeFromTheResponse();

        $request = $this->applicationContext->getServerRequestFactory()->createServerRequestFromArray([]);
        $request = $request->withMethod('POST');
        $request = $request->withParsedBody([
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => 'https://example.com/redirection/callback',
            'scope' => 'openid',
            'code_verifier' => 'E9Melhoa2OwvFrEMTJguCHaoeK1t8URWbuGJSstw-cM',
        ]);
        $request = $request->withHeader('Authorization', 'Basic '.base64_encode('client1:secret'));
        $request = $request->withHeader('Content-Type', 'application/x-www-form-urlencoded');

        $this->responseContext->setResponse($this->applicationContext->getApplication()->getTokenEndpointPipe()->dispatch($request));
    }

    /**
     * @return string
     */
    private function getAuthorizationCodeFromTheResponse(): string
    {
        $uriFactory = $this->applicationContext->getApplication()->getUriFactory();
        $locations = $this->responseContext->getResponse()->getHeader('Location');
        foreach ($locations as $location) {
            $uri = $uriFactory->createUri($location);
            $query = $uri->getQuery();
            parse_str($query, $data);
            if (array_key_exists('code', $data)) {
                return $data['code'];
            }
        }
        throw new \InvalidArgumentException(sprintf('The location header is "%s".', implode(', ', $locations)));
    }
}
