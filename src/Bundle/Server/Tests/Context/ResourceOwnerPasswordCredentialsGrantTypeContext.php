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

final class ResourceOwnerPasswordCredentialsGrantTypeContext implements Context
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
     * @Given A client sends a Resource Owner Password Credentials Grant Type request without username parameter
     */
    public function aClientSendsAResourceOwnerPasswordCredentialsGrantTypeRequestWithoutUsernameParameter()
    {
        $this->minkContext->getSession()->getDriver()->getClient()->request('POST', 'https://oauth2.test/token/get', [
            'grant_type' => 'password',
        ],
            [], [
                'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
                'HTTP_Authorization' => 'Basic '.base64_encode('client1:secret'),
            ]
        );
    }

    /**
     * @Given A client sends a Resource Owner Password Credentials Grant Type request without password parameter
     */
    public function aClientSendsAResourceOwnerPasswordCredentialsGrantTypeRequestWithoutPasswordParameter()
    {
        $this->minkContext->getSession()->getDriver()->getClient()->request('POST', 'https://oauth2.test/token/get', [
            'grant_type' => 'password',
            'username' => 'john.1',
        ],
            [], [
                'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
                'HTTP_Authorization' => 'Basic '.base64_encode('client1:secret'),
            ]
        );
    }

    /**
     * @Given A client sends a Resource Owner Password Credentials Grant Type request with invalid user credentials
     */
    public function aClientSendsAResourceOwnerPasswordCredentialsGrantTypeRequestWithInvalidUserCredentials()
    {
        $this->minkContext->getSession()->getDriver()->getClient()->request('POST', 'https://oauth2.test/token/get', [
            'grant_type' => 'password',
            'username' => 'john.1',
            'password' => 'BAD PASSWORD',
        ],
            [], [
                'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
                'HTTP_Authorization' => 'Basic '.base64_encode('client1:secret'),
            ]
        );
    }

    /**
     * @Given A client sends a valid Resource Owner Password Credentials Grant Type request
     */
    public function aClientSendsAValidResourceOwnerPasswordCredentialsGrantTypeRequest()
    {
        $this->minkContext->getSession()->getDriver()->getClient()->request('POST', 'https://oauth2.test/token/get', [
            'grant_type' => 'password',
            'username' => 'john.1',
            'password' => 'doe',
            'scope' => 'email phone address',
        ],
            [], [
                'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
                'HTTP_Authorization' => 'Basic '.base64_encode('client1:secret'),
            ]
        );
    }

    /**
     * @Given A client sends a valid Resource Owner Password Credentials Grant Type request but the grant type is not allowed
     */
    public function aClientSendsAValidResourceOwnerPasswordCredentialsGrantTypeRequestButTheGrantTypeIsNotAllowed()
    {
        $this->minkContext->getSession()->getDriver()->getClient()->request('POST', 'https://oauth2.test/token/get', [
            'grant_type' => 'password',
            'username' => 'john.1',
            'password' => 'doe',
            'client_id' => 'client2',
        ],
            [], [
                'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
            ]
        );
    }
}
