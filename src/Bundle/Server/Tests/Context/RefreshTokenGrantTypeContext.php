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

final class RefreshTokenGrantTypeContext implements Context
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
     * @Given A client sends a Refresh Token Grant Type request without refresh_token parameter
     */
    public function aClientSendsARefreshTokenGrantTypeRequestWithoutRefreshTokenParameter()
    {
        $this->minkContext->getSession()->getDriver()->getClient()->request('POST', 'https://oauth2.test/token/get', [
            'grant_type' => 'refresh_token',
        ],
            [], [
                'HTTP_Content-Type'  => 'application/x-www-form-urlencoded',
                'HTTP_Authorization' => 'Basic '.base64_encode('client1:secret'),
            ]
        );
    }

    /**
     * @Given a client sends a Refresh Token Grant Type request with an expired refresh token
     */
    public function aClientSendsARefreshTokenGrantTypeRequestWithAnExpiredRefreshToken()
    {
        $this->minkContext->getSession()->getDriver()->getClient()->request('POST', 'https://oauth2.test/token/get', [
            'grant_type'    => 'refresh_token',
            'refresh_token' => 'EXPIRED_REFRESH_TOKEN',
        ],
            [], [
                'HTTP_Content-Type'  => 'application/x-www-form-urlencoded',
                'HTTP_Authorization' => 'Basic '.base64_encode('client1:secret'),
            ]
        );
    }

    /**
     * @Given A client sends a valid Refresh Token Grant Type request
     */
    public function aClientSendsAValidRefreshTokenGrantTypeRequest()
    {
        $this->minkContext->getSession()->getDriver()->getClient()->request('POST', 'https://oauth2.test/token/get', [
            'grant_type'    => 'refresh_token',
            'refresh_token' => 'VALID_REFRESH_TOKEN',
        ],
            [], [
                'HTTP_Content-Type'  => 'application/x-www-form-urlencoded',
                'HTTP_Authorization' => 'Basic '.base64_encode('client1:secret'),
            ]
        );
    }

    /**
     * @Given A client sends a valid Refresh Token Grant Type request but the grant type is not allowed
     */
    public function aClientSendsAValidRefreshTokenGrantTypeRequestButTheGrantTypeIsNotAllowed()
    {
        $this->minkContext->getSession()->getDriver()->getClient()->request('POST', 'https://oauth2.test/token/get', [
            'grant_type'    => 'refresh_token',
            'refresh_token' => 'VALID_REFRESH_TOKEN',
            'client_id'     => 'client2',
        ],
            [], [
                'HTTP_Content-Type' => 'application/x-www-form-urlencoded',
            ]
        );
    }

    /**
     * @Given a client sends a Refresh Token Grant Type request with a revoked refresh token
     */
    public function aClientSendsARefreshTokenGrantTypeRequestWithARevokedRefreshToken()
    {
        $this->minkContext->getSession()->getDriver()->getClient()->request('POST', 'https://oauth2.test/token/get', [
            'grant_type'    => 'refresh_token',
            'refresh_token' => 'REVOKED_REFRESH_TOKEN',
        ],
            [], [
                'HTTP_Content-Type'  => 'application/x-www-form-urlencoded',
                'HTTP_Authorization' => 'Basic '.base64_encode('client1:secret'),
            ]
        );
    }
}
