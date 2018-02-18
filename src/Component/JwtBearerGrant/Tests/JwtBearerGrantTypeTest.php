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

namespace OAuth2Framework\Component\JwtBearerGrant\Tests;

use Jose\Component\Checker\AudienceChecker;
use Jose\Component\Checker\ClaimCheckerManager;
use Jose\Component\Checker\ExpirationTimeChecker;
use Jose\Component\Checker\HeaderCheckerManager;
use Jose\Component\Checker\IssuedAtChecker;
use Jose\Component\Checker\NotBeforeChecker;
use Jose\Component\Core\AlgorithmManager;
use Jose\Component\Core\Converter\StandardConverter;
use Jose\Component\Core\JWK;
use Jose\Component\Core\JWKSet;
use Jose\Component\Encryption\Algorithm\ContentEncryption\A256GCM;
use Jose\Component\Encryption\Algorithm\KeyEncryption\A256KW;
use Jose\Component\Encryption\Compression\CompressionMethodManager;
use Jose\Component\Encryption\Compression\Deflate;
use Jose\Component\Encryption\JWEBuilder;
use Jose\Component\Encryption\JWEDecrypter;
use Jose\Component\Encryption\Serializer\CompactSerializer as JweCompactSerializer;
use Jose\Component\Signature\Algorithm\ES256;
use Jose\Component\Signature\Algorithm\RS256;
use Jose\Component\Signature\JWSBuilder;
use Jose\Component\Signature\JWSTokenSupport;
use Jose\Component\Signature\JWSVerifier;
use Jose\Component\Signature\Serializer\CompactSerializer as JwsCompactSerializer;
use OAuth2Framework\Component\Core\Client\Client;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\Client\ClientRepository;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\Exception\OAuth2Exception;
use OAuth2Framework\Component\Core\UserAccount\UserAccount;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;
use OAuth2Framework\Component\Core\UserAccount\UserAccountRepository;
use OAuth2Framework\Component\JwtBearerGrant\JwtBearerGrantType;
use OAuth2Framework\Component\TrustedIssuer\TrustedIssuer;
use OAuth2Framework\Component\TrustedIssuer\TrustedIssuerRepository;
use OAuth2Framework\Component\TokenEndpoint\GrantTypeData;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @group GrantType
 * @group JwtBearer
 */
class JwtBearerGrantTypeTest extends TestCase
{
    /**
     * @test
     */
    public function genericInformation()
    {
        self::assertEquals([], $this->getGrantType()->associatedResponseTypes());
        self::assertEquals('urn:ietf:params:oauth:grant-type:jwt-bearer', $this->getGrantType()->name());
    }

    /**
     * @test
     */
    public function theRequestHaveMissingParameters()
    {
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getParsedBody()->willReturn([]);

        try {
            $this->getGrantType()->checkRequest($request->reveal());
            $this->fail('An OAuth2 exception should be thrown.');
        } catch (OAuth2Exception $e) {
            self::assertEquals(400, $e->getCode());
            self::assertEquals([
                'error' => 'invalid_request',
                'error_description' => 'Missing grant type parameter(s): assertion.',
            ], $e->getData());
        }
    }

    /**
     * @test
     */
    public function theRequestHaveAllRequiredParameters()
    {
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getParsedBody()->willReturn(['assertion' => 'FOO']);

        $this->getGrantType()->checkRequest($request->reveal());
        self::assertTrue(true);
    }

    /**
     * @test
     */
    public function theTokenResponseIsCorrectlyPreparedWithAssertionFromClient()
    {
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getParsedBody()->willReturn(['assertion' => $this->createValidEncryptedAssertionFromClient()]);
        $grantTypeData = GrantTypeData::create(null);

        $receivedGrantTypeData = $this->getGrantType()->prepareResponse($request->reveal(), $grantTypeData);
        self::assertNotSame($receivedGrantTypeData, $grantTypeData);
        self::assertTrue($receivedGrantTypeData->hasMetadata('jwt'));
        self::assertTrue($receivedGrantTypeData->hasMetadata('claims'));
    }

