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

use Base64Url\Base64Url;
use Behat\Behat\Context\Context;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\MinkExtension\Context\MinkContext;
use Behat\Symfony2Extension\Context\KernelDictionary;
use Jose\Factory\JWSFactory;
use OAuth2Framework\Component\Server\Model\Client\ClientId;
use OAuth2Framework\Bundle\Server\Model\ClientRepository;

final class ClientCredentialsGrantTypeContext implements Context
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
     * @Given An unauthenticated client sends a Client Credentials Grant Type request
     */
    public function anUnauthenticatedClientSendsAClientCredentialsGrantTypeRequest()
    {
        $this->minkContext->getSession()->getDriver()->getClient()->request('POST', 'https://oauth2.test/token/get', [
            'grant_type' => 'client_credentials',
        ],
            [], [
                'HTTP_Content-Type' => 'application/x-www-form-urlencoded',
            ]
        );
    }

    /**
     * @Given An public client sends a Client Credentials Grant Type request
     */
    public function anPublicClientSendsAClientCredentialsGrantTypeRequest()
    {
        $this->minkContext->getSession()->getDriver()->getClient()->request('POST', 'https://oauth2.test/token/get', [
            'grant_type' => 'client_credentials',
            'client_id'  => 'client2',
        ],
            [], [
                'HTTP_Content-Type' => 'application/x-www-form-urlencoded',
            ]
        );
    }

    /**
     * @Given A client sends a valid Client Credentials Grant Type request
     */
    public function aClientSendsAValidClientCredentialsGrantTypeRequest()
    {
        $this->minkContext->getSession()->getDriver()->getClient()->request('POST', 'https://oauth2.test/token/get', [
            'grant_type' => 'client_credentials',
            'scope'      => 'email phone address',
        ],
            [], [
                'HTTP_Content-Type'  => 'application/x-www-form-urlencoded',
                'HTTP_Authorization' => 'Basic '.base64_encode('client1:secret'),
            ]
        );
    }

    /**
     * @Given A client authenticated with a JWT assertion sends a valid Client Credentials Grant Type request
     */
    public function aClientAuthenticatedWithAJwtAssertionSendsAValidClientCredentialsGrantTypeRequest()
    {
        $this->minkContext->getSession()->getDriver()->getClient()->request('POST', 'https://oauth2.test/token/get', [
            'grant_type'            => 'client_credentials',
            'client_assertion_type' => 'urn:ietf:params:oauth:client-assertion-type:jwt-bearer',
            'client_assertion'      => $this->generateValidClientAssertion(),
            'scope'                 => 'email phone address',
        ],
            [], [
                'HTTP_Content-Type' => 'application/x-www-form-urlencoded',
            ]
        );
    }

    /**
     * @Given A client sends a valid Client Credentials Grant Type request but the grant type is not allowed
     */
    public function aClientSendsAValidClientCredentialsGrantTypeRequestButTheGrantTypeIsNotAllowed()
    {
        $this->minkContext->getSession()->getDriver()->getClient()->request('POST', 'https://oauth2.test/token/get', [
            'grant_type'    => 'client_credentials',
            'client_id'     => 'client4',
            'client_secret' => 'secret',
        ],
            [], [
                'HTTP_Content-Type' => 'application/x-www-form-urlencoded',
            ]
        );
    }

    /**
     * @Given A client sends a Client Credentials Grant Type request but credentials expired
     */
    public function aClientSendsAClientCredentialsGrantTypeRequestButCredentialsExpired()
    {
        $this->minkContext->getSession()->getDriver()->getClient()->request('POST', 'https://oauth2.test/token/get', [
            'grant_type'    => 'client_credentials',
            'scope'         => 'email phone address',
            'client_id'     => 'client5',
            'client_secret' => 'secret',
        ],
            [], [
                'HTTP_Content-Type'  => 'application/x-www-form-urlencoded',
            ]
        );
    }

    /**
     * @Given A deleted client sends a Client Credentials Grant Type request
     */
    public function aDeletedClientSendsAClientCredentialsGrantTypeRequest()
    {
        $this->minkContext->getSession()->getDriver()->getClient()->request('POST', 'https://oauth2.test/token/get', [
            'grant_type'    => 'client_credentials',
            'scope'         => 'email phone address',
            'client_id'     => 'client6',
            'client_secret' => 'secret',
        ],
            [], [
                'HTTP_Content-Type'  => 'application/x-www-form-urlencoded',
            ]
        );
    }

    private function generateValidClientAssertion()
    {
        $claims = [
            'iss' => 'client3',
            'sub' => 'client3',
            'aud' => 'My Server',
            'jti' => Base64Url::encode(random_bytes(64)),
            'exp' => (new \DateTimeImmutable('now + 1 day'))->getTimestamp(),
        ];
        $headers = [
            'alg' => 'HS256',
        ];
        $client = $this->getContainer()->get(ClientRepository::class)->find(ClientId::create('client3'));

        return JWSFactory::createJWSToCompactJSON($claims, $client->getPublicKeySet()->getKey(0), $headers);
    }
}
