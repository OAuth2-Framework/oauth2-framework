<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2018 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\Bundle\Server\Tests\Context;

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2018 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

use Behat\Behat\Context\Context;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\MinkExtension\Context\MinkContext;
use Behat\Symfony2Extension\Context\KernelDictionary;
use OAuth2Framework\Bundle\Server\Tests\TestBundle\Listener;

final class AuthorizationCodeGrantTypeContext implements Context
{
    use KernelDictionary;

    /**
     * @var MinkContext
     */
    private $minkContext;

    /**
     * @BeforeScenario
     *
     * @param BeforeScenarioScope $scope
     */
    public function gatherContexts(BeforeScenarioScope $scope)
    {
        $environment = $scope->getEnvironment();

        $this->minkContext = $environment->getContext(MinkContext::class);
    }

    /**
     * @Given A client sends an Authorization Code Grant Type request but the code parameter is missing
     */
    public function aClientSendsAnAuthorizationCodeGrantTypeRequestButTheCodeParameterIsMissing()
    {
        $this->minkContext->getSession()->getDriver()->getClient()->request('POST', 'https://oauth2.test/token/get', [
            'grant_type' => 'authorization_code',
            'scope' => 'openid email phone address',
        ],
            [], [
                'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
                'HTTP_Authorization' => 'Basic '.base64_encode('client1:secret'),
            ]
        );
    }

    /**
     * @Given A client sends an Authorization Code Grant Type request but the redirection Uri parameter is missing
     */
    public function aClientSendsAnAuthorizationCodeGrantTypeRequestButTheRedirectionUriParameterIsMissing()
    {
        $this->minkContext->getSession()->getDriver()->getClient()->request('POST', 'https://oauth2.test/token/get', [
            'grant_type' => 'authorization_code',
            'code' => 'VALID_AUTH_CODE',
            'scope' => 'openid email phone address',
        ],
            [], [
                'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
                'HTTP_Authorization' => 'Basic '.base64_encode('client1:secret'),
            ]
        );
    }

    /**
     * @Given A client sends an Authorization Code Grant Type request but the redirection Uri parameter mismatch
     */
    public function aClientSendsAnAuthorizationCodeGrantTypeRequestButTheRedirectionUriParameterMismatch()
    {
        $this->minkContext->getSession()->getDriver()->getClient()->request('POST', 'https://oauth2.test/token/get', [
            'grant_type' => 'authorization_code',
            'code' => 'VALID_AUTH_CODE',
            'redirect_uri' => 'http://127.0.0.1/',
            'scope' => 'openid email phone address',
        ],
            [], [
                'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
                'HTTP_Authorization' => 'Basic '.base64_encode('client1:secret'),
            ]
        );
    }

    /**
     * @Given A client sends a valid Authorization Code Grant Type request
     */
    public function aClientSendsAValidAuthorizationCodeGrantTypeRequest()
    {
        $this->minkContext->getSession()->getDriver()->getClient()->request('POST', 'https://oauth2.test/token/get', [
            'grant_type' => 'authorization_code',
            'code' => 'VALID_AUTH_CODE',
            'redirect_uri' => 'https://www.example.com/callback',
            'scope' => 'openid email phone address',
        ],
            [], [
                'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
                'HTTP_Authorization' => 'Basic '.base64_encode('client1:secret'),
            ]
        );
    }

    /**
     * @Then an authorization code used event is thrown
     */
    public function anAuthorizationCodeUsedEventIsThrown()
    {
        $events = $this->getContainer()->get(Listener\AuthCodeMarkedAsUsedListener::class)->getEvents();
        Assertion::greaterThan(count($events), 0);
    }

    /**
     * @Given A client sends a valid Authorization Code Grant Type request with reduced scope
     */
    public function aClientSendsAValidAuthorizationCodeGrantTypeRequestWithReducedScope()
    {
        $this->minkContext->getSession()->getDriver()->getClient()->request('POST', 'https://oauth2.test/token/get', [
            'grant_type' => 'authorization_code',
            'code' => 'VALID_AUTH_CODE',
            'redirect_uri' => 'https://www.example.com/callback',
            'scope' => 'openid',
        ],
            [], [
                'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
                'HTTP_Authorization' => 'Basic '.base64_encode('client1:secret'),
            ]
        );
    }