    /**
     * @test
     */
    public function theTokenResponseIsCorrectlyPreparedWithAssertionFromTrustedIssuer()
    {
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getParsedBody()->willReturn(['assertion' => $this->createValidAssertionFromIssuer()]);
        $grantTypeData = GrantTypeData::create(null);

        $receivedGrantTypeData = $this->getGrantType()->prepareResponse($request->reveal(), $grantTypeData);
        self::assertNotSame($receivedGrantTypeData, $grantTypeData);
        self::assertTrue($receivedGrantTypeData->hasMetadata('jwt'));
        self::assertTrue($receivedGrantTypeData->hasMetadata('claims'));
    }

    /**
     * @test
     */
    public function theAssertionHasBeenIssuedByAnUnknownIssuer()
    {
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getParsedBody()->willReturn(['assertion' => $this->createAssertionFromUnknownIssuer()]);
        $grantTypeData = GrantTypeData::create(null);

        try {
            $this->getGrantType()->prepareResponse($request->reveal(), $grantTypeData);
        } catch (OAuth2Exception $e) {
            self::assertEquals(400, $e->getCode());
            self::assertEquals([
                'error' => 'invalid_request',
                'error_description' => 'Unable to find the issuer of the assertion.',
            ], $e->getData());
        }
    }

    /**
     * @test
     */
    public function theGrantTypeCanGrantTheClientUsingTheTokenIssuedByATrustedIssuer()
    {
        $client = Client::createEmpty();
        $client = $client->create(
            ClientId::create('CLIENT_ID'),
            DataBag::create([]),
            UserAccountId::create('USER_ACCOUNT_ID')
        );
        $client->eraseMessages();
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getParsedBody()->willReturn(['assertion' => $this->createValidAssertionFromIssuer()]);
        $request->getAttribute('client')->willReturn($client);
        $grantTypeData = GrantTypeData::create($client);
        $grantTypeData = $grantTypeData->withResourceOwnerId(UserAccountId::create('USER_ACCOUNT_ID'));

        $receivedGrantTypeData = $this->getGrantType()->grant($request->reveal(), $grantTypeData);
        self::assertSame($receivedGrantTypeData, $grantTypeData);
        self::assertEquals('USER_ACCOUNT_ID', $receivedGrantTypeData->getResourceOwnerId()->getValue());
        self::assertEquals('CLIENT_ID', $receivedGrantTypeData->getClient()->getPublicId()->getValue());
    }

    /**
     * @test
     */
    public function theGrantTypeCanGrantTheClientUsingTheTokenIssuedByTheClient()
    {
        $client = Client::createEmpty();
        $client = $client->create(
            ClientId::create('CLIENT_ID'),
            DataBag::create([]),
            UserAccountId::create('USER_ACCOUNT_ID')
        );
        $client->eraseMessages();
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getParsedBody()->willReturn(['assertion' => $this->createValidEncryptedAssertionFromClient()]);
        $request->getAttribute('client')->willReturn($client);
        $grantTypeData = GrantTypeData::create($client);
        $grantTypeData = $grantTypeData->withResourceOwnerId(UserAccountId::create('CLIENT_ID'));

        $receivedGrantTypeData = $this->getGrantType()->grant($request->reveal(), $grantTypeData);
        self::assertSame($receivedGrantTypeData, $grantTypeData);
        self::assertEquals('CLIENT_ID', $receivedGrantTypeData->getResourceOwnerId()->getValue());
        self::assertEquals('CLIENT_ID', $receivedGrantTypeData->getClient()->getPublicId()->getValue());
    }

    /**
     * @var JwtBearerGrantType|null
     */
    private $grantType = null;

    private function getGrantType(): JwtBearerGrantType
    {
        if (null === $this->grantType) {
            $this->grantType = new JwtBearerGrantType(
                new StandardConverter(),
                $this->getJwsVerifier(),
                $this->getHeaderCheckerManager(),
                $this->getClaimCheckerManager(),
                $this->getClientRepository(),
                $this->getUserAccountRepository()
            );

            $this->grantType->enableTrustedIssuerSupport(
                $this->getTrustedIssuerManager()
            );

            $this->grantType->enableEncryptedAssertions(
                $this->getJweDecrypter(),
                $this->getEncryptionKeySet(),
                false
            );
        }

        return $this->grantType;
    }

