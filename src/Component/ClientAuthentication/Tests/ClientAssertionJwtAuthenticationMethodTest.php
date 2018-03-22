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

namespace OAuth2Framework\Component\ClientAuthentication\Tests;

use Http\Message\MessageFactory\DiactorosMessageFactory;
use Jose\Component\Checker\ClaimCheckerManager;
use Jose\Component\Checker\HeaderCheckerManager;
use Jose\Component\Core\AlgorithmManager;
use Jose\Component\Core\Converter\StandardConverter;
use Jose\Component\Core\JWK;
use Jose\Component\KeyManagement\JKUFactory;
use Jose\Component\Signature\Algorithm\HS256;
use Jose\Component\Signature\Algorithm\RS256;
use Jose\Component\Signature\JWSBuilder;
use Jose\Component\Signature\JWSTokenSupport;
use Jose\Component\Signature\JWSVerifier;
use Jose\Component\Signature\Serializer\CompactSerializer;
use OAuth2Framework\Component\Core\Client\Client;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\Exception\OAuth2Exception;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;
use OAuth2Framework\Component\ClientAuthentication\AuthenticationMethodManager;
use OAuth2Framework\Component\ClientAuthentication\ClientAssertionJwt;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response;

/**
 * @group TokenEndpoint
 * @group ClientAuthentication
 */
class ClientAssertionJwtAuthenticationMethodTest extends TestCase
{
    /**
     * @test
     */
    public function genericCalls()
    {
        $method = $this->getMethod();

        self::assertEquals([], $method->getSchemesParameters());
        self::assertEquals(['client_secret_jwt', 'private_key_jwt'], $method->getSupportedMethods());
        self::assertEquals(['HS256', 'RS256'], $method->getSupportedSignatureAlgorithms());
        self::assertEquals([], $method->getSupportedKeyEncryptionAlgorithms());
        self::assertEquals([], $method->getSupportedContentEncryptionAlgorithms());
    }

    /**
     * @test
     */
    public function theClientIdCannotBeFoundInTheRequest()
    {
        $method = $this->getMethod();
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getHeader('Authorization')->willReturn([]);
        $request->getParsedBody()->willReturn([]);

        $clientId = $method->findClientIdAndCredentials($request->reveal(), $credentials);
        self::assertNull($clientId);
        self::assertNull($credentials);
    }

    /**
     * @test
     */
    public function theClientAssertionTypeIsNotSupported()
    {
        $method = $this->getMethod();
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getHeader('Authorization')->willReturn([]);
        $request->getParsedBody()->willReturn([
            'client_assertion_type' => 'foo',
        ]);

        $clientId = $method->findClientIdAndCredentials($request->reveal(), $credentials);
        self::assertNull($clientId);
        self::assertNull($credentials);
    }

    /**
     * @test
     */
    public function theClientAssertionIsMissing()
    {
        $method = $this->getMethod();
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getHeader('Authorization')->willReturn([]);
        $request->getParsedBody()->willReturn([
            'client_assertion_type' => 'urn:ietf:params:oauth:client-assertion-type:jwt-bearer',
        ]);

        try {
            $method->findClientIdAndCredentials($request->reveal(), $credentials);
            $this->fail('An OAuth2 exception should be thrown.');
        } catch (OAuth2Exception $e) {
            self::assertEquals('invalid_request', $e->getMessage());
            self::assertEquals('Parameter "client_assertion" is missing.', $e->getErrorDescription());
        }
    }

    /**
     * @test
     */
    public function theClientAssertionIsInvalid()
    {
        $method = $this->getMethod();
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getHeader('Authorization')->willReturn([]);
        $request->getParsedBody()->willReturn([
            'client_assertion_type' => 'urn:ietf:params:oauth:client-assertion-type:jwt-bearer',
            'client_assertion' => 'foo',
        ]);

        try {
            $method->findClientIdAndCredentials($request->reveal(), $credentials);
            $this->fail('An OAuth2 exception should be thrown.');
        } catch (OAuth2Exception $e) {
            self::assertEquals('invalid_request', $e->getMessage());
            self::assertEquals('Unable to load, decrypt or verify the client assertion.', $e->getErrorDescription());
        }
    }