    /**
     * @Given A client sends an Authorization Code Grant Type request but a scope is not allowed
     */
    public function aClientSendsAnAuthorizationCodeGrantTypeRequestButAScopeIsNotAllowed()
    {
        $this->minkContext->getSession()->getDriver()->getClient()->request('POST', 'https://oauth2.test/token/get', [
            'grant_type' => 'authorization_code',
            'code' => 'VALID_AUTH_CODE',
            'redirect_uri' => 'https://www.example.com/callback',
            'scope' => 'openid write',
        ],
            [], [
                'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
                'HTTP_Authorization' => 'Basic '.base64_encode('client1:secret'),
            ]
        );
    }

    /**
     * @Given A client sends an Authorization Code Grant Type request but a authorization code is for another client
     */
    public function aClientSendsAnAuthorizationCodeGrantTypeRequestButAAuthorizationCodeIsForAnotherClient()
    {
        $this->minkContext->getSession()->getDriver()->getClient()->request('POST', 'https://oauth2.test/token/get', [
            'grant_type' => 'authorization_code',
            'code' => 'VALID_AUTH_CODE',
            'redirect_uri' => 'https://www.example.com/callback',
            'scope' => 'openid',
            'client_id' => 'client2',
        ],
            [], [
                'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
            ]
        );
    }

    /**
     * @Given A client sends an Authorization Code Grant Type request but the authorization code expired
     */
    public function aClientSendsAnAuthorizationCodeGrantTypeRequestButTheAuthorizationCodeExpired()
    {
        $this->minkContext->getSession()->getDriver()->getClient()->request('POST', 'https://oauth2.test/token/get', [
            'grant_type' => 'authorization_code',
            'code' => 'EXPIRED_AUTH_CODE',
            'redirect_uri' => 'https://www.example.com/callback',
            'scope' => 'openid',
        ],
            [], [
                'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
                'HTTP_Authorization' => 'Basic '.base64_encode('client1:secret'),
            ]
        );
    }

    /**
     * @Given A client sends an Authorization Code Grant Type request but the authorization code requires a code_verifier parameter
     */
    public function aClientSendsAnAuthorizationCodeGrantTypeRequestButTheAuthorizationCodeRequiresACodeVerifierParameter()
    {
        $this->minkContext->getSession()->getDriver()->getClient()->request('POST', 'https://oauth2.test/token/get', [
            'grant_type' => 'authorization_code',
            'code' => 'AUTH_CODE_WITH_CODE_VERIFIER_PLAIN',
            'redirect_uri' => 'https://www.example.com/callback',
            'scope' => 'openid',
        ],
            [], [
                'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
                'HTTP_Authorization' => 'Basic '.base64_encode('client1:secret'),
            ]
        );
    }

    /**
     * @Given A client sends an Authorization Code Grant Type request but the code_verifier parameter of the authorization code is invalid
     */
    public function aClientSendsAnAuthorizationCodeGrantTypeRequestButTheCodeVerifierParameterOfTheAuthorizationCodeIsInvalid()
    {
        $this->minkContext->getSession()->getDriver()->getClient()->request('POST', 'https://oauth2.test/token/get', [
            'grant_type' => 'authorization_code',
            'code' => 'AUTH_CODE_WITH_CODE_VERIFIER_PLAIN',
            'redirect_uri' => 'https://www.example.com/callback',
            'scope' => 'openid',
            'code_verifier' => 'BAD CODE VERIFIER',
        ],
            [], [
                'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
                'HTTP_Authorization' => 'Basic '.base64_encode('client1:secret'),
            ]
        );
    }

    /**
     * @Given A client sends a valid Authorization Code Grant Type request with code verifier that uses plain method
     */
    public function aClientSendsAValidAuthorizationCodeGrantTypeRequestWithCodeVerifierThatUsesPlainMethod()
    {
        $this->minkContext->getSession()->getDriver()->getClient()->request('POST', 'https://oauth2.test/token/get', [
            'grant_type' => 'authorization_code',
            'code' => 'AUTH_CODE_WITH_CODE_VERIFIER_PLAIN',
            'redirect_uri' => 'https://www.example.com/callback',
            'scope' => 'openid',
            'code_verifier' => 'E9Melhoa2OwvFrEMTJguCHaoeK1t8URWbuGJSstw-cM',
        ],
            [], [
                'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
                'HTTP_Authorization' => 'Basic '.base64_encode('client1:secret'),
            ]
        );
    }

