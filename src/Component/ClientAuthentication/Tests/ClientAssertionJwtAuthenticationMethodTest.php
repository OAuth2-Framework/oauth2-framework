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
        self::assertEquals(['RSA-OAEP-256'], $method->getSupportedKeyEncryptionAlgorithms());
        self::assertEquals(['A256CBC-HS512'], $method->getSupportedContentEncryptionAlgorithms());
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
    public function theEncryptedClientAssertionSignedAndEncryptedByTheClientIsInvalidBecauseOfMissingClaims()
    {
        $method = $this->getMethod();
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getHeader('Authorization')->willReturn([]);
        $request->getParsedBody()->willReturn([
            'client_assertion_type' => 'urn:ietf:params:oauth:client-assertion-type:jwt-bearer',
            'client_assertion' => $this->createInvalidClientAssertionSignedAndEncryptedByTheClient(),
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
    public function theEncryptedClientAssertionIsValidAndTheClientIdIsRetrieved()
    {
        $method = $this->getMethod();
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getHeader('Authorization')->willReturn([]);
        $request->getParsedBody()->willReturn([
            'client_assertion_type' => 'urn:ietf:params:oauth:client-assertion-type:jwt-bearer',
            'client_assertion' => $this->createValidClientAssertionSignedAndEncryptedByTheClient(),
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
                new JWSVerifier(AlgorithmManager::create([new HS256(), new RS256()])),
                HeaderCheckerManager::create([], [new JWSTokenSupport()]),
                ClaimCheckerManager::create([]),
                3600
            );

            $this->method->enableEncryptedAssertions(
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
                false
            );
        }

        return $this->method;
    }

    /**
     * @param \Http\Mock\Client $client
     *
     * @return JKUFactory
     */
    private function getJkuFactory(\Http\Mock\Client $client): JKUFactory
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
                'exp' => time() + 3600,
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
     *
     * @throws \Exception
     */
    private function createValidClientAssertionSignedAndEncryptedByTheClient(): string
    {
        $token = $this->createValidClientAssertionSignedByTheClient();

        return $this->encryptAssertion($token);
    }

    /**
     * @return string
     *
     * @throws \Exception
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

    /**
     * @return string
     *
     * @throws \Exception
     */
    private function createInvalidClientAssertionSignedAndEncryptedByTheClient(): string
    {
        $token = $this->createInvalidClientAssertionSignedByTheClient();

        return $this->encryptAssertion($token);
    }

    /**
     * @param string $assertion
     *
     * @return string
     *
     * @throws \Exception
     */
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
}