    /**
     * @return UserAccountRepository
     */
    private function getUserAccountRepository(): UserAccountRepository
    {
        $userAccount = $this->prophesize(UserAccount::class);
        $userAccount->getPublicId()->willReturn(UserAccountId::create('USER_ACCOUNT_ID'));
        $userAccountRepository = $this->prophesize(UserAccountRepository::class);
        $userAccountRepository->find(Argument::type(UserAccountId::class))->will(function ($args) use ($userAccount) {
            if ('USER_ACCOUNT_ID' === ($args[0])->getValue()) {
                return $userAccount->reveal();
            }

            return null;
        });

        return $userAccountRepository->reveal();
    }

    /**
     * @return ClientRepository
     */
    private function getClientRepository(): ClientRepository
    {
        $keyset = $this->getPublicEcKeySet();
        $clientRepository = $this->prophesize(ClientRepository::class);
        $clientRepository->find(Argument::type(ClientId::class))->will(function ($args) use ($keyset) {
            if ('CLIENT_ID' === ($args[0])->getValue()) {
                $client = Client::createEmpty();
                $client = $client->create(
                    ClientId::create('CLIENT_ID'),
                    DataBag::create([
                        'jwks' => json_encode($keyset),
                        'token_endpoint_auth_method' => 'private_key_jwt',
                    ]),
                    UserAccountId::create('USER_ACCOUNT_ID')
                );
                $client->eraseMessages();

                return $client;
            }

            return null;
        });

        return $clientRepository->reveal();
    }

    /**
     * @return JWSVerifier
     */
    private function getJwsVerifier(): JWSVerifier
    {
        return new JWSVerifier(AlgorithmManager::create([
            new RS256(),
            new ES256(),
        ]));
    }

    /**
     * @return JWEDecrypter
     */
    private function getJweDecrypter(): JWEDecrypter
    {
        return new JWEDecrypter(
            AlgorithmManager::create([new A256KW()]),
            AlgorithmManager::create([new A256GCM()]),
            CompressionMethodManager::create([new Deflate()])
        );
    }

    /**
     * @return JWSBuilder
     */
    private function getJwsBuilder(): JWSBuilder
    {
        return new JWSBuilder(
            new StandardConverter(),
            AlgorithmManager::create([
                new RS256(),
                new ES256(),
            ])
        );
    }

    /**
     * @return JWEBuilder
     */
    private function getJweBuilder(): JWEBuilder
    {
        return new JWEBuilder(
            new StandardConverter(),
            AlgorithmManager::create([new A256KW()]),
            AlgorithmManager::create([new A256GCM()]),
            CompressionMethodManager::create([new Deflate()])
        );
    }

    /**
     * @return HeaderCheckerManager
     */
    private function getHeaderCheckerManager(): HeaderCheckerManager
    {
        return HeaderCheckerManager::create([], [new JWSTokenSupport()]);
    }

    /**
     * @return ClaimCheckerManager
     */
    private function getClaimCheckerManager(): ClaimCheckerManager
    {
        return ClaimCheckerManager::create([
            new AudienceChecker('My OAuth2 Server', true),
            new IssuedAtChecker(),
            new NotBeforeChecker(),
            new ExpirationTimeChecker(),
        ]);
    }

    /**
     * @return TrustedIssuerRepository
     */
    private function getTrustedIssuerManager(): TrustedIssuerRepository
    {
        $issuer = $this->prophesize(TrustedIssuer::class);
        $issuer->name()->willreturn('Trusted Issuer #1');
        $issuer->getAllowedAssertiontypes()->willReturn([]);
        $issuer->getAllowedSignatureAlgorithms()->willReturn(['ES256']);
        $issuer->getJWKSet()->willReturn($this->getPublicEcKeySet());

        $manager = $this->prophesize(TrustedIssuerRepository::class);
        $manager->find('Unknown Issuer')->willReturn(null);
        $manager->find('Trusted Issuer #1')->willReturn($issuer->reveal());

        return $manager->reveal();
    }