    /**
     * @Given A client sends a valid Authorization Code Grant Type request with code verifier that uses S256 method
     */
    public function aClientSendsAValidAuthorizationCodeGrantTypeRequestWithCodeVerifierThatUsesSMethod()
    {
        $this->minkContext->getSession()->getDriver()->getClient()->request('POST', 'https://oauth2.test/token/get', [
            'grant_type' => 'authorization_code',
            'code' => 'AUTH_CODE_WITH_CODE_VERIFIER_S256',
            'redirect_uri' => 'https://www.example.com/callback',
            'scope' => 'openid',
            'code_verifier' => 'E9Melhoa2OwvFrEMTJguCHaoeK1t8URWbuGJSstw-cM',
        ],
            [], [
                'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
                'HTTP_Authorization' => 'Basic '.base64_encode('client1:secret'),
            ]
        );
    }

    /**
     * @Given A client sends a Authorization Code Grant Type request but the code parameter is missing
     */
    public function aClientSendsAAuthorizationCodeGrantTypeRequestButTheCodeParameterIsMissing()
    {
        $this->minkContext->getSession()->getDriver()->getClient()->request('POST', 'https://oauth2.test/token/get', [
            'grant_type' => 'authorization_code',
            'redirect_uri' => 'https://www.example.com/callback',
            'scope' => 'openid',
            'code_verifier' => 'E9Melhoa2OwvFrEMTJguCHaoeK1t8URWbuGJSstw-cM',
        ],
            [], [
                'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
                'HTTP_Authorization' => 'Basic '.base64_encode('client1:secret'),
            ]
        );
    }

    /**
     * @Given A client sends a Authorization Code Grant Type request but the redirection Uri parameter is missing
     */
    public function aClientSendsAAuthorizationCodeGrantTypeRequestButTheRedirectionUriParameterIsMissing()
    {
        $this->minkContext->getSession()->getDriver()->getClient()->request('POST', 'https://oauth2.test/token/get', [
            'grant_type' => 'authorization_code',
            'code' => 'AUTH_CODE_WITH_CODE_VERIFIER_S256',
            'scope' => 'openid',
            'code_verifier' => 'E9Melhoa2OwvFrEMTJguCHaoeK1t8URWbuGJSstw-cM',
        ],
            [], [
                'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
                'HTTP_Authorization' => 'Basic '.base64_encode('client1:secret'),
            ]
        );
    }

    /**
     * @Given A client sends a Authorization Code Grant Type request but the redirection Uri parameter mismatch
     */
    public function aClientSendsAAuthorizationCodeGrantTypeRequestButTheRedirectionUriParameterMismatch()
    {
        $this->minkContext->getSession()->getDriver()->getClient()->request('POST', 'https://oauth2.test/token/get', [
            'grant_type' => 'authorization_code',
            'code' => 'AUTH_CODE_WITH_CODE_VERIFIER_S256',
            'redirect_uri' => 'https://bad.callback.foo',
            'scope' => 'openid',
            'code_verifier' => 'E9Melhoa2OwvFrEMTJguCHaoeK1t8URWbuGJSstw-cM',
        ],
            [], [
                'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
                'HTTP_Authorization' => 'Basic '.base64_encode('client1:secret'),
            ]
        );
    }

    /**
     * @Given A client sends a Authorization Code Grant Type request but a scope is not allowed
     */
    public function aClientSendsAAuthorizationCodeGrantTypeRequestButAScopeIsNotAllowed()
    {
        $this->minkContext->getSession()->getDriver()->getClient()->request('POST', 'https://oauth2.test/token/get', [
            'grant_type' => 'authorization_code',
            'code' => 'AUTH_CODE_WITH_CODE_VERIFIER_S256',
            'redirect_uri' => 'https://www.example.com/callback',
            'scope' => 'openid profile email phone address',
            'code_verifier' => 'E9Melhoa2OwvFrEMTJguCHaoeK1t8URWbuGJSstw-cM',
        ],
            [], [
                'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
                'HTTP_Authorization' => 'Basic '.base64_encode('client1:secret'),
            ]
        );
    }

