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

namespace OAuth2Framework\Component\Server\JwtBearerGrant\Tests;

use Jose\Component\Checker\AudienceChecker;
use Jose\Component\Checker\ClaimCheckerManager;
use Jose\Component\Checker\ExpirationTimeChecker;
use Jose\Component\Checker\IssuedAtChecker;
use Jose\Component\Checker\NotBeforeChecker;
use Jose\Component\Core\AlgorithmManager;
use Jose\Component\Core\Converter\StandardConverter;
use Jose\Component\Core\JWK;
use Jose\Component\Core\JWKSet;
use Jose\Component\Signature\Algorithm\ES256;
use Jose\Component\Signature\Algorithm\RS256;
use Jose\Component\Signature\JWSBuilder;
use Jose\Component\Signature\JWSVerifier;
use Jose\Component\Signature\Serializer\CompactSerializer;
use Jose\Component\Signature\Serializer\JWSSerializerManager;
use OAuth2Framework\Component\Server\Core\Client\Client;
use OAuth2Framework\Component\Server\Core\Client\ClientId;
use OAuth2Framework\Component\Server\Core\Client\ClientRepository;
use OAuth2Framework\Component\Server\Core\DataBag\DataBag;
use OAuth2Framework\Component\Server\Core\Response\OAuth2Exception;
use OAuth2Framework\Component\Server\Core\UserAccount\UserAccount;
use OAuth2Framework\Component\Server\Core\UserAccount\UserAccountId;
use OAuth2Framework\Component\Server\Core\UserAccount\UserAccountRepository;
use OAuth2Framework\Component\Server\JwtBearerGrant\JwtBearerGrantType;
use OAuth2Framework\Component\Server\JwtBearerGrant\TrustedIssuer;
use OAuth2Framework\Component\Server\JwtBearerGrant\TrustedIssuerManager;
use OAuth2Framework\Component\Server\TokenEndpoint\GrantTypeData;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @group GrantType
 * @group JwtBearer
 */
final class JwtBearerGrantTypeTest extends TestCase
{
    /**
     * @test
     */
    public function genericInformation()
    {
        self::assertEquals([], $this->getGrantType()->getAssociatedResponseTypes());
        self::assertEquals('urn:ietf:params:oauth:grant-type:jwt-bearer', $this->getGrantType()->getGrantType());
    }

    /**
     * @test
     */
    public function theRequestHaveMissingParameters()
    {
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getParsedBody()->willReturn([]);

        try {
            $this->getGrantType()->checkTokenRequest($request->reveal());
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

        $this->getGrantType()->checkTokenRequest($request->reveal());
        self::assertTrue(true);
    }

    /**
     * @test
     */
    public function theTokenResponseIsCorrectlyPreparedWithAssertionFromClient()
    {
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getParsedBody()->willReturn(['assertion' => $this->createValidAssertionFromClient()]);
        $grantTypeData = GrantTypeData::create(null);

        $receivedGrantTypeData = $this->getGrantType()->prepareTokenResponse($request->reveal(), $grantTypeData);
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

        $receivedGrantTypeData = $this->getGrantType()->prepareTokenResponse($request->reveal(), $grantTypeData);
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
            $this->getGrantType()->prepareTokenResponse($request->reveal(), $grantTypeData);
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
    public function theGrantTypeCanGrantTheClient()
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
     * @var JwtBearerGrantType|null
     */
    private $grantType = null;

    private function getGrantType(): JwtBearerGrantType
    {
        if (null === $this->grantType) {
            $jwsSerializerManager = JWSSerializerManager::create([new CompactSerializer(new StandardConverter())]);

            $this->grantType = new JwtBearerGrantType(
                $this->getTrustedIssuerManager(),
                $jwsSerializerManager,
                $this->getJwsVerifier(),
                $this->getClaimCheckerManager(),
                $this->getClientRepository(),
                $this->getUserAccountRepository()
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
     * @return TrustedIssuerManager
     */
    private function getTrustedIssuerManager(): TrustedIssuerManager
    {
        $manager = new TrustedIssuerManager();
        $issuer = $this->prophesize(TrustedIssuer::class);
        $issuer->name()->willreturn('Trusted Issuer #1');
        $issuer->getAllowedSignatureAlgorithms()->willReturn(['ES256']);
        $issuer->getSignatureKeys()->willReturn($this->getPublicEcKeySet());

        $manager->add($issuer->reveal());

        return $manager;
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
        $serializer = new CompactSerializer($jsonConverter);

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
        $serializer = new CompactSerializer($jsonConverter);

        return $serializer->serialize($jws, 0);
    }

    /**
     * @return string
     */
    private function createValidAssertionFromClient(): string
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
        $serializer = new CompactSerializer($jsonConverter);

        return $serializer->serialize($jws, 0);
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
