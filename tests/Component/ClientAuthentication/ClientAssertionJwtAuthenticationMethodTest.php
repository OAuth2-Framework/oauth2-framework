<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\Component\ClientAuthentication;

use InvalidArgumentException;
use Jose\Component\Checker\ClaimCheckerManager;
use Jose\Component\Checker\HeaderCheckerManager;
use Jose\Component\Core\AlgorithmManager;
use Jose\Component\Core\JWK;
use Jose\Component\Core\JWKSet;
use Jose\Component\Core\Util\JsonConverter;
use Jose\Component\Encryption\Algorithm\ContentEncryption\A256CBCHS512;
use Jose\Component\Encryption\Algorithm\KeyEncryption\RSAOAEP256;
use Jose\Component\Encryption\Compression\CompressionMethodManager;
use Jose\Component\Encryption\Compression\Deflate;
use Jose\Component\Encryption\JWEBuilder;
use Jose\Component\Encryption\JWEDecrypter;
use Jose\Component\Encryption\JWELoader;
use Jose\Component\Encryption\JWETokenSupport;
use Jose\Component\Encryption\Serializer\JWESerializerManager;
use Jose\Component\Signature\Algorithm\HS256;
use Jose\Component\Signature\Algorithm\RS256;
use Jose\Component\Signature\JWS;
use Jose\Component\Signature\JWSBuilder;
use Jose\Component\Signature\JWSTokenSupport;
use Jose\Component\Signature\JWSVerifier;
use Jose\Component\Signature\Serializer\CompactSerializer;
use const JSON_THROW_ON_ERROR;
use OAuth2Framework\Component\ClientAuthentication\ClientAssertionJwt;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\Message\OAuth2Error;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;
use OAuth2Framework\Tests\Component\OAuth2TestCase;
use OAuth2Framework\Tests\TestBundle\Entity\Client;

/**
 * @internal
 */
final class ClientAssertionJwtAuthenticationMethodTest extends OAuth2TestCase
{
    private ?ClientAssertionJwt $method = null;

    /**
     * @test
     */
    public function genericCalls(): void
    {
        $method = $this->getMethod();

        static::assertSame([], $method->getSchemesParameters());
        static::assertSame(['client_secret_jwt', 'private_key_jwt'], $method->getSupportedMethods());
        static::assertSame(['HS256', 'RS256'], $method->getSupportedSignatureAlgorithms());
        static::assertSame([], $method->getSupportedKeyEncryptionAlgorithms());
        static::assertSame([], $method->getSupportedContentEncryptionAlgorithms());
    }

    /**
     * @test
     */
    public function genericCallsWithEncryptionSupport(): void
    {
        $method = $this->getMethodWithEncryptionSupport(false);

        static::assertSame([], $method->getSchemesParameters());
        static::assertSame(['client_secret_jwt', 'private_key_jwt'], $method->getSupportedMethods());
        static::assertSame(['HS256', 'RS256'], $method->getSupportedSignatureAlgorithms());
        static::assertSame(['RSA-OAEP-256'], $method->getSupportedKeyEncryptionAlgorithms());
        static::assertSame(['A256CBC-HS512'], $method->getSupportedContentEncryptionAlgorithms());
    }

    /**
     * @test
     */
    public function theClientIdCannotBeFoundInTheRequest(): void
    {
        $method = $this->getMethod();
        $request = $this->buildRequest('GET', []);

        $clientId = $method->findClientIdAndCredentials($request, $credentials);
        static::assertNull($clientId);
        static::assertNull($credentials);
    }

    /**
     * @test
     */
    public function theClientAssertionTypeIsNotSupported(): void
    {
        $method = $this->getMethod();
        $request = $this->buildRequest('GET', [
            'client_assertion_type' => 'foo',
        ],);

        $clientId = $method->findClientIdAndCredentials($request, $credentials);
        static::assertNull($clientId);
        static::assertNull($credentials);
    }

