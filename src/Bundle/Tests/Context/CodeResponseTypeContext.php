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

namespace OAuth2Framework\Bundle\Tests\Context;

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
use Http\Factory\Diactoros\UriFactory;
use OAuth2Framework\Bundle\Tests\TestBundle\Listener;

final class CodeResponseTypeContext implements Context
{
    use KernelDictionary;

    /**
     * @var ResponseTypeContext
     */
    private $responseTypeContext;

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
        $this->responseTypeContext = $environment->getContext(ResponseTypeContext::class);
    }

    /**
     * @Given A client sends an authorization requests with the Authorization Code Response Type and a Code Challenge, but the method is not supported
     */
    public function aClientSendsAnAuthorizationRequestsWithTheAuthorizationCodeResponseTypeAndACodeChallengeButTheMethodIsNotSupported()
    {
        $this->minkContext->getSession()->getDriver()->getClient()->request(
            'GET', 'https://oauth2.test/authorize',
            [
                'client_id' => 'client1',
                'redirect_uri' => 'https://example.com/redirection/callback',
                'response_type' => 'code',
                'code_challenge' => 'E9Melhoa2OwvFrEMTJguCHaoeK1t8URWbuGJSstw-cM',
                'code_challenge_method' => 'foo',
                'state' => '0123456789',
            ]
        );
    }

    /**
     * @Given A client sends an authorization requests with the Authorization Code Response Type
     */
    public function aClientSendsAnAuthorizationRequestsWithTheAuthorizationCodeResponseType()
    {
        $this->minkContext->getSession()->getDriver()->getClient()->request(
            'GET', 'https://oauth2.test/authorize',
            [
                'client_id' => 'client1',
                'redirect_uri' => 'https://example.com/redirection/callback',
                'response_type' => 'code',
                'state' => '0123456789',
            ]
        );
    }

    /**
     * @Given A client sends an authorization requests with the Authorization Code Response Type and a code verifier
     */
    public function aClientSendsAnAuthorizationRequestsWithTheAuthorizationCodeResponseTypeAndACodeVerifier()
    {
        $this->minkContext->getSession()->getDriver()->getClient()->request(
            'GET', 'https://oauth2.test/authorize',
            [
                'client_id' => 'client1',
                'redirect_uri' => 'https://example.com/redirection/callback',
                'response_type' => 'code',
                'code_challenge' => 'E9Melhoa2OwvFrEMTJguCHaoeK1t8URWbuGJSstw-cM',
                'code_challenge_method' => 'S256',
                'state' => '0123456789',
            ]
        );
    }

    /**
     * @Then an authorization code creation event is thrown
     */
    public function anAuthorizationCodeCreationEventIsThrown()
    {
        $events = $this->getContainer()->get(Listener\AuthCodeCreatedListener::class)->getEvents();
        Assertion::greaterThan(count($events), 0);
    }

    /**
     * @Then no authorization code creation event is thrown
     */
    public function noAuthorizationCodeCreationEventIsThrown()
    {
        $events = $this->getContainer()->get(Listener\AuthCodeCreatedListener::class)->getEvents();
        Assertion::eq(0, count($events));
    }

    /**
     * @Given A client sends an authorization requests with the Authorization Code Response Type, a code verifier and the scope "openid"
     */
    public function aClientSendsAnAuthorizationRequestsWithTheAuthorizationCodeResponseTypeACodeVerifierAndTheScopeOpenId()
    {
        $this->minkContext->getSession()->getDriver()->getClient()->request(
            'GET', 'https://oauth2.test/authorize',
            [
                'client_id' => 'client1',
                'redirect_uri' => 'https://example.com/redirection/callback',
                'response_type' => 'code',
                'code_challenge' => 'E9Melhoa2OwvFrEMTJguCHaoeK1t8URWbuGJSstw-cM',
                'code_challenge_method' => 'plain',
                'state' => '0123456789',
                'scope' => 'openid offline_access',
                'prompt' => 'consent',
            ]
        );
    }

    /**
     * @When the client exchanges the authorization for an access token
     */
    public function theClientExchangesTheAuthorizationForAnAccessToken()
    {
        $code = $this->getAuthorizationCodeFromTheResponse();

        $this->minkContext->getSession()->getDriver()->getClient()->request('POST', 'https://oauth2.test/token/get', [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => 'https://example.com/redirection/callback',
            'scope' => 'openid offline_access',
            'code_verifier' => 'E9Melhoa2OwvFrEMTJguCHaoeK1t8URWbuGJSstw-cM',
        ],
            [], [
                'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
                'HTTP_Authorization' => 'Basic '.base64_encode('client1:secret'),
            ]
        );
    }

    /**
     * @return string
     */
    private function getAuthorizationCodeFromTheResponse(): string
    {
        $uriFactory = new UriFactory();
        $location = $this->minkContext->getSession()->getResponseHeader('Location');
        $uri = $uriFactory->createUri($location);
        $query = $uri->getQuery();
        parse_str($query, $data);
        if (array_key_exists('code', $data)) {
            return $data['code'];
        }

        throw new \InvalidArgumentException(sprintf('The location header is "%s".', $location));
    }
}
