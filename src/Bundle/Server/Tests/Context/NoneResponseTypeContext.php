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
use Behat\Behat\Tester\Exception\PendingException;
use Behat\MinkExtension\Context\MinkContext;
use Behat\Symfony2Extension\Context\KernelDictionary;

final class NoneResponseTypeContext implements Context
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
     * @Given A client sends a authorization requests with the None Response Type
     */
    public function aClientSendsAAuthorizationRequestsWithTheNoneResponseType()
    {
        throw new PendingException();
        $this->minkContext->getSession()->getDriver()->getClient()->request(
            'GET', 'https://oauth2.test/authorize',
            [
                'client_id' => 'client1',
                'redirect_uri' => 'https://example.com/redirection/callback',
                'response_type' => 'none',
                'state' => '0123456789',
            ]
        );
    }
}
