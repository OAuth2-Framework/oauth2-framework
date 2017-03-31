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

final class AuthorizationCodeGrantTypeContext implements Context
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
     * @Given A client sends a Authorization Code Grant Type request but the code parameter is missing
     */
    public function aClientSendsAAuthorizationCodeGrantTypeRequestButTheCodeParameterIsMissing()
    {
        $request = $this->applicationContext->getServerRequestFactory()->createServerRequest([]);
        $request = $request->withMethod('POST');
        $request = $request->withParsedBody([
            'grant_type' => 'authorization_code',
            'scope' => 'openid email phone address',
        ]);
        $request = $request->withHeader('Authorization', 'Basic '.base64_encode('client1:secret'));
        $request = $request->withHeader('Content-Type', 'application/x-www-form-urlencoded');

        $this->responseContext->setResponse($this->applicationContext->getApplication()->getTokenEndpointPipe()->dispatch($request));
    }

    /**
     * @Given A client sends a Authorization Code Grant Type request but the redirection Uri parameter is missing
     */
    public function aClientSendsAAuthorizationCodeGrantTypeRequestButTheRedirectionUriParameterIsMissing()
    {
        $request = $this->applicationContext->getServerRequestFactory()->createServerRequest([]);
        $request = $request->withMethod('POST');
        $request = $request->withParsedBody([
            'grant_type' => 'authorization_code',
            'code' => 'VALID_AUTH_CODE',
            'scope' => 'openid email phone address',
        ]);
        $request = $request->withHeader('Authorization', 'Basic '.base64_encode('client1:secret'));
        $request = $request->withHeader('Content-Type', 'application/x-www-form-urlencoded');

        $this->responseContext->setResponse($this->applicationContext->getApplication()->getTokenEndpointPipe()->dispatch($request));
    }

    /**
     * @Given A client sends a Authorization Code Grant Type request but the redirection Uri parameter mismatch
     */
    public function aClientSendsAAuthorizationCodeGrantTypeRequestButTheRedirectionUriParameterMismatch()
    {
        $request = $this->applicationContext->getServerRequestFactory()->createServerRequest([]);
        $request = $request->withMethod('POST');
        $request = $request->withParsedBody([
            'grant_type' => 'authorization_code',
            'code' => 'VALID_AUTH_CODE',
            'redirect_uri' => 'http://127.0.0.1/',
            'scope' => 'openid email phone address',
        ]);
        $request = $request->withHeader('Authorization', 'Basic '.base64_encode('client1:secret'));
        $request = $request->withHeader('Content-Type', 'application/x-www-form-urlencoded');

        $this->responseContext->setResponse($this->applicationContext->getApplication()->getTokenEndpointPipe()->dispatch($request));
    }

    /**
     * @Given A client sends a valid Authorization Code Grant Type request
     */
    public function aClientSendsAValidAuthorizationCodeGrantTypeRequest()
    {
        $request = $this->applicationContext->getServerRequestFactory()->createServerRequest([]);
        $request = $request->withMethod('POST');
        $request = $request->withParsedBody([
            'grant_type' => 'authorization_code',
            'code' => 'VALID_AUTH_CODE',
            'redirect_uri' => 'https://www.example.com/callback',
            'scope' => 'openid email phone address',
        ]);
        $request = $request->withHeader('Authorization', 'Basic '.base64_encode('client1:secret'));
        $request = $request->withHeader('Content-Type', 'application/x-www-form-urlencoded');

        $this->responseContext->setResponse($this->applicationContext->getApplication()->getTokenEndpointPipe()->dispatch($request));
    }

    /**
     * @Then an authorization code used event is thrown
     */
    public function anAuthorizationCodeUsedEventIsThrown()
    {
        $events = $this->applicationContext->getApplication()->getAuthCodeMarkedAsUsedEventHandler()->getEvents();
        Assertion::greaterThan(count($events), 0);
    }

    /**
     * @Given A client sends a valid Authorization Code Grant Type request with reduced scope
     */
    public function aClientSendsAValidAuthorizationCodeGrantTypeRequestWithReducedScope()
    {
        $request = $this->applicationContext->getServerRequestFactory()->createServerRequest([]);
        $request = $request->withMethod('POST');
        $request = $request->withParsedBody([
            'grant_type' => 'authorization_code',
            'code' => 'VALID_AUTH_CODE',
            'redirect_uri' => 'https://www.example.com/callback',
            'scope' => 'openid',
        ]);
        $request = $request->withHeader('Authorization', 'Basic '.base64_encode('client1:secret'));
        $request = $request->withHeader('Content-Type', 'application/x-www-form-urlencoded');

        $this->responseContext->setResponse($this->applicationContext->getApplication()->getTokenEndpointPipe()->dispatch($request));
    }

    /**
     * @Given A client sends a Authorization Code Grant Type request but a scope is not allowed
     */
    public function aClientSendsAAuthorizationCodeGrantTypeRequestButAScopeIsNotAllowed()
    {
        $request = $this->applicationContext->getServerRequestFactory()->createServerRequest([]);
        $request = $request->withMethod('POST');
        $request = $request->withParsedBody([
            'grant_type' => 'authorization_code',
            'code' => 'VALID_AUTH_CODE',
            'redirect_uri' => 'https://www.example.com/callback',
            'scope' => 'openid write',
        ]);
        $request = $request->withHeader('Authorization', 'Basic '.base64_encode('client1:secret'));
        $request = $request->withHeader('Content-Type', 'application/x-www-form-urlencoded');

        $this->responseContext->setResponse($this->applicationContext->getApplication()->getTokenEndpointPipe()->dispatch($request));
    }

    /**
     * @Given A client sends a Authorization Code Grant Type request but a authorization code is for another client
     */
    public function aClientSendsAAuthorizationCodeGrantTypeRequestButAAuthorizationCodeIsForAnotherClient()
    {
        $request = $this->applicationContext->getServerRequestFactory()->createServerRequest([]);
        $request = $request->withMethod('POST');
        $request = $request->withParsedBody([
            'grant_type' => 'authorization_code',
            'code' => 'VALID_AUTH_CODE',
            'redirect_uri' => 'https://www.example.com/callback',
            'scope' => 'openid',
            'client_id' => 'client2',
        ]);
        $request = $request->withHeader('Content-Type', 'application/x-www-form-urlencoded');

        $this->responseContext->setResponse($this->applicationContext->getApplication()->getTokenEndpointPipe()->dispatch($request));
    }

    /**
     * @Given A client sends a Authorization Code Grant Type request but the authorization code expired
     */
    public function aClientSendsAAuthorizationCodeGrantTypeRequestButTheAuthorizationCodeExpired()
    {
        $request = $this->applicationContext->getServerRequestFactory()->createServerRequest([]);
        $request = $request->withMethod('POST');
        $request = $request->withParsedBody([
            'grant_type' => 'authorization_code',
            'code' => 'EXPIRED_AUTH_CODE',
            'redirect_uri' => 'https://www.example.com/callback',
            'scope' => 'openid',
        ]);
        $request = $request->withHeader('Authorization', 'Basic '.base64_encode('client1:secret'));
        $request = $request->withHeader('Content-Type', 'application/x-www-form-urlencoded');

        $this->responseContext->setResponse($this->applicationContext->getApplication()->getTokenEndpointPipe()->dispatch($request));
    }

    /**
     * @Given A client sends a Authorization Code Grant Type request but the authorization code is revoked
     */
    public function aClientSendsAAuthorizationCodeGrantTypeRequestButTheAuthorizationCodeIsRevoked()
    {
        $request = $this->applicationContext->getServerRequestFactory()->createServerRequest([]);
        $request = $request->withMethod('POST');
        $request = $request->withParsedBody([
            'grant_type' => 'authorization_code',
            'code' => 'REVOKED_AUTH_CODE',
            'redirect_uri' => 'https://www.example.com/callback',
            'scope' => 'openid',
        ]);
        $request = $request->withHeader('Authorization', 'Basic '.base64_encode('client1:secret'));
        $request = $request->withHeader('Content-Type', 'application/x-www-form-urlencoded');

        $this->responseContext->setResponse($this->applicationContext->getApplication()->getTokenEndpointPipe()->dispatch($request));
    }

    /**
     * @Given A client sends a Authorization Code Grant Type request but the authorization code is used
     */
    public function aClientSendsAAuthorizationCodeGrantTypeRequestButTheAuthorizationCodeIsUsed()
    {
        $request = $this->applicationContext->getServerRequestFactory()->createServerRequest([]);
        $request = $request->withMethod('POST');
        $request = $request->withParsedBody([
            'grant_type' => 'authorization_code',
            'code' => 'USED_AUTH_CODE',
            'redirect_uri' => 'https://www.example.com/callback',
            'scope' => 'openid',
        ]);
        $request = $request->withHeader('Authorization', 'Basic '.base64_encode('client1:secret'));
        $request = $request->withHeader('Content-Type', 'application/x-www-form-urlencoded');

        $this->responseContext->setResponse($this->applicationContext->getApplication()->getTokenEndpointPipe()->dispatch($request));
    }

    /**
     * @Given A client sends a Authorization Code Grant Type request but the authorization code requires a code_verifier parameter
     */
    public function aClientSendsAAuthorizationCodeGrantTypeRequestButTheAuthorizationCodeRequiresACodeVerifierParameter()
    {
        $request = $this->applicationContext->getServerRequestFactory()->createServerRequest([]);
        $request = $request->withMethod('POST');
        $request = $request->withParsedBody([
            'grant_type' => 'authorization_code',
            'code' => 'AUTH_CODE_WITH_CODE_VERIFIER_PLAIN',
            'redirect_uri' => 'https://www.example.com/callback',
            'scope' => 'openid',
        ]);
        $request = $request->withHeader('Authorization', 'Basic '.base64_encode('client1:secret'));
        $request = $request->withHeader('Content-Type', 'application/x-www-form-urlencoded');

        $this->responseContext->setResponse($this->applicationContext->getApplication()->getTokenEndpointPipe()->dispatch($request));
    }

    /**
     * @Given A client sends a Authorization Code Grant Type request but the code_verifier parameter of the authorization code is invalid
     */
    public function aClientSendsAAuthorizationCodeGrantTypeRequestButTheCodeVerifierParameterOfTheAuthorizationCodeIsInvalid()
    {
        $request = $this->applicationContext->getServerRequestFactory()->createServerRequest([]);
        $request = $request->withMethod('POST');
        $request = $request->withParsedBody([
            'grant_type' => 'authorization_code',
            'code' => 'AUTH_CODE_WITH_CODE_VERIFIER_PLAIN',
            'redirect_uri' => 'https://www.example.com/callback',
            'scope' => 'openid',
            'code_verifier' => 'BAD CODE VERIFIER',
        ]);
        $request = $request->withHeader('Authorization', 'Basic '.base64_encode('client1:secret'));
        $request = $request->withHeader('Content-Type', 'application/x-www-form-urlencoded');

        $this->responseContext->setResponse($this->applicationContext->getApplication()->getTokenEndpointPipe()->dispatch($request));
    }

    /**
     * @Given A client sends a valid Authorization Code Grant Type request with code verifier that uses plain method
     */
    public function aClientSendsAValidAuthorizationCodeGrantTypeRequestWithCodeVerifierThatUsesPlainMethod()
    {
        $request = $this->applicationContext->getServerRequestFactory()->createServerRequest([]);
        $request = $request->withMethod('POST');
        $request = $request->withParsedBody([
            'grant_type' => 'authorization_code',
            'code' => 'AUTH_CODE_WITH_CODE_VERIFIER_PLAIN',
            'redirect_uri' => 'https://www.example.com/callback',
            'scope' => 'openid',
            'code_verifier' => 'E9Melhoa2OwvFrEMTJguCHaoeK1t8URWbuGJSstw-cM',
        ]);
        $request = $request->withHeader('Authorization', 'Basic '.base64_encode('client1:secret'));
        $request = $request->withHeader('Content-Type', 'application/x-www-form-urlencoded');

        $this->responseContext->setResponse($this->applicationContext->getApplication()->getTokenEndpointPipe()->dispatch($request));
    }

    /**
     * @Given A client sends a valid Authorization Code Grant Type request with code verifier that uses S256 method
     */
    public function aClientSendsAValidAuthorizationCodeGrantTypeRequestWithCodeVerifierThatUsesSMethod()
    {
        $request = $this->applicationContext->getServerRequestFactory()->createServerRequest([]);
        $request = $request->withMethod('POST');
        $request = $request->withParsedBody([
            'grant_type' => 'authorization_code',
            'code' => 'AUTH_CODE_WITH_CODE_VERIFIER_S256',
            'redirect_uri' => 'https://www.example.com/callback',
            'scope' => 'openid',
            'code_verifier' => 'E9Melhoa2OwvFrEMTJguCHaoeK1t8URWbuGJSstw-cM',
        ]);
        $request = $request->withHeader('Authorization', 'Basic '.base64_encode('client1:secret'));
        $request = $request->withHeader('Content-Type', 'application/x-www-form-urlencoded');

        $this->responseContext->setResponse($this->applicationContext->getApplication()->getTokenEndpointPipe()->dispatch($request));
    }
}