    /**
     * @test
     */
    public function theClientAssertionIsMissing(): void
    {
        $method = $this->getMethod();
        $request = $this->buildRequest('GET', [
            'client_assertion_type' => 'urn:ietf:params:oauth:client-assertion-type:jwt-bearer',
        ]);

        try {
            $method->findClientIdAndCredentials($request, $credentials);
            static::fail('An OAuth2 exception should be thrown.');
        } catch (OAuth2Error $e) {
            static::assertSame('invalid_request', $e->getMessage());
            static::assertSame('Parameter "client_assertion" is missing.', $e->getErrorDescription());
        }
    }

    /**
     * @test
     */
    public function theClientAssertionIsInvalid(): void
    {
        $method = $this->getMethod();
        $request = $this->buildRequest('GET', [
            'client_assertion_type' => 'urn:ietf:params:oauth:client-assertion-type:jwt-bearer',
            'client_assertion' => 'foo',
        ]);

        try {
            $method->findClientIdAndCredentials($request, $credentials);
            static::fail('An OAuth2 exception should be thrown.');
        } catch (OAuth2Error $e) {
            static::assertSame('invalid_request', $e->getMessage());
            static::assertSame('Unable to load, decrypt or verify the client assertion.', $e->getErrorDescription());
        }
    }

    /**
     * @test
     */
    public function theClientAssertionSignedByTheClientIsInvalidBecauseOfMissingClaims(): void
    {
        $assertion = $this->serializeJWS($this->createInvalidClientAssertionSignedByTheClient());
        $method = $this->getMethod();
        $request = $this->buildRequest('GET', [
            'client_assertion_type' => 'urn:ietf:params:oauth:client-assertion-type:jwt-bearer',
            'client_assertion' => $assertion,
        ]);

        try {
            $method->findClientIdAndCredentials($request, $credentials);
            static::fail('An OAuth2 exception should be thrown.');
        } catch (OAuth2Error $e) {
            static::assertSame('invalid_request', $e->getMessage());
            static::assertSame(
                'The following claim(s) is/are mandatory: "iss, sub, aud, exp".',
                $e->getErrorDescription()
            );
        }
    }

    /**
     * @test
     */
    public function theClientAssertionSignedByTheClientIsRejectedBecauseEncryptionIsMandatory(): void
    {
        $assertion = $this->serializeJWS($this->createInvalidClientAssertionSignedByTheClient());
        $method = $this->getMethodWithEncryptionSupport(true);
        $request = $this->buildRequest('GET', [
            'client_assertion_type' => 'urn:ietf:params:oauth:client-assertion-type:jwt-bearer',
            'client_assertion' => $assertion,
        ]);

        try {
            $method->findClientIdAndCredentials($request, $credentials);
            static::fail('An OAuth2 exception should be thrown.');
        } catch (OAuth2Error $e) {
            static::assertSame('invalid_request', $e->getMessage());
            static::assertSame(
                'The encryption of the assertion is mandatory but the decryption of the assertion failed.',
                $e->getErrorDescription()
            );
        }
    }

    /**
     * @test
     */
    public function theEncryptedClientAssertionSignedAndEncryptedByTheClientIsInvalidBecauseOfMissingClaims(): void
    {
        $assertion = $this->encryptAssertion(
            $this->serializeJWS($this->createInvalidClientAssertionSignedByTheClient())
        );
        $method = $this->getMethodWithEncryptionSupport(false);
        $request = $this->buildRequest('GET', [
            'client_assertion_type' => 'urn:ietf:params:oauth:client-assertion-type:jwt-bearer',
            'client_assertion' => $assertion,
        ]);

        try {
            $method->findClientIdAndCredentials($request, $credentials);
            static::fail('An OAuth2 exception should be thrown.');
        } catch (OAuth2Error $e) {
            static::assertSame('invalid_request', $e->getMessage());
            static::assertSame(
                'The following claim(s) is/are mandatory: "iss, sub, aud, exp".',
                $e->getErrorDescription()
            );
        }
    }

    /**
     * @test
     */
    public function theClientAssertionIsValidAndTheClientIdIsRetrieved(): void
    {
        $assertion = $this->serializeJWS($this->createValidClientAssertionSignedByTheClient());
        $method = $this->getMethod();
        $request = $this->buildRequest('GET', [
            'client_assertion_type' => 'urn:ietf:params:oauth:client-assertion-type:jwt-bearer',
            'client_assertion' => $assertion,
        ]);
        $clientId = $method->findClientIdAndCredentials($request, $credentials);
        static::assertSame('ClientId', $clientId->getValue());
    }