    /**
     * @Given A client sends a Authorization Code Grant Type request but a authorization code is for another client
     */
    public function aClientSendsAAuthorizationCodeGrantTypeRequestButAAuthorizationCodeIsForAnotherClient()
    {
        $this->minkContext->getSession()->getDriver()->getClient()->request('POST', 'https://oauth2.test/token/get', [
            'grant_type' => 'authorization_code',
            'code' => 'VALID_AUTH_CODE',
            'redirect_uri' => 'https://www.example.com/callback',
            'scope' => 'openid',
            'client_id' => 'client2',
        ],
            [], [
                'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
            ]
        );
    }

    /**
     * @Given A client sends a Authorization Code Grant Type request but the authorization code expired
     */
    public function aClientSendsAAuthorizationCodeGrantTypeRequestButTheAuthorizationCodeExpired()
    {
        $this->minkContext->getSession()->getDriver()->getClient()->request('POST', 'https://oauth2.test/token/get', [
            'grant_type' => 'authorization_code',
            'code' => 'EXPIRED_AUTH_CODE',
            'redirect_uri' => 'https://www.example.com/callback',
            'scope' => 'openid',
            'code_verifier' => 'E9Melhoa2OwvFrEMTJguCHaoeK1t8URWbuGJSstw-cM',
        ],
            [], [
                'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
                'HTTP_Authorization' => 'Basic '.base64_encode('client1:secret'),
            ]
        );
    }

    /**
     * @Given A client sends a Authorization Code Grant Type request but the authorization code is revoked
     */
    public function aClientSendsAAuthorizationCodeGrantTypeRequestButTheAuthorizationCodeIsRevoked()
    {
        $this->minkContext->getSession()->getDriver()->getClient()->request('POST', 'https://oauth2.test/token/get', [
            'grant_type' => 'authorization_code',
            'code' => 'AUTH_CODE_REVOKED',
            'redirect_uri' => 'https://www.example.com/callback',
            'scope' => 'openid',
            'code_verifier' => 'E9Melhoa2OwvFrEMTJguCHaoeK1t8URWbuGJSstw-cM',
        ],
            [], [
                'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
                'HTTP_Authorization' => 'Basic '.base64_encode('client1:secret'),
            ]
        );
    }

    /**
     * @Given A client sends a Authorization Code Grant Type request but the authorization code is used
     */
    public function aClientSendsAAuthorizationCodeGrantTypeRequestButTheAuthorizationCodeIsUsed()
    {
        $this->minkContext->getSession()->getDriver()->getClient()->request('POST', 'https://oauth2.test/token/get', [
            'grant_type' => 'authorization_code',
            'code' => 'AUTH_CODE_USED',
            'redirect_uri' => 'https://www.example.com/callback',
            'scope' => 'openid',
            'code_verifier' => 'E9Melhoa2OwvFrEMTJguCHaoeK1t8URWbuGJSstw-cM',
        ],
            [], [
                'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
                'HTTP_Authorization' => 'Basic '.base64_encode('client1:secret'),
            ]
        );
    }

    /**
     * @Given A client sends a Authorization Code Grant Type request but the authorization code requires a code_verifier parameter
     */
    public function aClientSendsAAuthorizationCodeGrantTypeRequestButTheAuthorizationCodeRequiresACodeVerifierParameter()
    {
        $this->minkContext->getSession()->getDriver()->getClient()->request('POST', 'https://oauth2.test/token/get', [
            'grant_type' => 'authorization_code',
            'code' => 'AUTH_CODE_WITH_CODE_VERIFIER_S256',
            'redirect_uri' => 'https://www.example.com/callback',
            'scope' => 'openid',
        ],
            [], [
                'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
                'HTTP_Authorization' => 'Basic '.base64_encode('client1:secret'),
            ]
        );
    }

    /**
     * @Given A client sends a Authorization Code Grant Type request but the code_verifier parameter of the authorization code is invalid
     */
    public function aClientSendsAAuthorizationCodeGrantTypeRequestButTheCodeVerifierParameterOfTheAuthorizationCodeIsInvalid()
    {
        $this->minkContext->getSession()->getDriver()->getClient()->request('POST', 'https://oauth2.test/token/get', [
            'grant_type' => 'authorization_code',
            'code' => 'AUTH_CODE_WITH_CODE_VERIFIER_S256',
            'redirect_uri' => 'https://www.example.com/callback',
            'scope' => 'openid',
            'code_verifier' => 'INVALID CODE VERIFIER',
        ],
            [], [
                'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
                'HTTP_Authorization' => 'Basic '.base64_encode('client1:secret'),
            ]
        );
    }
}
