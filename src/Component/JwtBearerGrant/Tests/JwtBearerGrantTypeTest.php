<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2019 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license. See the LICENSE file for details.
 */

namespace OAuth2Framework\Component\JwtBearerGrant\Tests;

use Jose\Component\Checker\AudienceChecker;
use Jose\Component\Checker\ClaimCheckerManager;
use Jose\Component\Checker\ExpirationTimeChecker;
use Jose\Component\Checker\HeaderCheckerManager;
use Jose\Component\Checker\IssuedAtChecker;
use Jose\Component\Checker\NotBeforeChecker;
use Jose\Component\Core\AlgorithmManager;
use Jose\Component\Core\JWK;
use Jose\Component\Core\JWKSet;
use Jose\Component\Core\Util\JsonConverter;
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
use OAuth2Framework\Component\Core\Message\OAuth2Error;
use OAuth2Framework\Component\Core\TrustedIssuer\TrustedIssuer;
use OAuth2Framework\Component\Core\TrustedIssuer\TrustedIssuerRepository;
use OAuth2Framework\Component\Core\UserAccount\UserAccount;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;
use OAuth2Framework\Component\Core\UserAccount\UserAccountRepository;
use OAuth2Framework\Component\JwtBearerGrant\JwtBearerGrantType;
use OAuth2Framework\Component\TokenEndpoint\GrantTypeData;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;

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
        static::assertEquals([], $this->getGrantType()->associatedResponseTypes());
        static::assertEquals('urn:ietf:params:oauth:grant-type:jwt-bearer', $this->getGrantType()->name());
    }

    /**
     * @test
     */
    public function theRequestHaveMissingParameters()
    {
        $request = $this->buildRequest([]);

        try {
            $this->getGrantType()->checkRequest($request->reveal());
            static::fail('An OAuth2 exception should be thrown.');
        } catch (OAuth2Error $e) {
            static::assertEquals(400, $e->getCode());
            static::assertEquals([
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
        $request = $this->buildRequest(['assertion' => 'FOO']);

        $this->getGrantType()->checkRequest($request->reveal());
        static::assertTrue(true);
    }

    /**
     * @test
     */
    public function theTokenResponseIsCorrectlyPreparedWithAssertionFromClient()
    {
        if (!\class_exists(JWEBuilder::class)) {
            static::markTestSkipped('The component "web-token/jwt-encryption" is not installed.');
        }
        $request = $this->buildRequest(['assertion' => $this->createValidEncryptedAssertionFromClient()]);
        $grantTypeData = new GrantTypeData(null);

        $this->getGrantType()->prepareResponse($request->reveal(), $grantTypeData);
        static::assertTrue($grantTypeData->getMetadata()->has('jwt'));
        static::assertTrue($grantTypeData->getMetadata()->has('claims'));
    }

    /**
     * @test
     */
    public function theTokenResponseIsCorrectlyPreparedWithAssertionFromTrustedIssuer()
    {
        $request = $this->buildRequest(['assertion' => $this->createValidAssertionFromIssuer()]);
        $grantTypeData = new GrantTypeData(null);

        $this->getGrantType()->prepareResponse($request->reveal(), $grantTypeData);
        static::assertTrue($grantTypeData->getMetadata()->has('jwt'));
        static::assertTrue($grantTypeData->getMetadata()->has('claims'));
    }

    /**
     * @test
     */
    public function theAssertionHasBeenIssuedByAnUnknownIssuer()
    {
        $request = $this->buildRequest(['assertion' => $this->createAssertionFromUnknownIssuer()]);
        $grantTypeData = new GrantTypeData(null);

        try {
            $this->getGrantType()->prepareResponse($request->reveal(), $grantTypeData);
        } catch (OAuth2Error $e) {
            static::assertEquals(400, $e->getCode());
            static::assertEquals([
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
        $client = $this->prophesize(Client::class);
        $client->isPublic()->willReturn(false);
        $client->getPublicId()->willReturn(new ClientId('CLIENT_ID'));
        $client->getClientId()->willReturn(new ClientId('CLIENT_ID'));
        $client->getOwnerId()->willReturn(new UserAccountId('USER_ACCOUNT_ID'));

        $request = $this->buildRequest(['assertion' => $this->createValidAssertionFromIssuer()]);
        $request->getAttribute('client')->willReturn($client);
        $grantTypeData = new GrantTypeData($client->reveal());
        $grantTypeData->setResourceOwnerId(new UserAccountId('USER_ACCOUNT_ID'));

        $this->getGrantType()->grant($request->reveal(), $grantTypeData);
        static::assertSame($grantTypeData, $grantTypeData);
        static::assertEquals('USER_ACCOUNT_ID', $grantTypeData->getResourceOwnerId()->getValue());
        static::assertEquals('CLIENT_ID', $grantTypeData->getClient()->getPublicId()->getValue());
    }

    /**
     * @test
     */
    public function theGrantTypeCanGrantTheClientUsingTheTokenIssuedByTheClient()
    {
        if (!\class_exists(JWEBuilder::class)) {
            static::markTestSkipped('The component "web-token/jwt-encryption" is not installed.');
        }
        $client = $this->prophesize(Client::class);
        $client->isPublic()->willReturn(false);
        $client->getPublicId()->willReturn(new ClientId('CLIENT_ID'));
        $client->getClientId()->willReturn(new ClientId('CLIENT_ID'));
        $client->getOwnerId()->willReturn(new UserAccountId('USER_ACCOUNT_ID'));

        $request = $this->buildRequest(['assertion' => $this->createValidEncryptedAssertionFromClient()]);
        $request->getAttribute('client')->willReturn($client);
        $grantTypeData = new GrantTypeData($client->reveal());
        $grantTypeData->setResourceOwnerId(new UserAccountId('CLIENT_ID'));

        $this->getGrantType()->grant($request->reveal(), $grantTypeData);
        static::assertSame($grantTypeData, $grantTypeData);
        static::assertEquals('CLIENT_ID', $grantTypeData->getResourceOwnerId()->getValue());
        static::assertEquals('CLIENT_ID', $grantTypeData->getClient()->getPublicId()->getValue());
    }

    /**
     * @var JwtBearerGrantType|null
     */
    private $grantType;

    private function getGrantType(): JwtBearerGrantType
    {
        if (null === $this->grantType) {
            $this->grantType = new JwtBearerGrantType(
                $this->getJwsVerifier(),
                $this->getHeaderCheckerManager(),
                $this->getClaimCheckerManager(),
                $this->getClientRepository(),
                $this->getUserAccountRepository()
            );

            $this->grantType->enableTrustedIssuerSupport(
                $this->getTrustedIssuerManager()
            );

            if (\class_exists(JWEBuilder::class)) {
                $this->grantType->enableEncryptedAssertions(
                    $this->getJweDecrypter(),
                    $this->getEncryptionKeySet(),
                    false
                );
            }
        }

        return $this->grantType;
    }

    private function getUserAccountRepository(): UserAccountRepository
    {
        $userAccount = $this->prophesize(UserAccount::class);
        $userAccount->getPublicId()->willReturn(new UserAccountId('USER_ACCOUNT_ID'));
        $userAccount->getUserAccountId()->willReturn(new UserAccountId('USER_ACCOUNT_ID'));
        $userAccountRepository = $this->prophesize(UserAccountRepository::class);
        $userAccountRepository->find(Argument::type(UserAccountId::class))->will(function ($args) use ($userAccount) {
            if ('USER_ACCOUNT_ID' === ($args[0])->getValue()) {
                return $userAccount->reveal();
            }

            return;
        });

        return $userAccountRepository->reveal();
    }

    private function getClientRepository(): ClientRepository
    {
        $keyset = $this->getPublicEcKeySet();

        $client = $this->prophesize(Client::class);
        $client->isPublic()->willReturn(false);
        $client->getPublicId()->willReturn(new ClientId('CLIENT_ID'));
        $client->getClientId()->willReturn(new ClientId('CLIENT_ID'));
        $client->has('token_endpoint_auth_method')->willReturn(true);
        $client->get('token_endpoint_auth_method')->willReturn('private_key_jwt');
        $client->getTokenEndpointAuthenticationMethod()->willReturn('private_key_jwt');
        $client->has('jwks')->willReturn(true);
        $client->get('jwks')->willReturn(\Safe\json_encode($keyset));
        $client->isDeleted()->willReturn(false);

        $clientRepository = $this->prophesize(ClientRepository::class);
        $clientRepository->find(Argument::type(ClientId::class))->will(function ($args) use ($keyset, $client) {
            if ('CLIENT_ID' === ($args[0])->getValue()) {
                return $client->reveal();
            }

            return;
        });

        return $clientRepository->reveal();
    }

    private function getJwsVerifier(): JWSVerifier
    {
        return new JWSVerifier(new AlgorithmManager([
            new RS256(),
            new ES256(),
        ]));
    }

    private function getJweDecrypter(): JWEDecrypter
    {
        return new JWEDecrypter(
            new AlgorithmManager([new A256KW()]),
            new AlgorithmManager([new A256GCM()]),
            CompressionMethodManager::create([new Deflate()])
        );
    }

    private function getJwsBuilder(): JWSBuilder
    {
        return new JWSBuilder(
            null,
            new AlgorithmManager([
                new RS256(),
                new ES256(),
            ])
        );
    }

    private function getJweBuilder(): JWEBuilder
    {
        return new JWEBuilder(
            null,
            new AlgorithmManager([new A256KW()]),
            new AlgorithmManager([new A256GCM()]),
            CompressionMethodManager::create([new Deflate()])
        );
    }

    private function getHeaderCheckerManager(): HeaderCheckerManager
    {
        return HeaderCheckerManager::create([], [new JWSTokenSupport()]);
    }

    private function getClaimCheckerManager(): ClaimCheckerManager
    {
        return ClaimCheckerManager::create([
            new AudienceChecker('My OAuth2 Server', true),
            new IssuedAtChecker(),
            new NotBeforeChecker(),
            new ExpirationTimeChecker(),
        ]);
    }

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

    private function createAssertionFromUnknownIssuer(): string
    {
        $claims = [
            'iss' => 'Unknown Issuer',
            'sub' => 'USER_ACCOUNT_ID',
            'aud' => 'My OAuth2 Server',
            'exp' => \time() + 1000,
        ];
        $header = [
            'alg' => 'ES256',
        ];

        $jws = $this->getJwsBuilder()
            ->create()
            ->withPayload(JsonConverter::encode($claims))
            ->addSignature($this->getPrivateEcKey(), $header)
            ->build();
        $serializer = new JwsCompactSerializer();

        return $serializer->serialize($jws, 0);
    }

    private function createValidAssertionFromIssuer(): string
    {
        $claims = [
            'iss' => 'Trusted Issuer #1',
            'sub' => 'USER_ACCOUNT_ID',
            'aud' => 'My OAuth2 Server',
            'exp' => \time() + 1000,
        ];
        $header = [
            'alg' => 'ES256',
        ];

        $jws = $this->getJwsBuilder()
            ->create()
            ->withPayload(JsonConverter::encode($claims))
            ->addSignature($this->getPrivateEcKey(), $header)
            ->build();
        $serializer = new JwsCompactSerializer();

        return $serializer->serialize($jws, 0);
    }

    private function createValidEncryptedAssertionFromClient(): string
    {
        $claims = [
            'iss' => 'CLIENT_ID',
            'sub' => 'CLIENT_ID',
            'aud' => 'My OAuth2 Server',
            'exp' => \time() + 1000,
        ];
        $header = [
            'alg' => 'RS256',
        ];

        $jws = $this->getJwsBuilder()
            ->create()
            ->withPayload(JsonConverter::encode($claims))
            ->addSignature($this->getPrivateRsaKey(), $header)
            ->build();
        $serializer = new JwsCompactSerializer();
        $jwt = $serializer->serialize($jws, 0);

        $jwe = $this->getJweBuilder()
            ->create()
            ->withPayload($jwt)
            ->withSharedProtectedHeader(['alg' => 'A256KW', 'enc' => 'A256GCM'])
            ->addRecipient($this->getEncryptionKey())
            ->build();
        $serializer = new JweCompactSerializer();
        $jwt = $serializer->serialize($jwe, 0);

        return $jwt;
    }

    private function getPublicEcKeySet(): JWKSet
    {
        return JWKSet::createFromJson('{"keys":[{"kty":"EC","crv":"P-256","x":"VlZO9X_B43HFSUK8aeQn88UO2_VfeBtVU1Usl3rYq90","y":"oAHPRNZEUpe-T2-Q_rThJ4lGsNYLXomSYW69RZ9jzNQ"},{"kty":"EC","crv":"P-256","x":"w0qQe7oa_aI3G6irjTbdtMqc4e0vNveQgRoRCyvpIBE","y":"7DyqhillL89iM6fMK216ov1EixmJGda76ugNuE-fsic"}]}');
    }

    private function getPublicRsaKeySet(): JWKSet
    {
        return JWKSet::createFromJson('{"keys":[{"kty":"RSA","n":"sLjaCStJYRr_y7_3GLlDb4bnGJ8XirSdFboYmvA38NXJ6PhIIjr-sFzfwlcpxZxz6zzjXkDFs3AcUOvC3_KRT5tn4XBOHcR6ABrT65dZTe_qalEpYeQG4oxevc01vmD_dD6Ho2O69amT4gscus2pvszFPdraMYybH24aQFztVtc","e":"AQAB"},{"kty":"RSA","n":"um8f5neOmoGMsQ-BJMOgehsSOzQiYOk4W7AJL97q-V_8VojXJKHUqvTqiDeVfcgxPz1kNseIkm4PivKYQ1_Yh1j5RxL30V8Pc3VR7ReLMvEsQUbedkJKqhXy7gOYyc4IrYTux1I2dI5I8r_lvtDtTgWB5UrWfwj9ddVhk22z6jc","e":"AQAB"}]}');
    }

    private function getEncryptionKey(): JWK
    {
        return JWK::createFromJson('{"kty":"oct","k":"bJzb8RaN7TzPz001PeF0lw0ZoUJqbazGxMvBd_xzfms"}');
    }

    private function getEncryptionKeySet(): JWKSet
    {
        return JWKSet::createFromJson('{"keys":[{"kty":"oct","k":"bJzb8RaN7TzPz001PeF0lw0ZoUJqbazGxMvBd_xzfms"},{"kty":"oct","k":"dIx5cdLn-dAgNkvfZSiroJuy5oykHO4hDnYpmwlMq6A"}]}');
    }

    private function getPrivateEcKey(): JWK
    {
        return JWK::createFromJson('{"kty":"EC","crv":"P-256","d":"zudFvuFy_HbN4cZO5kEdN33Zz-VR48YrVV23mCzAwqA","x":"VlZO9X_B43HFSUK8aeQn88UO2_VfeBtVU1Usl3rYq90","y":"oAHPRNZEUpe-T2-Q_rThJ4lGsNYLXomSYW69RZ9jzNQ"}');
    }

    private function getPrivateRsaKey(): JWK
    {
        return JWK::createFromJson('{"kty":"RSA","n":"sLjaCStJYRr_y7_3GLlDb4bnGJ8XirSdFboYmvA38NXJ6PhIIjr-sFzfwlcpxZxz6zzjXkDFs3AcUOvC3_KRT5tn4XBOHcR6ABrT65dZTe_qalEpYeQG4oxevc01vmD_dD6Ho2O69amT4gscus2pvszFPdraMYybH24aQFztVtc","e":"AQAB","d":"By-tJhxNgpZfeoCW4rl95YYd1aF6iphnnt-PapWEINYAvOmDvWiavL86FiQHPdLr38_9CvMlVvOjIyNDLGonwHynPxAzUsT7M891N9D0cSCv9DlV3uqRVtdqF4MtWtpU5JWJ9q6auL1UPx2tJhOygu9tJ7w0bTGFwrUdb8PSnlE","p":"3p-6HWbX9YcSkeksJXW3_Y2cfZgRCUXH2or1dIidmscb4VVtTUwb-8gGzUDEq4iS_5pgLARl3O4lOHK0n6Qbrw","q":"yzdrGWwgaWqK6e9VFv3NXGeq1TEKHLkXjF7J24XWKm9lSmlssPRv0NwMPVp_CJ39BrLfFtpFr_fh0oG1sVZ5WQ","dp":"UQ6rP0VQ4G77zfCuSD1ibol_LyONIGkt6V6rHHEZoV9ZwWPPVlOd5MDh6R3p_eLOUw6scZpwVE7JcpIhPfcMtQ","dq":"Jg8g_cfkYhnUHm_2bbHm7jF0Ky1eCXcY0-9Eutpb--KVA9SuyI1fC6zKlgsG06RTKRgC9BK5DnXMU1J7ptTdMQ","qi":"17kC87NLUV6z-c-wtmbNqAkDbKmwpb2RMsGUQmhEPJwnWuwEKZpSQz776SUVwoc0xiQ8DpvU_FypflIlm6fq9w"}');
    }

    private function buildRequest(array $data): ObjectProphecy
    {
        $body = $this->prophesize(StreamInterface::class);
        $body->getContents()->willReturn(\http_build_query($data));
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->hasHeader('Content-Type')->willReturn(true);
        $request->getHeader('Content-Type')->willReturn(['application/x-www-form-urlencoded']);
        $request->getBody()->willReturn($body->reveal());
        $request->getParsedBody()->willReturn([]);

        return $request;
    }
}