    /**
     * @return string
     */
    private function createExpiredAssertion(): string
    {
    }

    /**
     * @return string
     */
    private function createAssertionFromUnknownIssuer(): string
    {
        $jsonConverter = new StandardConverter();
        $claims = [
            'iss' => 'Unknown Issuer',
            'sub' => 'USER_ACCOUNT_ID',
            'aud' => 'My OAuth2 Server',
            'exp' => time() + 1000,
        ];
        $header = [
            'alg' => 'ES256',
        ];

        $jws = $this->getJwsBuilder()
            ->create()
            ->withPayload($jsonConverter->encode($claims))
            ->addSignature($this->getPrivateEcKey(), $header)
            ->build();
        $serializer = new JwsCompactSerializer($jsonConverter);

        return $serializer->serialize($jws, 0);
    }

    /**
     * @return string
     */
    private function createValidAssertionFromIssuer(): string
    {
        $jsonConverter = new StandardConverter();
        $claims = [
            'iss' => 'Trusted Issuer #1',
            'sub' => 'USER_ACCOUNT_ID',
            'aud' => 'My OAuth2 Server',
            'exp' => time() + 1000,
        ];
        $header = [
            'alg' => 'ES256',
        ];

        $jws = $this->getJwsBuilder()
            ->create()
            ->withPayload($jsonConverter->encode($claims))
            ->addSignature($this->getPrivateEcKey(), $header)
            ->build();
        $serializer = new JwsCompactSerializer($jsonConverter);

        return $serializer->serialize($jws, 0);
    }

    /**
     * @return string
     */
    private function createValidEncryptedAssertionFromClient(): string
    {
        $jsonConverter = new StandardConverter();
        $claims = [
            'iss' => 'CLIENT_ID',
            'sub' => 'CLIENT_ID',
            'aud' => 'My OAuth2 Server',
            'exp' => time() + 1000,
        ];
        $header = [
            'alg' => 'RS256',
        ];

        $jws = $this->getJwsBuilder()
            ->create()
            ->withPayload($jsonConverter->encode($claims))
            ->addSignature($this->getPrivateRsaKey(), $header)
            ->build();
        $serializer = new JwsCompactSerializer($jsonConverter);
        $jwt = $serializer->serialize($jws, 0);

        $jwe = $this->getJweBuilder()
            ->create()
            ->withPayload($jwt)
            ->withSharedProtectedHeader(['alg' => 'A256KW', 'enc' => 'A256GCM'])
            ->addRecipient($this->getEncryptionKey())
            ->build();
        $serializer = new JweCompactSerializer($jsonConverter);
        $jwt = $serializer->serialize($jwe, 0);

        return $jwt;
    }

    /**
     * @return JWKSet
     */
    private function getPublicEcKeySet(): JWKSet
    {
        return JWKSet::createFromJson('{"keys":[{"kty":"EC","crv":"P-256","x":"VlZO9X_B43HFSUK8aeQn88UO2_VfeBtVU1Usl3rYq90","y":"oAHPRNZEUpe-T2-Q_rThJ4lGsNYLXomSYW69RZ9jzNQ"},{"kty":"EC","crv":"P-256","x":"w0qQe7oa_aI3G6irjTbdtMqc4e0vNveQgRoRCyvpIBE","y":"7DyqhillL89iM6fMK216ov1EixmJGda76ugNuE-fsic"}]}');
    }

