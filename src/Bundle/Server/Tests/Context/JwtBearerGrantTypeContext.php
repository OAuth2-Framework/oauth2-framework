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
use Behat\Behat\Tester\Exception\PendingException;
use Behat\MinkExtension\Context\MinkContext;
use Behat\Symfony2Extension\Context\KernelDictionary;
use Jose\Component\Core\AlgorithmManager;
use Jose\Component\Core\Converter\StandardJsonConverter;
use Jose\Component\Core\JWK;
use Jose\Component\Signature\Algorithm\HS256;
use Jose\Component\Signature\Algorithm\RS256;
use Jose\Component\Signature\JWSBuilder;
use Jose\Component\Signature\Serializer\CompactSerializer;
use OAuth2Framework\Bundle\Server\Model\ClientRepository;
use OAuth2Framework\Component\Server\Model\Client\ClientId;

final class JwtBearerGrantTypeContext implements Context
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
     * @Given An client sends a JWT Bearer Grant Type request without assertion
     */
    public function anClientSendsAJwtBearerGrantTypeRequestWithoutAssertion()
    {
        $this->minkContext->getSession()->getDriver()->getClient()->request('POST', 'https://oauth2.test/token/get', [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
        ],
            [], [
                'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
                'HTTP_Authorization' => 'Basic '.base64_encode('client1:secret'),
            ]
        );
    }

    /**
     * @Given A client sends a valid JWT Bearer Grant Type request
     */
    public function aClientSendsAValidJwtBearerGrantTypeRequest()
    {
        $this->minkContext->getSession()->getDriver()->getClient()->request('POST', 'https://oauth2.test/token/get', [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $this->generateValidAssertion(),
        ],
            [], [
                'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
            ]
        );
    }

    /**
     * @Given A client sends a JWT Bearer Grant Type request without assertion
     */
    public function aClientSendsAJwtBearerGrantTypeRequestWithoutAssertion()
    {
        $this->minkContext->getSession()->getDriver()->getClient()->request('POST', 'https://oauth2.test/token/get', [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
        ],
            [], [
                'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
            ]
        );
    }

    /**
     * @Given A client sends a valid JWT Bearer Grant Type request with an assertion issued from a trusted issuer
     */
    public function aClientSendsAValidJwtBearerGrantTypeRequestWithAnAssertionIssuedFromATrustedIssuer()
    {
        throw new PendingException();
        $this->minkContext->getSession()->getDriver()->getClient()->request('POST', 'https://oauth2.test/token/get', [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $this->generateValidAssertionFromTrustedIssuer(),
        ],
            [], [
                'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
                'HTTP_Authorization' => 'Basic '.base64_encode('client1:secret'),
            ]
        );
    }

    /**
     * @Given A client sends a valid JWT Bearer Grant Type request but the grant type is not allowed
     */
    public function aClientSendsAValidJwtBearerGrantTypeRequestButTheGrantTypeIsNotAllowed()
    {
        $this->minkContext->getSession()->getDriver()->getClient()->request('POST', 'https://oauth2.test/token/get', [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $this->generateValidAssertionButClientNotAllowed(),
        ],
            [], [
                'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
            ]
        );
    }

    /**
     * @Given A client sends a valid JWT Bearer Grant Type request but the client authentication mismatched
     */
    public function aClientSendsAValidJwtBearerGrantTypeRequestButTheClientAuthenticationMismatched()
    {
        $this->minkContext->getSession()->getDriver()->getClient()->request('POST', 'https://oauth2.test/token/get', [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $this->generateValidAssertion(),
            'client_assertion_type' => 'urn:ietf:params:oauth:client-assertion-type:jwt-bearer',
            'client_assertion' => $this->generateValidClientAssertion(),
        ],
            [], [
                'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
            ]
        );
    }

    private function generateValidAssertion()
    {
        $claims = [
            'iss' => 'client1',
            'sub' => 'client1',
            'aud' => 'My Server',
            'jti' => Base64Url::encode(random_bytes(64)),
            'exp' => (new \DateTimeImmutable('now + 1 day'))->getTimestamp(),
        ];
        $headers = [
            'alg' => 'HS256',
        ];
        $client = $this->getContainer()->get(ClientRepository::class)->find(ClientId::create('client1'));

        $jwsBuilder = new JWSBuilder(
            new StandardJsonConverter(),
            AlgorithmManager::create([new HS256()])
        );
        $serializer = new CompactSerializer();
        $jws = $jwsBuilder
            ->create()
            ->withPayload($claims)
            ->addSignature($client->getPublicKeySet()->get(0), $headers)
            ->build();

        return $serializer->serialize($jws, 0);
    }

    private function generateValidAssertionButClientNotAllowed()
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

        $jwsBuilder = new JWSBuilder(
            new StandardJsonConverter(),
            AlgorithmManager::create([new HS256()])
        );
        $serializer = new CompactSerializer();
        $jws = $jwsBuilder
            ->create()
            ->withPayload($claims)
            ->addSignature($client->getPublicKeySet()->get(0), $headers)
            ->build();

        return $serializer->serialize($jws, 0);
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

        $jwsBuilder = new JWSBuilder(
            new StandardJsonConverter(),
            AlgorithmManager::create([new HS256()])
        );
        $serializer = new CompactSerializer();
        $jws = $jwsBuilder
            ->create()
            ->withPayload($claims)
            ->addSignature($client->getPublicKeySet()->get(0), $headers)
            ->build();

        return $serializer->serialize($jws, 0);
    }

    private function generateValidAssertionFromTrustedIssuer()
    {
        $claims = [
            'iss' => 'https://my.trusted.issuer',
            'sub' => 'client1',
            'aud' => 'My Server',
            'jti' => Base64Url::encode(random_bytes(64)),
            'exp' => (new \DateTimeImmutable('now + 1 day'))->getTimestamp(),
        ];
        $headers = [
            'alg' => 'RS256',
        ];
        $key = JWK::create([
            'kty' => 'RSA',
            'kid' => 'bilbo.baggins@hobbiton.example',
            'use' => 'sig',
            'n' => 'n4EPtAOCc9AlkeQHPzHStgAbgs7bTZLwUBZdR8_KuKPEHLd4rHVTeT-O-XV2jRojdNhxJWTDvNd7nqQ0VEiZQHz_AJmSCpMaJMRBSFKrKb2wqVwGU_NsYOYL-QtiWN2lbzcEe6XC0dApr5ydQLrHqkHHig3RBordaZ6Aj-oBHqFEHYpPe7Tpe-OfVfHd1E6cS6M1FZcD1NNLYD5lFHpPI9bTwJlsde3uhGqC0ZCuEHg8lhzwOHrtIQbS0FVbb9k3-tVTU4fg_3L_vniUFAKwuCLqKnS2BYwdq_mzSnbLY7h_qixoR7jig3__kRhuaxwUkRz5iaiQkqgc5gHdrNP5zw',
            'e' => 'AQAB',
            'd' => 'bWUC9B-EFRIo8kpGfh0ZuyGPvMNKvYWNtB_ikiH9k20eT-O1q_I78eiZkpXxXQ0UTEs2LsNRS-8uJbvQ-A1irkwMSMkK1J3XTGgdrhCku9gRldY7sNA_AKZGh-Q661_42rINLRCe8W-nZ34ui_qOfkLnK9QWDDqpaIsA-bMwWWSDFu2MUBYwkHTMEzLYGqOe04noqeq1hExBTHBOBdkMXiuFhUq1BU6l-DqEiWxqg82sXt2h-LMnT3046AOYJoRioz75tSUQfGCshWTBnP5uDjd18kKhyv07lhfSJdrPdM5Plyl21hsFf4L_mHCuoFau7gdsPfHPxxjVOcOpBrQzwQ',
            'p' => '3Slxg_DwTXJcb6095RoXygQCAZ5RnAvZlno1yhHtnUex_fp7AZ_9nRaO7HX_-SFfGQeutao2TDjDAWU4Vupk8rw9JR0AzZ0N2fvuIAmr_WCsmGpeNqQnev1T7IyEsnh8UMt-n5CafhkikzhEsrmndH6LxOrvRJlsPp6Zv8bUq0k',
            'q' => 'uKE2dh-cTf6ERF4k4e_jy78GfPYUIaUyoSSJuBzp3Cubk3OCqs6grT8bR_cu0Dm1MZwWmtdqDyI95HrUeq3MP15vMMON8lHTeZu2lmKvwqW7anV5UzhM1iZ7z4yMkuUwFWoBvyY898EXvRD-hdqRxHlSqAZ192zB3pVFJ0s7pFc',
            'dp' => 'B8PVvXkvJrj2L-GYQ7v3y9r6Kw5g9SahXBwsWUzp19TVlgI-YV85q1NIb1rxQtD-IsXXR3-TanevuRPRt5OBOdiMGQp8pbt26gljYfKU_E9xn-RULHz0-ed9E9gXLKD4VGngpz-PfQ_q29pk5xWHoJp009Qf1HvChixRX59ehik',
            'dq' => 'CLDmDGduhylc9o7r84rEUVn7pzQ6PF83Y-iBZx5NT-TpnOZKF1pErAMVeKzFEl41DlHHqqBLSM0W1sOFbwTxYWZDm6sI6og5iTbwQGIC3gnJKbi_7k_vJgGHwHxgPaX2PnvP-zyEkDERuf-ry4c_Z11Cq9AqC2yeL6kdKT1cYF8',
            'qi' => '3PiqvXQN0zwMeE-sBvZgi289XP9XCQF3VWqPzMKnIgQp7_Tugo6-NZBKCQsMf3HaEGBjTVJs_jcK8-TRXvaKe-7ZMaQj8VfBdYkssbu0NKDDhjJ-GtiseaDVWt7dcH0cfwxgFUHpQh7FoCrjFJ6h6ZEpMF6xmujs4qMpPz8aaI4',
        ]);

        $jwsBuilder = new JWSBuilder(
            new StandardJsonConverter(),
            AlgorithmManager::create([new RS256()])
        );
        $serializer = new CompactSerializer();
        $jws = $jwsBuilder
            ->create()
            ->withPayload($claims)
            ->addSignature($key, $headers)
            ->build();

        return $serializer->serialize($jws, 0);
    }
}