    /**
     * @test
     */
    public function theEncryptedClientAssertionIsValidAndTheClientIdIsRetrieved(): void
    {
        $assertion = $this->encryptAssertion(
            $this->serializeJWS($this->createValidClientAssertionSignedByTheClient())
        );
        $method = $this->getMethodWithEncryptionSupport(false);
        $request = $this->buildRequest(
            'GET',
            [
                'client_assertion_type' => 'urn:ietf:params:oauth:client-assertion-type:jwt-bearer',
                'client_assertion' => $assertion,
            ]
        );

        $clientId = $method->findClientIdAndCredentials($request, $credentials);
        static::assertSame('ClientId', $clientId->getValue());
    }

    /**
     * @test
     */
    public function theClientUsesAnotherAuthenticationMethod(): void
    {
        $jws = $this->createInvalidClientAssertionSignedByTheClient();
        $assertion = $this->encryptAssertion($this->serializeJWS($jws));
        $method = $this->getMethodWithEncryptionSupport(false);
        $manager = $this->getAuthenticationMethodManager();
        $manager->add($method);

        $client = Client::create(
            ClientId::create('CLIENT_ID'),
            DataBag::create([
                'token_endpoint_auth_method' => 'client_secret_post',
            ]),
            UserAccountId::create('USER_ACCOUNT_ID')
        );

        $request = $this->buildRequest('GET', [
            'client_assertion_type' => 'urn:ietf:params:oauth:client-assertion-type:jwt-bearer',
            'client_assertion' => $assertion,
        ]);

        static::assertFalse($manager->isClientAuthenticated($request, $client, $method, $jws));
    }

    /**
     * @test
     */
    public function theClientWithPrivateKeyIsAuthenticated(): void
    {
        $jws = $this->createValidClientAssertionSignedByTheClient();
        $assertion = $this->encryptAssertion($this->serializeJWS($jws));
        $method = $this->getMethodWithEncryptionSupport(false);
        $manager = $this->getAuthenticationMethodManager();
        $manager->add($method);

        $client = Client::create(
            ClientId::create('CLIENT_ID'),
            DataBag::create([
                'token_endpoint_auth_method' => 'private_key_jwt',
                'jwks' => json_decode(
                    '{"keys":[{"kty":"oct","k":"U0VDUkVUU0VDUkVUU0VDUkVUU0VDUkVUU0VDUkVUU0VDUkVUU0VDUkVUU0VDUkVUU0VDUkVUU0VDUkVUU0VDUkVUU0VDUkVUU0VDUkVUU0VDUkVUU0VDUkVUU0VDUkVUU0VDUkVUU0VDUkVU"}]}',
                    true,
                    512,
                    JSON_THROW_ON_ERROR
                ),
            ]),
            UserAccountId::create('USER_ACCOUNT_ID')
        );

        $request = $this->buildRequest('GET', [
            'client_assertion_type' => 'urn:ietf:params:oauth:client-assertion-type:jwt-bearer',
            'client_assertion' => $assertion,
        ]);

        static::assertTrue($manager->isClientAuthenticated($request, $client, $method, $jws));
    }

    /**
     * @test
     */
    public function theClientWithClientSecretIsAuthenticated(): void
    {
        $jws = $this->createValidClientAssertionSignedByTheClient();
        $assertion = $this->encryptAssertion($this->serializeJWS($jws));
        $method = $this->getMethodWithEncryptionSupport(false);
        $manager = $this->getAuthenticationMethodManager();
        $manager->add($method);

        $client = Client::create(
            ClientId::create('CLIENT_ID'),
            DataBag::create([
                'token_endpoint_auth_method' => 'client_secret_jwt',
                'client_secret' => 'SECRETSECRETSECRETSECRETSECRETSECRETSECRETSECRETSECRETSECRETSECRETSECRETSECRETSECRETSECRETSECRETSECRETSECRET',
            ]),
            UserAccountId::create('USER_ACCOUNT_ID')
        );

        $request = $this->buildRequest('GET', [
            'client_assertion_type' => 'urn:ietf:params:oauth:client-assertion-type:jwt-bearer',
            'client_assertion' => $assertion,
        ]);

        static::assertTrue($manager->isClientAuthenticated($request, $client, $method, $jws));
    }

