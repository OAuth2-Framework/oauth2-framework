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
use Jose\Component\Core\JWKSet;
use Jose\Component\Encryption\Algorithm\ContentEncryption\A256CBCHS512;
use Jose\Component\Encryption\Algorithm\KeyEncryption\RSAOAEP256;
use Jose\Component\Encryption\Compression\CompressionMethodManager;
use Jose\Component\Encryption\Compression\Deflate;
use Jose\Component\Encryption\JWEBuilder;
use Jose\Component\Encryption\JWEDecrypter;
use Jose\Component\Encryption\JWELoader;
use Jose\Component\Encryption\JWETokenSupport;
use Jose\Component\Encryption\Serializer\JWESerializerManager;
use Jose\Component\KeyManagement\JKUFactory;
use Jose\Component\Signature\Algorithm\HS256;
use Jose\Component\Signature\Algorithm\RS256;
use Jose\Component\Signature\JWS;
use Jose\Component\Signature\JWSBuilder;
use Jose\Component\Signature\JWSTokenSupport;
use Jose\Component\Signature\JWSVerifier;
use Jose\Component\Signature\Serializer\CompactSerializer;
use OAuth2Framework\Component\ClientAuthentication\AuthenticationMethodManager;
use OAuth2Framework\Component\ClientAuthentication\ClientAssertionJwt;
use OAuth2Framework\Component\Core\Client\Client;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\Message\OAuth2Error;
use OAuth2Framework\Component\Core\TrustedIssuer\TrustedIssuer;
use OAuth2Framework\Component\Core\TrustedIssuer\TrustedIssuerRepository;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;

/**
 * @group TokenEndpoint
 * @group ClientAuthentication
 */
final class ClientAssertionJwtAuthenticationMethodTest extends TestCase
{
    /**
     * @test
     */
    public function genericCalls()
    {
        $method = $this->getMethod();

        static::assertEquals([], $method->getSchemesParameters());
        static::assertEquals(['client_secret_jwt', 'private_key_jwt'], $method->getSupportedMethods());
        static::assertEquals(['HS256', 'RS256'], $method->getSupportedSignatureAlgorithms());
        static::assertEquals([], $method->getSupportedKeyEncryptionAlgorithms());
        static::assertEquals([], $method->getSupportedContentEncryptionAlgorithms());
    }

    /**
     * @test
     */
    public function genericCallsWithEncryptionSupport()
    {
        $method = $this->getMethodWithEncryptionSupport(false);

        static::assertEquals([], $method->getSchemesParameters());
        static::assertEquals(['client_secret_jwt', 'private_key_jwt'], $method->getSupportedMethods());
        static::assertEquals(['HS256', 'RS256'], $method->getSupportedSignatureAlgorithms());
        static::assertEquals(['RSA-OAEP-256'], $method->getSupportedKeyEncryptionAlgorithms());
        static::assertEquals(['A256CBC-HS512'], $method->getSupportedContentEncryptionAlgorithms());
    }

    /**
     * @test
     */
    public function theClientIdCannotBeFoundInTheRequest()
    {
        $method = $this->getMethod();
        $request = $this->buildRequest([]);
        $request->getParsedBody()->willReturn([]);
        $request->getHeader('Authorization')->willReturn([]);

        $clientId = $method->findClientIdAndCredentials($request->reveal(), $credentials);
        static::assertNull($clientId);
        static::assertNull($credentials);
    }

    /**
     * @test
     */
    public function theClientAssertionTypeIsNotSupported()
    {
        $method = $this->getMethod();
        $request = $this->buildRequest([
            'client_assertion_type' => 'foo',
        ]);

        $request->getHeader('Authorization')->willReturn([]);
        $clientId = $method->findClientIdAndCredentials($request->reveal(), $credentials);
        static::assertNull($clientId);
        static::assertNull($credentials);
    }

    /**
     * @test
     */
    public function theClientAssertionIsMissing()
    {
        $method = $this->getMethod();
        $request = $this->buildRequest([
            'client_assertion_type' => 'urn:ietf:params:oauth:client-assertion-type:jwt-bearer',
        ]);

        $request->getHeader('Authorization')->willReturn([]);

        try {
            $method->findClientIdAndCredentials($request->reveal(), $credentials);
            static::fail('An OAuth2 exception should be thrown.');
        } catch (OAuth2Error $e) {
            static::assertEquals('invalid_request', $e->getMessage());
            static::assertEquals('Parameter "client_assertion" is missing.', $e->getErrorDescription());
        }
    }

    /**
     * @test
     */
    public function theClientAssertionIsInvalid()
    {
        $method = $this->getMethod();
        $request = $this->buildRequest([
            'client_assertion_type' => 'urn:ietf:params:oauth:client-assertion-type:jwt-bearer',
            'client_assertion' => 'foo',
        ]);

        $request->getHeader('Authorization')->willReturn([]);

        try {
            $method->findClientIdAndCredentials($request->reveal(), $credentials);
            static::fail('An OAuth2 exception should be thrown.');
        } catch (OAuth2Error $e) {
            static::assertEquals('invalid_request', $e->getMessage());
            static::assertEquals('Unable to load, decrypt or verify the client assertion.', $e->getErrorDescription());
        }
    }

