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

final class ApiContext implements Context
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
     * @When a client sends an API request without expired token
     */
    public function aClientSendsAnApiRequestWithoutExpiredToken()
    {
        $this->minkContext->getSession()->getDriver()->getClient()->request(
            'GET',
            'https://oauth2.test/api/hello/john'
        );
    }

    /**
     * @When a client sends an API request using an expired token
     */
    public function aClientSendsAnApiRequestUsingAnExpiredToken()
    {
        $this->minkContext->getSession()->getDriver()->getClient()->request(
            'GET',
            'https://oauth2.test/api/hello/john',
            [],
            [],
            [
                'HTTP_Authorization' => 'Bearer ACCESS_TOKEN_FOR_API_EXPIRED',
            ]
        );
    }

    /**
     * @When a client sends an API request using a revoked token
     */
    public function aClientSendsAnApiRequestUsingARevokedToken()
    {
        $this->minkContext->getSession()->getDriver()->getClient()->request(
            'GET',
            'https://oauth2.test/api/hello/john',
            [],
            [],
            [
                'HTTP_Authorization' => 'Bearer ACCESS_TOKEN_FOR_API_REVOKED',
            ]
        );
    }

    /**
     * @When a client sends an API request using an insufficient scope
     */
    public function aClientSendsAnApiRequestUsingAnInsufficientScope()
    {
        $this->minkContext->getSession()->getDriver()->getClient()->request(
            'GET',
            'https://oauth2.test/api/scope',
            [],
            [],
            [
                'HTTP_Authorization' => 'Bearer ACCESS_TOKEN_FOR_API',
            ]
        );
    }

    /**
     * @When a client sends a valid API request to a resource that does not need any scope
     */
    public function aClientSendsAValidApiRequestToAResourceThatDoesNotNeedAnyScope()
    {
        $this->minkContext->getSession()->getDriver()->getClient()->request(
            'GET',
            'https://oauth2.test/api/hello/john',
            [],
            [],
            [
                'HTTP_Authorization' => 'Bearer ACCESS_TOKEN_FOR_API',
            ]
        );
    }

    /**
     * @When a client sends a valid API request using a token with sufficient scope
     */
    public function aClientSendsAValidApiRequestUsingATokenWithSufficientScope()
    {
        $this->minkContext->getSession()->getDriver()->getClient()->request(
            'GET',
            'https://oauth2.test/api/scope',
            [],
            [],
            [
                'HTTP_Authorization' => 'Bearer ACCESS_TOKEN_FOR_API_WITH_SUFFICIENT_SCOPE',
            ]
        );
    }

    /**
     * @Then I print :header header
     */
    public function iPrintHeader(string $header)
    {
        $data = $this->minkContext->getSession()->getResponseHeader($header);
        if (null === $data) {
            throw new \Exception(sprintf('No header with name "%s"', $header));
        }
        dump($data);
    }
}