    /**
     * @test
     */
    public function theClientWithTrustedIssuerAssertionIsAuthenticated(): void
    {
        $jws = $this->createValidClientAssertionSignedByATrustedIssuer();
        $assertion = $this->serializeJWS($jws);
        $method = $this->getMethodWithTrustedIssuerSupport();
        $manager = $this->getAuthenticationMethodManager();
        $manager->add($method);

        $client = Client::create(
            ClientId::create('CLIENT_ID'),
            DataBag::create([
                'token_endpoint_auth_method' => 'client_secret_jwt',
            ]),
            UserAccountId::create('USER_ACCOUNT_ID')
        );

        $request = $this->buildRequest('GET', [
            'client_assertion_type' => 'urn:ietf:params:oauth:client-assertion-type:jwt-bearer',
            'client_assertion' => $assertion,
        ]);

        static::assertTrue($manager->isClientAuthenticated($request, $client, $method, $jws));
    }

    /**
     * @test
     */
    public function theClientConfigurationCanBeCheckedWithClientSecretJwt(): void
    {
        $method = $this->getMethod();
        $commandParameters = DataBag::create([
            'token_endpoint_auth_method' => 'client_secret_jwt',
        ]);
        $validatedParameters = $method->checkClientConfiguration($commandParameters, DataBag::create([]));

        static::assertTrue($validatedParameters->has('client_secret'));
        static::assertTrue($validatedParameters->has('client_secret_expires_at'));
    }

    /**
     * @test
     */
    public function theClientConfigurationCannotBeCheckedWithPrivateKeyJwtIfBothJwksAndJwksUriAreSet(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Either the parameter "jwks" or "jwks_uri" must be set.');
        $method = $this->getMethod();
        $commandParameters = DataBag::create([
            'token_endpoint_auth_method' => 'private_key_jwt',
            'jwks' => 'foo',
            'jwks_uri' => 'bar',
        ]);
        $validatedParameters = $method->checkClientConfiguration($commandParameters, DataBag::create([]));

        static::assertTrue($validatedParameters->has('token_endpoint_auth_method'));
        static::assertSame('private_key_jwt', $validatedParameters->get('token_endpoint_auth_method'));
    }

    /**
     * @test
     */
    public function theClientConfigurationCannotBeCheckedWithPrivateKeyJwtIfBothJwksAndJwksUriAreNotSetBecauseTrustedIssuerSupportIsDisabled(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Either the parameter "jwks" or "jwks_uri" must be set.');
        $method = $this->getMethod();
        $commandParameters = DataBag::create([
            'token_endpoint_auth_method' => 'private_key_jwt',
        ]);
        $validatedParameters = $method->checkClientConfiguration($commandParameters, DataBag::create([]));

        static::assertTrue($validatedParameters->has('token_endpoint_auth_method'));
        static::assertSame('private_key_jwt', $validatedParameters->get('token_endpoint_auth_method'));
    }

    /**
     * @test
     */
    public function theClientConfigurationCanBeCheckedWithPrivateKeyJwtIfBothJwksAndJwksUriAreNotSetBecauseTrustedIssuerSupportIsEnabled(): void
    {
        $method = $this->getMethodWithTrustedIssuerSupport();
        $commandParameters = DataBag::create([
            'token_endpoint_auth_method' => 'private_key_jwt',
        ]);
        $validatedParameters = $method->checkClientConfiguration($commandParameters, DataBag::create([]));

        static::assertFalse($validatedParameters->has('jwks'));
        static::assertFalse($validatedParameters->has('jwks_uri'));
    }