    /**
     * @test
     */
    public function theClientAssertionSignedByTheClientIsInvalidBecauseOfMissingClaims()
    {
        $assertion = $this->serializeJWS(
            $this->createInvalidClientAssertionSignedByTheClient()
        );
        $method = $this->getMethod();
        $request = $this->buildRequest([
            'client_assertion_type' => 'urn:ietf:params:oauth:client-assertion-type:jwt-bearer',
            'client_assertion' => $assertion,
        ]);

        $request->getHeader('Authorization')->willReturn([]);

        try {
            $method->findClientIdAndCredentials($request->reveal(), $credentials);
            static::fail('An OAuth2 exception should be thrown.');
        } catch (OAuth2Error $e) {
            static::assertEquals('invalid_request', $e->getMessage());
            static::assertEquals('The following claim(s) is/are mandatory: "iss, sub, aud, exp".', $e->getErrorDescription());
        }
    }

    /**
     * @test
     */
    public function theClientAssertionSignedByTheClientIsRejectedBecauseEncryptionIsMandatory()
    {
        $assertion = $this->serializeJWS(
            $this->createInvalidClientAssertionSignedByTheClient()
        );
        $method = $this->getMethodWithEncryptionSupport(true);
        $request = $this->buildRequest([
            'client_assertion_type' => 'urn:ietf:params:oauth:client-assertion-type:jwt-bearer',
            'client_assertion' => $assertion,
        ]);

        $request->getHeader('Authorization')->willReturn([]);

        try {
            $method->findClientIdAndCredentials($request->reveal(), $credentials);
            static::fail('An OAuth2 exception should be thrown.');
        } catch (OAuth2Error $e) {
            static::assertEquals('invalid_request', $e->getMessage());
            static::assertEquals('The encryption of the assertion is mandatory but the decryption of the assertion failed.', $e->getErrorDescription());
        }
    }

    /**
     * @test
     */
    public function theEncryptedClientAssertionSignedAndEncryptedByTheClientIsInvalidBecauseOfMissingClaims()
    {
        $assertion = $this->encryptAssertion(
            $this->serializeJWS(
                $this->createInvalidClientAssertionSignedByTheClient()
            )
        );
        $method = $this->getMethodWithEncryptionSupport(false);
        $request = $this->buildRequest([
            'client_assertion_type' => 'urn:ietf:params:oauth:client-assertion-type:jwt-bearer',
            'client_assertion' => $assertion,
        ]);

        $request->getHeader('Authorization')->willReturn([]);

        try {
            $method->findClientIdAndCredentials($request->reveal(), $credentials);
            static::fail('An OAuth2 exception should be thrown.');
        } catch (OAuth2Error $e) {
            static::assertEquals('invalid_request', $e->getMessage());
            static::assertEquals('The following claim(s) is/are mandatory: "iss, sub, aud, exp".', $e->getErrorDescription());
        }
    }

    /**
     * @test
     */
    public function theClientAssertionIsValidAndTheClientIdIsRetrieved()
    {
        $assertion = $this->serializeJWS(
            $this->createValidClientAssertionSignedByTheClient()
        );
        $method = $this->getMethod();
        $request = $this->buildRequest([
            'client_assertion_type' => 'urn:ietf:params:oauth:client-assertion-type:jwt-bearer',
            'client_assertion' => $assertion,
        ]);

        $request->getHeader('Authorization')->willReturn([]);
        $clientId = $method->findClientIdAndCredentials($request->reveal(), $credentials);
        static::assertEquals('ClientId', $clientId->getValue());
    }

    /**
     * @test
     */
    public function theEncryptedClientAssertionIsValidAndTheClientIdIsRetrieved()
    {
        $assertion = $this->encryptAssertion(
            $this->serializeJWS(
                $this->createValidClientAssertionSignedByTheClient()
            )
        );
        $method = $this->getMethodWithEncryptionSupport(false);
        $request = $this->buildRequest([
            'client_assertion_type' => 'urn:ietf:params:oauth:client-assertion-type:jwt-bearer',
            'client_assertion' => $assertion,
        ]);
        $request->getHeader('Authorization')->willReturn([]);

        $clientId = $method->findClientIdAndCredentials($request->reveal(), $credentials);
        static::assertEquals('ClientId', $clientId->getValue());
    }

