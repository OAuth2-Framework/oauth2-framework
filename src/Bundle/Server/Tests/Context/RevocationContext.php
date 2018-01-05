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
 * Copyright (c) 2014-2017 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

use Behat\Behat\Context\Context;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\MinkExtension\Context\MinkContext;
use Behat\Symfony2Extension\Context\KernelDictionary;
use OAuth2Framework\Bundle\Server\Tests\TestBundle\Listener;

final class RevocationContext implements Context
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
     * @Given a client sends a POST revocation request but it is not authenticated
     */
    public function aClientSendsAPostRevocationRequestButItIsNotAuthenticated()
    {
        $this->minkContext->getSession()->getDriver()->getClient()->request(
            'POST', 'https://oauth2.test/token/revocation',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
            ]
        );
    }

    /**
     * @Given a client sends a GET revocation request but it is not authenticated
     */
    public function aClientSendsAGetRevocationRequestButItIsNotAuthenticated()
    {
        $this->minkContext->getSession()->getDriver()->getClient()->request(
            'GET', 'https://oauth2.test/token/revocation',
            [],
            [],
            []
        );
    }

    /**
     * @Given a client sends a POST revocation request without token parameter
     */
    public function aClientSendsAPostRevocationRequestWithoutTokenParameter()
    {
        $this->minkContext->getSession()->getDriver()->getClient()->request(
            'POST', 'https://oauth2.test/token/revocation',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
                'HTTP_Authorization' => 'Basic '.base64_encode('client1:secret'),
            ]
        );
    }

    /**
     * @Given a client sends a GET revocation request without token parameter
     */
    public function aClientSendsAGetRevocationRequestWithoutTokenParameter()
    {
        $this->minkContext->getSession()->getDriver()->getClient()->request(
            'GET', 'https://oauth2.test/token/revocation',
            [],
            [],
            [
                'HTTP_Authorization' => 'Basic '.base64_encode('client1:secret'),
            ]
        );
    }

    /**
     * @Given a client sends a POST revocation request without token parameter with a callback parameter
     */
    public function aClientSendsAPostRevocationRequestWithoutTokenParameterWithACallbackParameter()
    {
        $this->minkContext->getSession()->getDriver()->getClient()->request(
            'POST', 'https://oauth2.test/token/revocation',
            [
                'callback' => 'foo',
            ],
            [],
            [
                'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
                'HTTP_Authorization' => 'Basic '.base64_encode('client1:secret'),
            ]
        );
    }

    /**
     * @Given a client sends a GET revocation request without token parameter with a callback parameter
     */
    public function aClientSendsAGetRevocationRequestWithoutTokenParameterWithACallbackParameter()
    {
        $this->minkContext->getSession()->getDriver()->getClient()->request(
            'GET', 'https://oauth2.test/token/revocation',
            [
                'callback' => 'foo',
            ],
            [],
            [
                'HTTP_Authorization' => 'Basic '.base64_encode('client1:secret'),
            ]
        );
    }

    /**
     * @Given a client sends a valid POST revocation request
     */
    public function aClientSendsAValidPostRevocationRequest()
    {
        $this->minkContext->getSession()->getDriver()->getClient()->request(
            'POST', 'https://oauth2.test/token/revocation',
            [
                'token' => 'ACCESS_TOKEN_#1',
            ],
            [],
            [
                'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
                'HTTP_Authorization' => 'Basic '.base64_encode('client1:secret'),
            ]
        );
    }

    /**
     * @Given a client sends a valid GET revocation request
     */
    public function aClientSendsAValidGetRevocationRequest()
    {
        $this->minkContext->getSession()->getDriver()->getClient()->request(
            'GET', 'https://oauth2.test/token/revocation',
            [
                'token' => 'ACCESS_TOKEN_#1',
            ],
            [],
            [
                'HTTP_Authorization' => 'Basic '.base64_encode('client1:secret'),
            ]
        );
    }

    /**
     * @Given a client sends a valid POST revocation request but the token owns to another client
     */
    public function aClientSendsAValidPostRevocationRequestButTheTokenOwnsToAnotherClient()
    {
        $this->minkContext->getSession()->getDriver()->getClient()->request(
            'POST', 'https://oauth2.test/token/revocation',
            [
                'token' => 'ACCESS_TOKEN_#2',
            ],
            [],
            [
                'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
                'HTTP_Authorization' => 'Basic '.base64_encode('client1:secret'),
            ]
        );
    }

    /**
     * @Given a client sends a valid GET revocation request but the token owns to another client
     */
    public function aClientSendsAValidGetRevocationRequestButTheTokenOwnsToAnotherClient()
    {
        $this->minkContext->getSession()->getDriver()->getClient()->request(
            'GET', 'https://oauth2.test/token/revocation',
            [
                'token' => 'ACCESS_TOKEN_#2',
            ],
            [],
            [
                'HTTP_Authorization' => 'Basic '.base64_encode('client1:secret'),
            ]
        );
    }

    /**
     * @Given a client sends a POST revocation request but the token type hint is not supported
     */
    public function aClientSendsAPostRevocationRequestButTheTokenTypeHintIsNotSupported()
    {
        $this->minkContext->getSession()->getDriver()->getClient()->request(
            'POST', 'https://oauth2.test/token/revocation',
            [
                'token' => 'ACCESS_TOKEN_#2',
                'token_type_hint' => 'bad_hint',
            ],
            [],
            [
                'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
                'HTTP_Authorization' => 'Basic '.base64_encode('client1:secret'),
            ]
        );
    }

    /**
     * @Given a client sends a GET revocation request but the token type hint is not supported
     */
    public function aClientSendsAGetRevocationRequestButTheTokenTypeHintIsNotSupported()
    {
        $this->minkContext->getSession()->getDriver()->getClient()->request(
            'GET', 'https://oauth2.test/token/revocation',
            [
                'token' => 'ACCESS_TOKEN_#2',
                'token_type_hint' => 'bad_hint',
            ],
            [],
            [
                'HTTP_Authorization' => 'Basic '.base64_encode('client1:secret'),
            ]
        );
    }

    /**
     * @Given a client sends a POST revocation request but the token does not exist or expired
     */
    public function aClientSendsAPostRevocationRequestButTheTokenDoesNotExistOrExpired()
    {
        $this->minkContext->getSession()->getDriver()->getClient()->request(
            'POST', 'https://oauth2.test/token/revocation',
            [
                'token' => 'UNKNOWN_REFRESH_TOKEN_#2',
                'token_type_hint' => 'refresh_token',
            ],
            [],
            [
                'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
                'HTTP_Authorization' => 'Basic '.base64_encode('client1:secret'),
            ]
        );
    }

    /**
     * @Given a client sends a GET revocation request but the token does not exist or expired
     */
    public function aClientSendsAGetRevocationRequestButTheTokenDoesNotExistOrExpired()
    {
        $this->minkContext->getSession()->getDriver()->getClient()->request(
            'GET', 'https://oauth2.test/token/revocation',
            [
                'token' => 'UNKNOWN_REFRESH_TOKEN_#2',
                'token_type_hint' => 'refresh_token',
            ],
            [],
            [
                'HTTP_Authorization' => 'Basic '.base64_encode('client1:secret'),
            ]
        );
    }

    /**
     * @Given a client sends a GET revocation request with callback but the token does not exist or expired
     */
    public function aClientSendsAGetRevocationRequestWithCallbackButTheTokenDoesNotExistOrExpired()
    {
        $this->minkContext->getSession()->getDriver()->getClient()->request(
            'GET', 'https://oauth2.test/token/revocation',
            [
                'token' => 'UNKNOWN_REFRESH_TOKEN_#2',
                'token_type_hint' => 'refresh_token',
                'callback' => 'callback',
            ],
            [],
            [
                'HTTP_Authorization' => 'Basic '.base64_encode('client1:secret'),
            ]
        );
    }

    /**
     * @Then no token revocation event is thrown
     */
    public function noTokenRevocationEventIsThrown()
    {
        $events = $this->getContainer()->get(Listener\AccessTokenRevokedListener::class)->getEvents();
        $events += $this->getContainer()->get(Listener\RefreshTokenRevokedListener::class)->getEvents();
        $events += $this->getContainer()->get(Listener\AuthCodeRevokedListener::class)->getEvents();
        Assertion::eq(0, count($events));
    }

    /**
     * @Then a token revocation event is thrown
     */
    public function aTokenRevocationEventIsThrown()
    {
        $events = $this->getContainer()->get(Listener\AccessTokenRevokedListener::class)->getEvents();
        $events += $this->getContainer()->get(Listener\RefreshTokenRevokedListener::class)->getEvents();
        $events += $this->getContainer()->get(Listener\AuthCodeRevokedListener::class)->getEvents();
        Assertion::greaterThan(count($events), 0);
    }
}