    /**
     * @return JWKSet
     */
    private function getPublicRsaKeySet(): JWKSet
    {
        return JWKSet::createFromJson('{"keys":[{"kty":"RSA","n":"sLjaCStJYRr_y7_3GLlDb4bnGJ8XirSdFboYmvA38NXJ6PhIIjr-sFzfwlcpxZxz6zzjXkDFs3AcUOvC3_KRT5tn4XBOHcR6ABrT65dZTe_qalEpYeQG4oxevc01vmD_dD6Ho2O69amT4gscus2pvszFPdraMYybH24aQFztVtc","e":"AQAB"},{"kty":"RSA","n":"um8f5neOmoGMsQ-BJMOgehsSOzQiYOk4W7AJL97q-V_8VojXJKHUqvTqiDeVfcgxPz1kNseIkm4PivKYQ1_Yh1j5RxL30V8Pc3VR7ReLMvEsQUbedkJKqhXy7gOYyc4IrYTux1I2dI5I8r_lvtDtTgWB5UrWfwj9ddVhk22z6jc","e":"AQAB"}]}');
    }

    /**
     * @return JWK
     */
    private function getEncryptionKey(): JWK
    {
        return JWK::createFromJson('{"kty":"oct","k":"bJzb8RaN7TzPz001PeF0lw0ZoUJqbazGxMvBd_xzfms"}');
    }

    /**
     * @return JWKSet
     */
    private function getEncryptionKeySet(): JWKSet
    {
        return JWKSet::createFromJson('{"keys":[{"kty":"oct","k":"bJzb8RaN7TzPz001PeF0lw0ZoUJqbazGxMvBd_xzfms"},{"kty":"oct","k":"dIx5cdLn-dAgNkvfZSiroJuy5oykHO4hDnYpmwlMq6A"}]}');
    }

    /**
     * @return JWK
     */
    private function getPrivateEcKey(): JWK
    {
        return JWK::createFromJson('{"kty":"EC","crv":"P-256","d":"zudFvuFy_HbN4cZO5kEdN33Zz-VR48YrVV23mCzAwqA","x":"VlZO9X_B43HFSUK8aeQn88UO2_VfeBtVU1Usl3rYq90","y":"oAHPRNZEUpe-T2-Q_rThJ4lGsNYLXomSYW69RZ9jzNQ"}');
    }

    /**
     * @return JWK
     */
    private function getPrivateRsaKey(): JWK
    {
        return JWK::createFromJson('{"kty":"RSA","n":"sLjaCStJYRr_y7_3GLlDb4bnGJ8XirSdFboYmvA38NXJ6PhIIjr-sFzfwlcpxZxz6zzjXkDFs3AcUOvC3_KRT5tn4XBOHcR6ABrT65dZTe_qalEpYeQG4oxevc01vmD_dD6Ho2O69amT4gscus2pvszFPdraMYybH24aQFztVtc","e":"AQAB","d":"By-tJhxNgpZfeoCW4rl95YYd1aF6iphnnt-PapWEINYAvOmDvWiavL86FiQHPdLr38_9CvMlVvOjIyNDLGonwHynPxAzUsT7M891N9D0cSCv9DlV3uqRVtdqF4MtWtpU5JWJ9q6auL1UPx2tJhOygu9tJ7w0bTGFwrUdb8PSnlE","p":"3p-6HWbX9YcSkeksJXW3_Y2cfZgRCUXH2or1dIidmscb4VVtTUwb-8gGzUDEq4iS_5pgLARl3O4lOHK0n6Qbrw","q":"yzdrGWwgaWqK6e9VFv3NXGeq1TEKHLkXjF7J24XWKm9lSmlssPRv0NwMPVp_CJ39BrLfFtpFr_fh0oG1sVZ5WQ","dp":"UQ6rP0VQ4G77zfCuSD1ibol_LyONIGkt6V6rHHEZoV9ZwWPPVlOd5MDh6R3p_eLOUw6scZpwVE7JcpIhPfcMtQ","dq":"Jg8g_cfkYhnUHm_2bbHm7jF0Ky1eCXcY0-9Eutpb--KVA9SuyI1fC6zKlgsG06RTKRgC9BK5DnXMU1J7ptTdMQ","qi":"17kC87NLUV6z-c-wtmbNqAkDbKmwpb2RMsGUQmhEPJwnWuwEKZpSQz776SUVwoc0xiQ8DpvU_FypflIlm6fq9w"}');
    }
}