    /**
     * @test
     */
    public function theClientUsesAnotherAuthenticationMethod()
    {
        $jws = $this->createInvalidClientAssertionSignedByTheClient();
        $assertion = $this->encryptAssertion(
            $this->serializeJWS(
                $jws
            )
        );
        $method = $this->getMethodWithEncryptionSupport(false);
        $manager = new AuthenticationMethodManager();
        $manager->add($method);

        $client = $this->prophesize(Client::class);
        $client->isPublic()->willReturn(false);
        $client->getPublicId()->willReturn(new ClientId('CLIENT_ID'));
        $client->getClientId()->willReturn(new ClientId('CLIENT_ID'));
        $client->get('token_endpoint_auth_method')->willReturn('client_secret_post');

        $request = $this->buildRequest([
            'client_assertion_type' => 'urn:ietf:params:oauth:client-assertion-type:jwt-bearer',
            'client_assertion' => $assertion,
        ]);

        static::assertFalse($manager->isClientAuthenticated($request->reveal(), $client->reveal(), $method, $jws));
    }

    /**
     * @test
     */
    public function theClientWithPrivateKeyIsAuthenticated()
    {
        $jws = $this->createValidClientAssertionSignedByTheClient();
        $assertion = $this->encryptAssertion(
            $this->serializeJWS(
                $jws
            )
        );
        $method = $this->getMethodWithEncryptionSupport(false);
        $manager = new AuthenticationMethodManager();
        $manager->add($method);

        $client = $this->prophesize(Client::class);
        $client->isPublic()->willReturn(false);
        $client->getPublicId()->willReturn(new ClientId('CLIENT_ID'));
        $client->getClientId()->willReturn(new ClientId('CLIENT_ID'));
        $client->get('token_endpoint_auth_method')->willReturn('private_key_jwt');
        $client->getTokenEndpointAuthenticationMethod()->willReturn('private_key_jwt');
        $client->has('jwks')->willReturn(true);
        $client->get('jwks')->willReturn(\Safe\json_decode('{"keys":[{"kty":"oct","k":"U0VDUkVU"}]}', true));
        $client->areClientCredentialsExpired()->willReturn(false);

        $request = $this->buildRequest([
            'client_assertion_type' => 'urn:ietf:params:oauth:client-assertion-type:jwt-bearer',
            'client_assertion' => $assertion,
        ]);

        static::assertTrue($manager->isClientAuthenticated($request->reveal(), $client->reveal(), $method, $jws));
    }

    /**
     * @test
     */
    public function theClientWithClientSecretIsAuthenticated()
    {
        $jws = $this->createValidClientAssertionSignedByTheClient();
        $assertion = $this->encryptAssertion(
            $this->serializeJWS(
                $jws
            )
        );
        $method = $this->getMethodWithEncryptionSupport(false);
        $manager = new AuthenticationMethodManager();
        $manager->add($method);

        $client = $this->prophesize(Client::class);
        $client->isPublic()->willReturn(false);
        $client->getPublicId()->willReturn(new ClientId('CLIENT_ID'));
        $client->getClientId()->willReturn(new ClientId('CLIENT_ID'));
        $client->get('token_endpoint_auth_method')->willReturn('client_secret_jwt');
        $client->getTokenEndpointAuthenticationMethod()->willReturn('client_secret_jwt');
        $client->has('jwks')->willReturn(false);
        $client->has('client_secret')->willReturn(true);
        $client->get('client_secret')->willReturn('SECRET');
        $client->areClientCredentialsExpired()->willReturn(false);

        $request = $this->buildRequest([
            'client_assertion_type' => 'urn:ietf:params:oauth:client-assertion-type:jwt-bearer',
            'client_assertion' => $assertion,
        ]);

        static::assertTrue($manager->isClientAuthenticated($request->reveal(), $client->reveal(), $method, $jws));
    }

    /**
     * @test
     */
    public function theClientWithTrustedIssuerAssertionIsAuthenticated()
    {
        $jws = $this->createValidClientAssertionSignedByATrustedIssuer();
        $assertion = $this->serializeJWS(
            $jws
        );
        $method = $this->getMethodWithTrustedIssuerSupport();
        $manager = new AuthenticationMethodManager();
        $manager->add($method);

        $client = $this->prophesize(Client::class);
        $client->isPublic()->willReturn(false);
        $client->getPublicId()->willReturn(new ClientId('CLIENT_ID'));
        $client->getClientId()->willReturn(new ClientId('CLIENT_ID'));
        $client->get('token_endpoint_auth_method')->willReturn('client_secret_jwt');
        $client->getTokenEndpointAuthenticationMethod()->willReturn('client_secret_jwt');
        $client->has('jwks')->willReturn(false);
        $client->has('client_secret')->willReturn(false);
        $client->areClientCredentialsExpired()->willReturn(false);

        $request = $this->buildRequest([
            'client_assertion_type' => 'urn:ietf:params:oauth:client-assertion-type:jwt-bearer',
            'client_assertion' => $assertion,
        ]);

        static::assertTrue($manager->isClientAuthenticated($request->reveal(), $client->reveal(), $method, $jws));
    }