    /**
     * @test
     */
    public function theClientAssertionSignedByTheClientIsInvalidBecauseOfMissingClaims()
    {
        $method = $this->getMethod();
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getHeader('Authorization')->willReturn([]);
        $request->getParsedBody()->willReturn([
            'client_assertion_type' => 'urn:ietf:params:oauth:client-assertion-type:jwt-bearer',
            'client_assertion' => $this->createInvalidClientAssertionSignedByTheClient(),
        ]);

        try {
            $method->findClientIdAndCredentials($request->reveal(), $credentials);
            $this->fail('An OAuth2 exception should be thrown.');
        } catch (OAuth2Exception $e) {
            self::assertEquals('invalid_request', $e->getMessage());
            self::assertEquals('The following claim(s) is/are mandatory: "iss, sub, aud, exp".', $e->getErrorDescription());
        }
    }

    /**
     * @test
     */
    public function theClientAssertionIsValidAndTheClientIdIsRetrieved()
    {
        $method = $this->getMethod();
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getHeader('Authorization')->willReturn([]);
        $request->getParsedBody()->willReturn([
            'client_assertion_type' => 'urn:ietf:params:oauth:client-assertion-type:jwt-bearer',
            'client_assertion' => $this->createValidClientAssertionSignedByTheClient(),
        ]);

        $clientId = $method->findClientIdAndCredentials($request->reveal(), $credentials);
        self::assertEquals('ClientId', $clientId->getValue());
    }

    /**
     * @test
     */
    /*public function theClientUsesAnotherAuthenticationMethod()
    {
        $method = $this->getMethod();
        $manager = new AuthenticationMethodManager();
        $method->add($method);
        $client = Client::createEmpty();
        $client = $client->create(
            ClientId::create('CLIENT_ID'),
            DataBag::create([
                'client_secret' => 'CLIENT_SECRET',
                'token_endpoint_auth_method' => 'client_secret_post',
            ]),
            UserAccountId::create('USER_ACCOUNT_ID')
        );
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getParsedBody()->willReturn([
            'client_assertion_type' => 'urn:ietf:params:oauth:client-assertion-type:jwt-bearer',
            'client_assertion'      => 'CLIENT_SECRET',
        ]);

        self::assertFalse($method->isClientAuthenticated($request->reveal(), $client, $method, 'CLIENT_SECRET'));
    }*/