    /**
     * @test
     */
    public function theClientConfigurationCannotBeCheckedWithPrivateKeyJwtIfJwksIsNotAValidKeySet(): void
    {
        $method = $this->getMethod();
        $commandParameters = DataBag::create([
            'token_endpoint_auth_method' => 'private_key_jwt',
            'jwks' => 'foo',
        ]);
        $validatedParameters = $method->checkClientConfiguration($commandParameters, DataBag::create([]));
        static::assertTrue($validatedParameters->has('jwks'));
    }

    /**
     * @test
     */
    public function theClientConfigurationCanBeCheckedWithPrivateKeyJwtIfJwksIsValid(): void
    {
        $method = $this->getMethod();
        $commandParameters = DataBag::create([
            'token_endpoint_auth_method' => 'private_key_jwt',
            'jwks' => json_decode(
                '{"keys":[{"kty":"oct","k":"U0VDUkVUU0VDUkVUU0VDUkVUU0VDUkVUU0VDUkVUU0VDUkVUU0VDUkVUU0VDUkVUU0VDUkVUU0VDUkVUU0VDUkVUU0VDUkVUU0VDUkVUU0VDUkVUU0VDUkVUU0VDUkVUU0VDUkVUU0VDUkVU"}]}',
                true
            ),
        ]);
        $validatedParameters = $method->checkClientConfiguration($commandParameters, DataBag::create([]));

        static::assertTrue($validatedParameters->has('jwks'));
        static::assertSame(
            json_decode(
                '{"keys":[{"kty":"oct","k":"U0VDUkVUU0VDUkVUU0VDUkVUU0VDUkVUU0VDUkVUU0VDUkVUU0VDUkVUU0VDUkVUU0VDUkVUU0VDUkVUU0VDUkVUU0VDUkVUU0VDUkVUU0VDUkVUU0VDUkVUU0VDUkVUU0VDUkVUU0VDUkVU"}]}',
                true
            ),
            $validatedParameters->get('jwks')
        );
    }

    /**
     * @test
     */
    public function theClientConfigurationCannotBeCheckedWithPrivateKeyJwtIfJwksUriFactoryIsNotAvailable(): void
    {
        $method = $this->getMethod();
        $commandParameters = DataBag::create([
            'token_endpoint_auth_method' => 'private_key_jwt',
            'jwks_uri' => 'foo',
        ]);
        $validatedParameters = $method->checkClientConfiguration($commandParameters, DataBag::create([]));

        static::assertTrue($validatedParameters->has('jwks_uri'));
    }

