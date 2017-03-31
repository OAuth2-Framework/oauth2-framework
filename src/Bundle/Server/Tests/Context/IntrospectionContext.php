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

final class IntrospectionContext implements Context
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
     * @Given An unauthenticated protected resource tries to get information about a token
     */
    public function anUnauthenticatedProtectedResourceTriesToGetInformationAboutAToken()
    {
        $this->minkContext->getSession()->getDriver()->getClient()->request('POST', 'https://oauth2.test/token/introspection', [], [], [
            'HTTP_Content-Type' => 'application/x-www-form-urlencoded',
        ]);
    }

    /**
     * @Given A protected resource sends an invalid introspection request
     */
    public function aProtectedResourceSendsAnInvalidIntrospectionRequest()
    {
        $this->minkContext->getSession()->getDriver()->getClient()->request(
            'POST',
            'https://oauth2.test/token/introspection',
            [],
            [],
            [
                'HTTP_Content-Type' => 'application/x-www-form-urlencoded',
                'HTTP_X-Resource-Server-Id' => 'ResourceServer1',
                'REMOTE_ADDR' => '127.0.0.1',
            ]
        );
    }

    /**
     * @Given A protected resource tries to get information of a token that owns another protected resource
     */
    public function aProtectedResourceTriesToGetInformationOfATokenThatOwnsAnotherProtectedResource()
    {
        $this->minkContext->getSession()->getDriver()->getClient()->request('POST', 'https://oauth2.test/token/introspection', [
            'token' => 'ACCESS_TOKEN_#1',
        ],
        [], [
            'HTTP_Content-Type' => 'application/x-www-form-urlencoded',
            'HTTP_X-Resource-Server-Id' => 'ResourceServer2',
            'REMOTE_ADDR' => '127.0.0.1',
        ]);
    }

    /**
     * @Given A protected resource tries to get information of a token
     */
    public function aProtectedResourceTriesToGetInformationOfAToken()
    {
        $this->minkContext->getSession()->getDriver()->getClient()->request('POST', 'https://oauth2.test/token/introspection', [
            'token' => 'ACCESS_TOKEN_#1',
        ],
        [], [
            'HTTP_Content-Type' => 'application/x-www-form-urlencoded',
            'HTTP_X-Resource-Server-Id' => 'ResourceServer1',
            'REMOTE_ADDR' => '127.0.0.1',
        ]);
    }

    /**
     * @Given A protected resource tries to get information of a revoked token
     */
    public function aProtectedResourceTriesToGetInformationOfARevokedToken()
    {
        $this->minkContext->getSession()->getDriver()->getClient()->request('POST', 'https://oauth2.test/token/introspection', [
            'token' => 'REVOKED_REFRESH_TOKEN',
        ],
            [], [
                'HTTP_Content-Type' => 'application/x-www-form-urlencoded',
                'HTTP_X-Resource-Server-Id' => 'ResourceServer1',
                'REMOTE_ADDR' => '127.0.0.1',
            ]);
    }
}
