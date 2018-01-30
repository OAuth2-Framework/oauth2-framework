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

final class IssuerDiscoveryContext implements Context
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
     * @When A client sends an Issuer Discovery request without rel parameter
     */
    public function aClientSendAnIssuerDiscoveryRequestWithoutRelParameter()
    {
        $this->minkContext->getSession()->getDriver()->getClient()->request(
            'GET',
            'https://oauth2.test/.well-known/webfinger',
            [],
            [],
            []
        );
    }

    /**
     * @When A client sends an Issuer Discovery request with an invalid rel parameter
     */
    public function aClientSendAnIssuerDiscoveryRequestWithAnInvalidRelParameter()
    {
        $this->minkContext->getSession()->getDriver()->getClient()->request(
            'GET',
            'https://oauth2.test/.well-known/webfinger',
            [
                'rel' => 'foo.bar',
            ],
            [],
            []
        );
    }

    /**
     * @When A client sends an Issuer Discovery request without resource parameter
     */
    public function aClientSendAnIssuerDiscoveryRequestWithoutResourceParameter()
    {
        $this->minkContext->getSession()->getDriver()->getClient()->request(
            'GET',
            'https://oauth2.test/.well-known/webfinger',
            [
                'rel' => 'http://openid.net/specs/connect/1.0/issuer',
            ],
            [],
            []
        );
    }

    /**
     * @When A client sends an Issuer Discovery request with an invalid resource parameter based on an XRI
     */
    public function aClientSendAnIssuerDiscoveryRequestWithAnInvalidResourceParameterBasedOnAnXRI()
    {
        $this->minkContext->getSession()->getDriver()->getClient()->request(
            'GET',
            'https://oauth2.test/.well-known/webfinger',
            [
                'rel' => 'http://openid.net/specs/connect/1.0/issuer',
                'resource' => '@foo',
            ],
            [],
            []
        );
    }

    /**
     * @When A client sends an Issuer Discovery request with an invalid resource parameter based on an email
     */
    public function aClientSendAnIssuerDiscoveryRequestWithAnInvalidResourceParameterBasedOnAnEmail()
    {
        $this->minkContext->getSession()->getDriver()->getClient()->request(
            'GET',
            'https://oauth2.test/.well-known/webfinger',
            [
                'rel' => 'http://openid.net/specs/connect/1.0/issuer',
                'resource' => 'acct:john@example.com',
            ],
            [],
            []
        );
    }

    /**
     * @When A client sends an Issuer Discovery request with an invalid resource parameter based on an Url
     */
    public function aClientSendAnIssuerDiscoveryRequestWithAnInvalidResourceParameterBasedOnAnUrl()
    {
        $this->minkContext->getSession()->getDriver()->getClient()->request(
            'GET',
            'https://oauth2.test/.well-known/webfinger',
            [
                'rel' => 'http://openid.net/specs/connect/1.0/issuer',
                'resource' => 'https://example.com:8080/+john',
            ],
            [],
            []
        );
    }

    /**
     * @When A client sends an Issuer Discovery request with a valid resource parameter based on an email
     */
    public function aClientSendAnIssuerDiscoveryRequestWithAValidResourceParameterBasedOnAnEmail()
    {
        $this->minkContext->getSession()->getDriver()->getClient()->request(
            'GET',
            'https://oauth2.test/.well-known/webfinger',
            [
                'rel' => 'http://openid.net/specs/connect/1.0/issuer',
                'resource' => 'acct:john@my-service.com:9000',
            ],
            [],
            []
        );
    }

    /**
     * @When A client sends an Issuer Discovery request with a valid resource parameter based on an Url
     */
    public function aClientSendAnIssuerDiscoveryRequestWithAValidResourceParameterBasedOnAnUrl()
    {
        $this->minkContext->getSession()->getDriver()->getClient()->request(
            'GET',
            'https://oauth2.test/.well-known/webfinger',
            [
                'rel' => 'http://openid.net/specs/connect/1.0/issuer',
                'resource' => 'https://my-service.com:9000/+john',
            ],
            [],
            []
        );
    }
}