    private function getMethod(): ClientAssertionJwt
    {
        if ($this->method === null) {
            $this->method = new ClientAssertionJwt(
                new JWSVerifier(new AlgorithmManager([new HS256(), new RS256()])),
                new HeaderCheckerManager([], [new JWSTokenSupport()]),
                new ClaimCheckerManager([]),
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
                new JWESerializerManager([new \Jose\Component\Encryption\Serializer\CompactSerializer()]),
                new JWEDecrypter(
                    new AlgorithmManager([new RSAOAEP256()]),
                    new AlgorithmManager([new A256CBCHS512()]),
                    new CompressionMethodManager([new Deflate()])
                ),
                new HeaderCheckerManager([], [new JWETokenSupport()])
            ),
            new JWKSet([new JWK([
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
        $method->enableTrustedIssuerSupport($this->getTrustedIssuerRepository());

        return $method;
    }

    private function serializeJWS(JWS $jws): string
    {
        $serializer = new CompactSerializer();

        return $serializer->serialize($jws, 0);
    }

    private function createValidClientAssertionSignedByTheClient(): JWS
    {
        $jwsBuilder = new JWSBuilder(new AlgorithmManager([new HS256()]));

        return $jwsBuilder
            ->create()
            ->withPayload(JsonConverter::encode([
                'iss' => 'ClientId',
                'sub' => 'ClientId',
                'aud' => 'My Server',
                'exp' => time() + 3600,
            ]))
            ->addSignature(
                JWK::createFromJson(
                    '{"kty":"oct","k":"U0VDUkVUU0VDUkVUU0VDUkVUU0VDUkVUU0VDUkVUU0VDUkVUU0VDUkVUU0VDUkVUU0VDUkVUU0VDUkVUU0VDUkVUU0VDUkVUU0VDUkVUU0VDUkVUU0VDUkVUU0VDUkVUU0VDUkVUU0VDUkVU"}'
                ),
                [
                    'alg' => 'HS256',
                ]
            )
            ->build()
        ;
    }

    private function createValidClientAssertionSignedByATrustedIssuer(): JWS
    {
        $jwsBuilder = new JWSBuilder(new AlgorithmManager([new RS256()]));

        return $jwsBuilder
            ->create()
            ->withPayload(JsonConverter::encode([
                'iss' => 'Trusted Issuer #1',
                'sub' => 'ClientId',
                'aud' => 'My Server',
                'exp' => time() + 3600,
            ]))
            ->addSignature($this->getPrivateRsaKey(), [
                'alg' => 'RS256',
            ])
            ->build()
        ;
    }

    private function createInvalidClientAssertionSignedByTheClient(): JWS
    {
        $jwsBuilder = new JWSBuilder(new AlgorithmManager([new HS256()]));

        return $jwsBuilder
            ->create()
            ->withPayload(JsonConverter::encode([]))
            ->addSignature(
                JWK::createFromJson(
                    '{"kty":"oct","k":"U0VDUkVUU0VDUkVUU0VDUkVUU0VDUkVUU0VDUkVUU0VDUkVUU0VDUkVUU0VDUkVUU0VDUkVUU0VDUkVUU0VDUkVUU0VDUkVUU0VDUkVUU0VDUkVUU0VDUkVUU0VDUkVUU0VDUkVUU0VDUkVU"}'
                ),
                [
                    'alg' => 'HS256',
                ]
            )
            ->build()
        ;
    }

    private function encryptAssertion(string $assertion): string
    {
        $jweBuilder = new JWEBuilder(
            new AlgorithmManager([new RSAOAEP256()]),
            new AlgorithmManager([new A256CBCHS512()]),
            new CompressionMethodManager([new Deflate()])
        );
        $jwe = $jweBuilder->create()
            ->withPayload($assertion)
            ->withSharedProtectedHeader([
                'alg' => 'RSA-OAEP-256',
                'enc' => 'A256CBC-HS512',
            ])
            ->addRecipient(new JWK([
                'kty' => 'RSA',
                'kid' => 'samwise.gamgee@hobbiton.example',
                'use' => 'enc',
                'n' => 'wbdxI55VaanZXPY29Lg5hdmv2XhvqAhoxUkanfzf2-5zVUxa6prHRrI4pP1AhoqJRlZfYtWWd5mmHRG2pAHIlh0ySJ9wi0BioZBl1XP2e-C-FyXJGcTy0HdKQWlrfhTm42EW7Vv04r4gfao6uxjLGwfpGrZLarohiWCPnkNrg71S2CuNZSQBIPGjXfkmIy2tl_VWgGnL22GplyXj5YlBLdxXp3XeStsqo571utNfoUTU8E4qdzJ3U1DItoVkPGsMwlmmnJiwA7sXRItBCivR4M5qnZtdw-7v4WuR4779ubDuJ5nalMv2S66-RPcnFAzWSKxtBDnFJJDGIUe7Tzizjg1nms0Xq_yPub_UOlWn0ec85FCft1hACpWG8schrOBeNqHBODFskYpUc2LC5JA2TaPF2dA67dg1TTsC_FupfQ2kNGcE1LgprxKHcVWYQb86B-HozjHZcqtauBzFNV5tbTuB-TpkcvJfNcFLlH3b8mb-H_ox35FjqBSAjLKyoeqfKTpVjvXhd09knwgJf6VKq6UC418_TOljMVfFTWXUxlnfhOOnzW6HSSzD1c9WrCuVzsUMv54szidQ9wf1cYWf3g5qFDxDQKis99gcDaiCAwM3yEBIzuNeeCa5dartHDb1xEB_HcHSeYbghbMjGfasvKn0aZRsnTyC0xhWBlsolZE',
                'e' => 'AQAB',
                'alg' => 'RSA-OAEP-256',
            ]))
            ->build()
        ;

        $serializer = new \Jose\Component\Encryption\Serializer\CompactSerializer();

        return $serializer->serialize($jwe, 0);
    }
}