    /**
     * @test
     */
    public function theClientConfigurationCanBeCheckedWithClientSecretJwt()
    {
        $method = $this->getMethod();
        $commandParameters = new DataBag([
            'token_endpoint_auth_method' => 'client_secret_jwt',
        ]);
        $validatedParameters = $method->checkClientConfiguration($commandParameters, new DataBag([]));

        static::assertTrue($validatedParameters->has('client_secret'));
        static::assertTrue($validatedParameters->has('client_secret_expires_at'));
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Either the parameter "jwks" or "jwks_uri" must be set.
     */
    public function theClientConfigurationCannotBeCheckedWithPrivateKeyJwtIfBothJwksAndJwksUriAreSet()
    {
        $method = $this->getMethod();
        $commandParameters = new DataBag([
            'token_endpoint_auth_method' => 'private_key_jwt',
            'jwks' => 'foo',
            'jwks_uri' => 'bar',
        ]);
        $validatedParameters = $method->checkClientConfiguration($commandParameters, new DataBag([]));

        static::assertTrue($validatedParameters->has('token_endpoint_auth_method'));
        static::assertEquals('private_key_jwt', $validatedParameters->get('token_endpoint_auth_method'));
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Either the parameter "jwks" or "jwks_uri" must be set.
     */
    public function theClientConfigurationCannotBeCheckedWithPrivateKeyJwtIfBothJwksAndJwksUriAreNotSetBecauseTrustedIssuerSupportIsDisabled()
    {
        $method = $this->getMethod();
        $commandParameters = new DataBag([
            'token_endpoint_auth_method' => 'private_key_jwt',
        ]);
        $validatedParameters = $method->checkClientConfiguration($commandParameters, new DataBag([]));

        static::assertTrue($validatedParameters->has('token_endpoint_auth_method'));
        static::assertEquals('private_key_jwt', $validatedParameters->get('token_endpoint_auth_method'));
    }

    /**
     * @test
     */
    public function theClientConfigurationCanBeCheckedWithPrivateKeyJwtIfBothJwksAndJwksUriAreNotSetBecauseTrustedIssuerSupportIsEnabled()
    {
        $method = $this->getMethodWithTrustedIssuerSupport();
        $commandParameters = new DataBag([
            'token_endpoint_auth_method' => 'private_key_jwt',
        ]);
        $validatedParameters = $method->checkClientConfiguration($commandParameters, new DataBag([]));

        static::assertFalse($validatedParameters->has('jwks'));
        static::assertFalse($validatedParameters->has('jwks_uri'));
    }

    /**
     * @test
     */
    public function theClientConfigurationCannotBeCheckedWithPrivateKeyJwtIfJwksIsNotAValidKeySet()
    {
        $method = $this->getMethod();
        $commandParameters = new DataBag([
            'token_endpoint_auth_method' => 'private_key_jwt',
            'jwks' => 'foo',
        ]);
        $validatedParameters = $method->checkClientConfiguration($commandParameters, new DataBag([]));
        static::assertTrue($validatedParameters->has('jwks'));
    }

    /**
     * @test
     */
    public function theClientConfigurationCanBeCheckedWithPrivateKeyJwtIfJwksIsValid()
    {
        $method = $this->getMethod();
        $commandParameters = new DataBag([
            'token_endpoint_auth_method' => 'private_key_jwt',
            'jwks' => \Safe\json_decode('{"keys":[{"kty":"oct","k":"U0VDUkVU"}]}', true),
        ]);
        $validatedParameters = $method->checkClientConfiguration($commandParameters, new DataBag([]));

        static::assertTrue($validatedParameters->has('jwks'));
        static::assertEquals(\Safe\json_decode('{"keys":[{"kty":"oct","k":"U0VDUkVU"}]}', true), $validatedParameters->get('jwks'));
    }

    /**
     * @test
     */
    public function theClientConfigurationCannotBeCheckedWithPrivateKeyJwtIfJwksUriFactoryIsNotAvailable()
    {
        $method = $this->getMethod();
        $commandParameters = new DataBag([
            'token_endpoint_auth_method' => 'private_key_jwt',
            'jwks_uri' => 'foo',
        ]);
        $validatedParameters = $method->checkClientConfiguration($commandParameters, new DataBag([]));

        static::assertTrue($validatedParameters->has('jwks_uri'));
    }

    /**
     * @var ClientAssertionJwt|null
     */
    private $method;

    private function getMethod(): ClientAssertionJwt
    {
        if (null === $this->method) {
            $this->method = new ClientAssertionJwt(
                new StandardConverter(),
                new JWSVerifier(AlgorithmManager::create([new HS256(), new RS256()])),
                HeaderCheckerManager::create([], [new JWSTokenSupport()]),
                ClaimCheckerManager::create([]),
                3600
            );
        }

        return $this->method;
    }

    private function getMethodWithEncryptionSupport(bool $isRequired): ClientAssertionJwt
    {
        $method = clone $this->getMethod();

        $method->enableEncryptedAssertions(
            new JWELoader(
                JWESerializerManager::create([new \Jose\Component\Encryption\Serializer\CompactSerializer(new StandardConverter())]),
                new JWEDecrypter(
                    AlgorithmManager::create([new RSAOAEP256()]),
                    AlgorithmManager::create([new A256CBCHS512()]),
                    CompressionMethodManager::create([new Deflate()])
                ),
                HeaderCheckerManager::create([], [new JWETokenSupport()])
            ),
            JWKSet::createFromKeys([JWK::create([
                'kty' => 'RSA',
                'kid' => 'samwise.gamgee@hobbiton.example',
                'use' => 'enc',
                'n' => 'wbdxI55VaanZXPY29Lg5hdmv2XhvqAhoxUkanfzf2-5zVUxa6prHRrI4pP1AhoqJRlZfYtWWd5mmHRG2pAHIlh0ySJ9wi0BioZBl1XP2e-C-FyXJGcTy0HdKQWlrfhTm42EW7Vv04r4gfao6uxjLGwfpGrZLarohiWCPnkNrg71S2CuNZSQBIPGjXfkmIy2tl_VWgGnL22GplyXj5YlBLdxXp3XeStsqo571utNfoUTU8E4qdzJ3U1DItoVkPGsMwlmmnJiwA7sXRItBCivR4M5qnZtdw-7v4WuR4779ubDuJ5nalMv2S66-RPcnFAzWSKxtBDnFJJDGIUe7Tzizjg1nms0Xq_yPub_UOlWn0ec85FCft1hACpWG8schrOBeNqHBODFskYpUc2LC5JA2TaPF2dA67dg1TTsC_FupfQ2kNGcE1LgprxKHcVWYQb86B-HozjHZcqtauBzFNV5tbTuB-TpkcvJfNcFLlH3b8mb-H_ox35FjqBSAjLKyoeqfKTpVjvXhd09knwgJf6VKq6UC418_TOljMVfFTWXUxlnfhOOnzW6HSSzD1c9WrCuVzsUMv54szidQ9wf1cYWf3g5qFDxDQKis99gcDaiCAwM3yEBIzuNeeCa5dartHDb1xEB_HcHSeYbghbMjGfasvKn0aZRsnTyC0xhWBlsolZE',
                'e' => 'AQAB',
                'alg' => 'RSA-OAEP-256',
                'd' => 'n7fzJc3_WG59VEOBTkayzuSMM780OJQuZjN_KbH8lOZG25ZoA7T4Bxcc0xQn5oZE5uSCIwg91oCt0JvxPcpmqzaJZg1nirjcWZ-oBtVk7gCAWq-B3qhfF3izlbkosrzjHajIcY33HBhsy4_WerrXg4MDNE4HYojy68TcxT2LYQRxUOCf5TtJXvM8olexlSGtVnQnDRutxEUCwiewfmmrfveEogLx9EA-KMgAjTiISXxqIXQhWUQX1G7v_mV_Hr2YuImYcNcHkRvp9E7ook0876DhkO8v4UOZLwA1OlUX98mkoqwc58A_Y2lBYbVx1_s5lpPsEqbbH-nqIjh1fL0gdNfihLxnclWtW7pCztLnImZAyeCWAG7ZIfv-Rn9fLIv9jZ6r7r-MSH9sqbuziHN2grGjD_jfRluMHa0l84fFKl6bcqN1JWxPVhzNZo01yDF-1LiQnqUYSepPf6X3a2SOdkqBRiquE6EvLuSYIDpJq3jDIsgoL8Mo1LoomgiJxUwL_GWEOGu28gplyzm-9Q0U0nyhEf1uhSR8aJAQWAiFImWH5W_IQT9I7-yrindr_2fWQ_i1UgMsGzA7aOGzZfPljRy6z-tY_KuBG00-28S_aWvjyUc-Alp8AUyKjBZ-7CWH32fGWK48j1t-zomrwjL_mnhsPbGs0c9WsWgRzI-K8gE',
                'p' => '7_2v3OQZzlPFcHyYfLABQ3XP85Es4hCdwCkbDeltaUXgVy9l9etKghvM4hRkOvbb01kYVuLFmxIkCDtpi-zLCYAdXKrAK3PtSbtzld_XZ9nlsYa_QZWpXB_IrtFjVfdKUdMz94pHUhFGFj7nr6NNxfpiHSHWFE1zD_AC3mY46J961Y2LRnreVwAGNw53p07Db8yD_92pDa97vqcZOdgtybH9q6uma-RFNhO1AoiJhYZj69hjmMRXx-x56HO9cnXNbmzNSCFCKnQmn4GQLmRj9sfbZRqL94bbtE4_e0Zrpo8RNo8vxRLqQNwIy85fc6BRgBJomt8QdQvIgPgWCv5HoQ',
                'q' => 'zqOHk1P6WN_rHuM7ZF1cXH0x6RuOHq67WuHiSknqQeefGBA9PWs6ZyKQCO-O6mKXtcgE8_Q_hA2kMRcKOcvHil1hqMCNSXlflM7WPRPZu2qCDcqssd_uMbP-DqYthH_EzwL9KnYoH7JQFxxmcv5An8oXUtTwk4knKjkIYGRuUwfQTus0w1NfjFAyxOOiAQ37ussIcE6C6ZSsM3n41UlbJ7TCqewzVJaPJN5cxjySPZPD3Vp01a9YgAD6a3IIaKJdIxJS1ImnfPevSJQBE79-EXe2kSwVgOzvt-gsmM29QQ8veHy4uAqca5dZzMs7hkkHtw1z0jHV90epQJJlXXnH8Q',
                'dp' => '19oDkBh1AXelMIxQFm2zZTqUhAzCIr4xNIGEPNoDt1jK83_FJA-xnx5kA7-1erdHdms_Ef67HsONNv5A60JaR7w8LHnDiBGnjdaUmmuO8XAxQJ_ia5mxjxNjS6E2yD44USo2JmHvzeeNczq25elqbTPLhUpGo1IZuG72FZQ5gTjXoTXC2-xtCDEUZfaUNh4IeAipfLugbpe0JAFlFfrTDAMUFpC3iXjxqzbEanflwPvj6V9iDSgjj8SozSM0dLtxvu0LIeIQAeEgT_yXcrKGmpKdSO08kLBx8VUjkbv_3Pn20Gyu2YEuwpFlM_H1NikuxJNKFGmnAq9LcnwwT0jvoQ',
                'dq' => 'S6p59KrlmzGzaQYQM3o0XfHCGvfqHLYjCO557HYQf72O9kLMCfd_1VBEqeD-1jjwELKDjck8kOBl5UvohK1oDfSP1DleAy-cnmL29DqWmhgwM1ip0CCNmkmsmDSlqkUXDi6sAaZuntyukyflI-qSQ3C_BafPyFaKrt1fgdyEwYa08pESKwwWisy7KnmoUvaJ3SaHmohFS78TJ25cfc10wZ9hQNOrIChZlkiOdFCtxDqdmCqNacnhgE3bZQjGp3n83ODSz9zwJcSUvODlXBPc2AycH6Ci5yjbxt4Ppox_5pjm6xnQkiPgj01GpsUssMmBN7iHVsrE7N2iznBNCeOUIQ',
                'qi' => 'FZhClBMywVVjnuUud-05qd5CYU0dK79akAgy9oX6RX6I3IIIPckCciRrokxglZn-omAY5CnCe4KdrnjFOT5YUZE7G_Pg44XgCXaarLQf4hl80oPEf6-jJ5Iy6wPRx7G2e8qLxnh9cOdf-kRqgOS3F48Ucvw3ma5V6KGMwQqWFeV31XtZ8l5cVI-I3NzBS7qltpUVgz2Ju021eyc7IlqgzR98qKONl27DuEES0aK0WE97jnsyO27Yp88Wa2RiBrEocM89QZI1seJiGDizHRUP4UZxw9zsXww46wy0P6f9grnYp7t8LkyDDk8eoI4KX6SNMNVcyVS9IWjlq8EzqZEKIA',
            ])]),
            $isRequired
        );

        return $method;
    }

    private function getMethodWithTrustedIssuerSupport(): ClientAssertionJwt
    {
        $method = clone $this->getMethod();

        $trustedIssuer = $this->prophesize(TrustedIssuer::class);
        $trustedIssuer->name()->willReturn('TRUSTED_ISSUER');
        $trustedIssuer->getAllowedAssertionTypes()->willReturn(['urn:ietf:params:oauth:client-assertion-type:jwt-bearer']);
        $trustedIssuer->getAllowedSignatureAlgorithms()->willReturn(['RS256']);
        $trustedIssuer->getJWKSet()->willReturn(JWKSet::createFromKeys([JWK::create([
            'kty' => 'RSA',
            'n' => '33WRDEG5rN7daMgI2N5H8cPwTeQPOnz34uG2fe0yKyHjJDGE2XoESRpu5LelSPdYM_r4AWMFWoDWPd-7xaq7uFEkM8c6zaQIgj4uEiq-pBMvH-e805SFbYOKYqfQe4eeXAk4OrQwcUkSrlGskf6YUaw_3IwbPgzEDTgTZFVtQlE',
            'e' => 'AQAB',
            'alg' => 'RS256',
            'use' => 'sig',
        ])]));

        $trustedIssuerRepository = $this->prophesize(TrustedIssuerRepository::class);
        $trustedIssuerRepository->find('TRUSTED_ISSUER')->willReturn($trustedIssuer->reveal());

        $method->enableTrustedIssuerSupport($trustedIssuerRepository->reveal());

        return $method;
    }

    private function getJkuFactory(\Http\Mock\Client $client): JKUFactory
    {
        return new JKUFactory(
            new StandardConverter(),
            $client,
            new DiactorosMessageFactory()
        );
    }

    private function getHttpClient(): \Http\Mock\Client
    {
        return new \Http\Mock\Client(
            new DiactorosMessageFactory()
        );
    }

    private function serializeJWS(JWS $jws): string
    {
        $jsonConverter = new StandardConverter();
        $serializer = new CompactSerializer($jsonConverter);

        return $serializer->serialize($jws, 0);
    }

    private function createValidClientAssertionSignedByTheClient(): JWS
    {
        $jsonConverter = new StandardConverter();
        $jwsBuilder = new JWSBuilder(
            $jsonConverter,
            AlgorithmManager::create([
                new HS256(),
            ])
        );

        return $jwsBuilder
            ->create()
            ->withPayload($jsonConverter->encode([
                'iss' => 'ClientId',
                'sub' => 'ClientId',
                'aud' => 'My Server',
                'exp' => \time() + 3600,
            ]))
            ->addSignature(
                JWK::createFromJson('{"kty":"oct","k":"U0VDUkVU"}'),
                ['alg' => 'HS256']
            )
            ->build();
    }

    private function createValidClientAssertionSignedByATrustedIssuer(): JWS
    {
        $jsonConverter = new StandardConverter();
        $jwsBuilder = new JWSBuilder(
            $jsonConverter,
            AlgorithmManager::create([
                new RS256(),
            ])
        );

        return $jwsBuilder
            ->create()
            ->withPayload($jsonConverter->encode([
                'iss' => 'TRUSTED_ISSUER',
                'sub' => 'ClientId',
                'aud' => 'My Server',
                'exp' => \time() + 3600,
            ]))
            ->addSignature(
                JWK::create([
                    'kty' => 'RSA',
                    'n' => '33WRDEG5rN7daMgI2N5H8cPwTeQPOnz34uG2fe0yKyHjJDGE2XoESRpu5LelSPdYM_r4AWMFWoDWPd-7xaq7uFEkM8c6zaQIgj4uEiq-pBMvH-e805SFbYOKYqfQe4eeXAk4OrQwcUkSrlGskf6YUaw_3IwbPgzEDTgTZFVtQlE',
                    'e' => 'AQAB',
                    'p' => '9Vovb8pySyOZUoTrNMD6JmTsDa12u9y4_HImQuKD0rerVo2y5y7D_r00i1MhGHkBrI3W2PsubIiZgKp1f0oQfQ',
                    'd' => 'jrDrO3Fo2GvD5Jn_lER0mnxtIb_kvYt5WyaYutbRN1u_SKhaVeklfWzkrSZb5DkV2LOE1JXfoEgvBnms1O9OSJXwqDrFF7NDebw95g6JzI-SbkIHw0Cb-_E9K92FjvW3Bi8j9PKIa8c_dpwIAIirc_q8uhSTf4WoIOHSFbSaQPE',
                    'q' => '6Sgna9gQw4dXN0jBSjOZSjl4S2_H3wHatclrvlYfbJVU6GlIlqWGaUkdFvCuEr9iXJAY4zpEQ4P370EZtsyVZQ',
                    'dp' => '5m79fpE1Jz0YE1ijT7ivOMAws-fnTCnR08eiB8-W36GBWplbHaXejrJFV1WMD-AWomnVD5VZ1LW29hEiqZp2QQ',
                    'dq' => 'JV2pC7CB50QeZx7C02h3jZyuObC9YHEEoxOXr9ZPjPBVvjV5S6NVajQsdEu4Kgr_8YOqaWgiHovcxTwyqcgZvQ',
                    'qi' => 'VZykPj-ugKQxuWTSE-hA-nJqkl7FzjfzHte4QYUSHLHFq6oLlHhgUoJ_4oFLaBmCvgZLAFRDDD6pnd5Fgzt9ow',
                ]),
                ['alg' => 'RS256']
            )
            ->build();
    }

    private function createInvalidClientAssertionSignedByTheClient(): JWS
    {
        $jsonConverter = new StandardConverter();
        $jwsBuilder = new JWSBuilder(
            $jsonConverter,
            AlgorithmManager::create([
                new HS256(),
            ])
        );

        return $jwsBuilder
            ->create()
            ->withPayload($jsonConverter->encode([]))
            ->addSignature(
                JWK::createFromJson('{"kty":"oct","k":"U0VDUkVU"}'),
                ['alg' => 'HS256']
            )
            ->build();
    }

    private function createInvalidClientAssertionSignedByATrustedIssuer(): JWS
    {
        $jsonConverter = new StandardConverter();
        $jwsBuilder = new JWSBuilder(
            $jsonConverter,
            AlgorithmManager::create([
                new RS256(),
            ])
        );

        return $jwsBuilder
            ->create()
            ->withPayload($jsonConverter->encode([
            ]))
            ->addSignature(
                JWK::create([
                    'kty' => 'RSA',
                    'n' => '33WRDEG5rN7daMgI2N5H8cPwTeQPOnz34uG2fe0yKyHjJDGE2XoESRpu5LelSPdYM_r4AWMFWoDWPd-7xaq7uFEkM8c6zaQIgj4uEiq-pBMvH-e805SFbYOKYqfQe4eeXAk4OrQwcUkSrlGskf6YUaw_3IwbPgzEDTgTZFVtQlE',
                    'e' => 'AQAB',
                    'p' => '9Vovb8pySyOZUoTrNMD6JmTsDa12u9y4_HImQuKD0rerVo2y5y7D_r00i1MhGHkBrI3W2PsubIiZgKp1f0oQfQ',
                    'd' => 'jrDrO3Fo2GvD5Jn_lER0mnxtIb_kvYt5WyaYutbRN1u_SKhaVeklfWzkrSZb5DkV2LOE1JXfoEgvBnms1O9OSJXwqDrFF7NDebw95g6JzI-SbkIHw0Cb-_E9K92FjvW3Bi8j9PKIa8c_dpwIAIirc_q8uhSTf4WoIOHSFbSaQPE',
                    'q' => '6Sgna9gQw4dXN0jBSjOZSjl4S2_H3wHatclrvlYfbJVU6GlIlqWGaUkdFvCuEr9iXJAY4zpEQ4P370EZtsyVZQ',
                    'dp' => '5m79fpE1Jz0YE1ijT7ivOMAws-fnTCnR08eiB8-W36GBWplbHaXejrJFV1WMD-AWomnVD5VZ1LW29hEiqZp2QQ',
                    'dq' => 'JV2pC7CB50QeZx7C02h3jZyuObC9YHEEoxOXr9ZPjPBVvjV5S6NVajQsdEu4Kgr_8YOqaWgiHovcxTwyqcgZvQ',
                    'qi' => 'VZykPj-ugKQxuWTSE-hA-nJqkl7FzjfzHte4QYUSHLHFq6oLlHhgUoJ_4oFLaBmCvgZLAFRDDD6pnd5Fgzt9ow',
                ]),
                ['alg' => 'RS256']
            )
            ->build();
    }

    private function encryptAssertion(string $assertion): string
    {
        $jsonConverter = new StandardConverter();
        $jweBuilder = new JWEBuilder(
            $jsonConverter,
            AlgorithmManager::create([new RSAOAEP256()]),
            AlgorithmManager::create([new A256CBCHS512()]),
            CompressionMethodManager::create([new Deflate()])
        );
        $jwe = $jweBuilder->create()
            ->withPayload($assertion)
            ->withSharedProtectedHeader(['alg' => 'RSA-OAEP-256', 'enc' => 'A256CBC-HS512'])
            ->addRecipient(JWK::create([
                'kty' => 'RSA',
                'kid' => 'samwise.gamgee@hobbiton.example',
                'use' => 'enc',
                'n' => 'wbdxI55VaanZXPY29Lg5hdmv2XhvqAhoxUkanfzf2-5zVUxa6prHRrI4pP1AhoqJRlZfYtWWd5mmHRG2pAHIlh0ySJ9wi0BioZBl1XP2e-C-FyXJGcTy0HdKQWlrfhTm42EW7Vv04r4gfao6uxjLGwfpGrZLarohiWCPnkNrg71S2CuNZSQBIPGjXfkmIy2tl_VWgGnL22GplyXj5YlBLdxXp3XeStsqo571utNfoUTU8E4qdzJ3U1DItoVkPGsMwlmmnJiwA7sXRItBCivR4M5qnZtdw-7v4WuR4779ubDuJ5nalMv2S66-RPcnFAzWSKxtBDnFJJDGIUe7Tzizjg1nms0Xq_yPub_UOlWn0ec85FCft1hACpWG8schrOBeNqHBODFskYpUc2LC5JA2TaPF2dA67dg1TTsC_FupfQ2kNGcE1LgprxKHcVWYQb86B-HozjHZcqtauBzFNV5tbTuB-TpkcvJfNcFLlH3b8mb-H_ox35FjqBSAjLKyoeqfKTpVjvXhd09knwgJf6VKq6UC418_TOljMVfFTWXUxlnfhOOnzW6HSSzD1c9WrCuVzsUMv54szidQ9wf1cYWf3g5qFDxDQKis99gcDaiCAwM3yEBIzuNeeCa5dartHDb1xEB_HcHSeYbghbMjGfasvKn0aZRsnTyC0xhWBlsolZE',
                'e' => 'AQAB',
                'alg' => 'RSA-OAEP-256',
            ]))
            ->build();

        $serializer = new \Jose\Component\Encryption\Serializer\CompactSerializer($jsonConverter);

        return $serializer->serialize($jwe, 0);
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