    /**
     * @test
     */
    public function theClientConfigurationCanBeCheckedWithClientSecretJwt()
    {
        $method = $this->getMethod();
        $commandParameters = DataBag::create([
            'token_endpoint_auth_method' => 'client_secret_jwt',
        ]);
        $validatedParameters = $method->checkClientConfiguration($commandParameters, DataBag::create([]));

        self::assertTrue($validatedParameters->has('client_secret'));
        self::assertTrue($validatedParameters->has('client_secret_expires_at'));
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Either the parameter "jwks" or "jwks_uri" must be set.
     */
    public function theClientConfigurationCannotBeCheckedWithPrivateKeyJwtIfBothJwksAndJwksUriAreSet()
    {
        $method = $this->getMethod();
        $commandParameters = DataBag::create([
            'token_endpoint_auth_method' => 'private_key_jwt',
            'jwks' => 'foo',
            'jwks_uri' => 'bar',
        ]);
        $method->checkClientConfiguration($commandParameters, DataBag::create([]));
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Either the parameter "jwks" or "jwks_uri" must be set.
     */
    public function theClientConfigurationCannotBeCheckedWithPrivateKeyJwtIfNoneOfTheJwksAndJwksUriAreSet()
    {
        $method = $this->getMethod();
        $commandParameters = DataBag::create([
            'token_endpoint_auth_method' => 'private_key_jwt',
        ]);
        $method->checkClientConfiguration($commandParameters, DataBag::create([]));
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The parameter "jwks" must be a valid JWKSet object.
     */
    public function theClientConfigurationCannotBeCheckedWithPrivateKeyJwtIfJwksIsNotAValidKeySet()
    {
        $method = $this->getMethod();
        $commandParameters = DataBag::create([
            'token_endpoint_auth_method' => 'private_key_jwt',
            'jwks' => 'foo',
        ]);
        $method->checkClientConfiguration($commandParameters, DataBag::create([]));
    }

    /**
     * @test
     */
    public function theClientConfigurationCanBeCheckedWithPrivateKeyJwtIfJwksIsValid()
    {
        $method = $this->getMethod();
        $commandParameters = DataBag::create([
            'token_endpoint_auth_method' => 'private_key_jwt',
            'jwks' => '{"keys":[{"kty":"oct","k":"bJzb8RaN7TzPz001PeF0lw0ZoUJqbazGxMvBd_xzfms"},{"kty":"oct","k":"dIx5cdLn-dAgNkvfZSiroJuy5oykHO4hDnYpmwlMq6A"}]}',
        ]);
        $validatedParameters = $method->checkClientConfiguration($commandParameters, DataBag::create([]));

        self::assertTrue($validatedParameters->has('jwks'));
        self::assertEquals('{"keys":[{"kty":"oct","k":"bJzb8RaN7TzPz001PeF0lw0ZoUJqbazGxMvBd_xzfms"},{"kty":"oct","k":"dIx5cdLn-dAgNkvfZSiroJuy5oykHO4hDnYpmwlMq6A"}]}', $validatedParameters->get('jwks'));
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Distant key sets cannot be used. Please use "jwks" instead of "jwks_uri".
     */
    public function theClientConfigurationCannotBeCheckedWithPrivateKeyJwtIfJwksUriFactoryIsNotAvailable()
    {
        $method = $this->getMethod();
        $commandParameters = DataBag::create([
            'token_endpoint_auth_method' => 'private_key_jwt',
            'jwks_uri' => 'foo',
        ]);
        $method->checkClientConfiguration($commandParameters, DataBag::create([]));
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The parameter "jwks_uri" must be a valid uri to a JWKSet.
     */
    public function theClientConfigurationCannotBeCheckedWithPrivateKeyJwtIfJwksUriIsNotValid()
    {
        $method = clone $this->getMethod();
        $httpClient = $this->getHttpClient();
        $method->enableJkuSupport(
            $this->getJkuFactory($httpClient)
        );
        $commandParameters = DataBag::create([
            'token_endpoint_auth_method' => 'private_key_jwt',
            'jwks_uri' => 'foo',
        ]);
        $method->checkClientConfiguration($commandParameters, DataBag::create([]));
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The parameter "jwks_uri" must be a valid uri to a JWKSet.
     */
    public function theClientConfigurationCannotBeCheckedWithPrivateKeyJwtIfJwksUriCannotBeReached()
    {
        $method = clone $this->getMethod();
        $httpClient = $this->getHttpClient();
        $httpClient->addResponse(new Response('php://memory', 404));
        $method->enableJkuSupport(
            $this->getJkuFactory($httpClient)
        );
        $commandParameters = DataBag::create([
            'token_endpoint_auth_method' => 'private_key_jwt',
            'jwks_uri' => 'https://www.foo.com/bad-url.jwkset',
        ]);
        $method->checkClientConfiguration($commandParameters, DataBag::create([]));
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The parameter "jwks_uri" must be a valid uri to a JWKSet.
     */
    public function theClientConfigurationCannotBeCheckedWithPrivateKeyJwtIfJwksUriDoesNotContainAValidKeySet()
    {
        $method = clone $this->getMethod();
        $httpClient = $this->getHttpClient();
        $stream = fopen('php://memory', 'w+');
        fwrite($stream, 'Hello World!');
        rewind($stream);
        $httpClient->addResponse(new Response($stream, 200));
        $method->enableJkuSupport(
            $this->getJkuFactory($httpClient)
        );
        $commandParameters = DataBag::create([
            'token_endpoint_auth_method' => 'private_key_jwt',
            'jwks_uri' => 'https://www.foo.com/index.html',
        ]);
        $method->checkClientConfiguration($commandParameters, DataBag::create([]));
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The distant key set is empty.
     */
    public function theClientConfigurationCannotBeCheckedWithPrivateKeyJwtIfJwksUriDoesContainAnEmptyKeySet()
    {
        $method = clone $this->getMethod();
        $httpClient = $this->getHttpClient();
        $stream = fopen('php://memory', 'w+');
        fwrite($stream, '{"keys":[]}');
        rewind($stream);
        $httpClient->addResponse(new Response($stream, 200));
        $method->enableJkuSupport(
            $this->getJkuFactory($httpClient)
        );
        $commandParameters = DataBag::create([
            'token_endpoint_auth_method' => 'private_key_jwt',
            'jwks_uri' => 'https://www.foo.com/index.html',
        ]);
        $method->checkClientConfiguration($commandParameters, DataBag::create([]));
    }

    /**
     * @test
     */
    public function theClientConfigurationCanBeCheckedWithPrivateKeyJwt()
    {
        $method = clone $this->getMethod();
        $httpClient = $this->getHttpClient();
        $stream = fopen('php://memory', 'w+');
        fwrite($stream, '{"keys":[{"kty":"oct","k":"bJzb8RaN7TzPz001PeF0lw0ZoUJqbazGxMvBd_xzfms"},{"kty":"oct","k":"dIx5cdLn-dAgNkvfZSiroJuy5oykHO4hDnYpmwlMq6A"}]}');
        rewind($stream);
        $httpClient->addResponse(new Response($stream, 200));
        $method->enableJkuSupport(
            $this->getJkuFactory($httpClient)
        );
        $commandParameters = DataBag::create([
            'token_endpoint_auth_method' => 'private_key_jwt',
            'jwks_uri' => 'https://www.foo.com/keyset',
        ]);
        $validatedParameters = $method->checkClientConfiguration($commandParameters, DataBag::create([]));

        self::assertTrue($validatedParameters->has('jwks_uri'));
        self::assertEquals('https://www.foo.com/keyset', $validatedParameters->get('jwks_uri'));
    }

    /**
     * @var null|ClientAssertionJwt
     */
    private $method = null;

    /**
     * @return ClientAssertionJwt
     */
    private function getMethod(): ClientAssertionJwt
    {
        if (null === $this->method) {
            $this->method = new ClientAssertionJwt(
                new StandardConverter(),
                new JWSVerifier(
                    AlgorithmManager::create([
                        new HS256(),
                        new RS256(),
                    ])
                ),
                HeaderCheckerManager::create(
                    [],
                    [new JWSTokenSupport()]
                ),
                ClaimCheckerManager::create([]),
                3600
            );
        }

        return $this->method;
    }

    /**
     * @param \Http\Mock\Client $client
     *
     * @return JKUFactory
     */
    private function getJkuFactory(\Http\Mock\Client $client):JKUFactory
    {
        return new JKUFactory(
            new StandardConverter(),
            $client,
            new DiactorosMessageFactory()
        );
    }

    /**
     * @return \Http\Mock\Client
     */
    private function getHttpClient(): \Http\Mock\Client
    {
        return new \Http\Mock\Client(
            new DiactorosMessageFactory()
        );
    }

    /**
     * @return string
     */
    private function createValidClientAssertionSignedByTheClient(): string
    {
        $jsonConverter = new StandardConverter();
        $jwsBuilder = new JWSBuilder(
            $jsonConverter,
            AlgorithmManager::create([
                new HS256(),
            ])
        );

        $jws = $jwsBuilder
            ->create()
            ->withPayload($jsonConverter->encode([
                'iss' => 'ClientId',
                'sub' => 'ClientId',
                'aud' => 'My Server',
                'exp' => time()+3600,
            ]))
            ->addSignature(
                JWK::createFromJson('{"kty":"oct","k":"bJzb8RaN7TzPz001PeF0lw0ZoUJqbazGxMvBd_xzfms"}'),
                ['alg' => 'HS256']
            )
            ->build();

        $serializer = new CompactSerializer($jsonConverter);

        return $serializer->serialize($jws, 0);
    }

    /**
     * @return string
     */
    private function createInvalidClientAssertionSignedByTheClient(): string
    {
        $jsonConverter = new StandardConverter();
        $jwsBuilder = new JWSBuilder(
            $jsonConverter,
            AlgorithmManager::create([
                new HS256(),
            ])
        );

        $jws = $jwsBuilder
            ->create()
            ->withPayload($jsonConverter->encode([]))
            ->addSignature(
                JWK::createFromJson('{"kty":"oct","k":"bJzb8RaN7TzPz001PeF0lw0ZoUJqbazGxMvBd_xzfms"}'),
                ['alg' => 'HS256']
            )
            ->build();

        $serializer = new CompactSerializer($jsonConverter);

        return $serializer->serialize($jws, 0);
    }
}
