<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\Component\AuthorizationEndpoint\AuthorizationRequest;

use Http\Mock\Client as HttpClient;
use Jose\Component\Checker\ClaimCheckerManager;
use Jose\Component\Checker\ExpirationTimeChecker;
use Jose\Component\Checker\IssuedAtChecker;
use Jose\Component\Checker\NotBeforeChecker;
use Jose\Component\Core\AlgorithmManager;
use Jose\Component\Signature\Algorithm\ES256;
use Jose\Component\Signature\Algorithm\HS256;
use Jose\Component\Signature\Algorithm\RS256;
use Jose\Component\Signature\JWSVerifier;
use Jose\Component\Signature\Serializer\CompactSerializer;
use const JSON_THROW_ON_ERROR;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationRequest\AuthorizationRequestLoader;
use OAuth2Framework\Component\Core\Message\OAuth2Error;
use OAuth2Framework\Tests\Component\OAuth2TestCase;
use OAuth2Framework\Tests\TestBundle\Entity\Client;

/**
 * @internal
 */
final class AuthorizationRequestLoaderTest extends OAuth2TestCase
{
    private ?AuthorizationRequestLoader $authorizationRequestLoader = null;

    private ?HttpClient $httpClient = null;

    /**
     * @test
     */
    public function basicCalls(): void
    {
        $loader = $this->getAuthorizationRequestLoader();
        static::assertSame([], $loader->getSupportedKeyEncryptionAlgorithms());
        static::assertSame([], $loader->getSupportedContentEncryptionAlgorithms());
        static::assertSame(['RS256', 'ES256', 'HS256'], $loader->getSupportedSignatureAlgorithms());
        static::assertTrue($loader->isRequestObjectSupportEnabled());
        static::assertTrue($loader->isRequestUriRegistrationRequired());
        static::assertTrue($loader->isRequestObjectReferenceSupportEnabled());
        static::assertFalse($loader->isEncryptedRequestSupportEnabled());
    }

    /**
     * @test
     */
    public function theAuthorizationRequestMustContainAClientId(): void
    {
        try {
            $this->getAuthorizationRequestLoader()
                ->load([])
            ;
            static::fail('The expected exception has not been thrown.');
        } catch (OAuth2Error $e) {
            static::assertSame(400, $e->getCode());
            static::assertSame('invalid_request', $e->getMessage());
            static::assertSame('Parameter "client_id" missing or invalid.', $e->getErrorDescription());
        }
    }

    /**
     * @test
     */
    public function theClientDoesNotExist(): void
    {
        try {
            $this->getAuthorizationRequestLoader()
                ->load([
                    'client_id' => 'BAD_CLIENT_ID',
                ])
            ;
            static::fail('The expected exception has not been thrown.');
        } catch (OAuth2Error $e) {
            static::assertSame(400, $e->getCode());
            static::assertSame('invalid_request', $e->getMessage());
            static::assertSame('Parameter "client_id" missing or invalid.', $e->getErrorDescription());
        }
    }

    /**
     * @test
     */
    public function theClientIsDeleted(): void
    {
        try {
            $this->getAuthorizationRequestLoader()
                ->load([
                    'client_id' => 'DELETED_CLIENT_ID',
                ])
            ;
            static::fail('The expected exception has not been thrown.');
        } catch (OAuth2Error $e) {
            static::assertSame(400, $e->getCode());
            static::assertSame('invalid_request', $e->getMessage());
            static::assertSame('Parameter "client_id" missing or invalid.', $e->getErrorDescription());
        }
    }

    /**
     * @test
     */
    public function theRequestObjectIsNotAValidAssertion(): void
    {
        try {
            $this->getAuthorizationRequestLoader()
                ->load([
                    'request' => 'INVALID_ASSERTION',
                ])
            ;
            static::fail('The expected exception has not been thrown.');
        } catch (OAuth2Error $e) {
            static::assertSame(400, $e->getCode());
            static::assertSame('invalid_request_object', $e->getMessage());
            static::assertSame('Unsupported input', $e->getErrorDescription());
        }
    }

    /**
     * @test
     */
    public function theRequestObjectPayloadDoesNotContainClaims(): void
    {
        try {
            $this->getAuthorizationRequestLoader()
                ->load([
                    'request' => 'eyJhbGciOiJub25lIn0.SEVMTE8gV09STEQh.',
                ])
            ;
            static::fail('The expected exception has not been thrown.');
        } catch (OAuth2Error $e) {
            static::assertSame(400, $e->getCode());
            static::assertSame('invalid_request_object', $e->getMessage());
            static::assertSame('Invalid assertion. The payload must contain claims.', $e->getErrorDescription());
        }
    }

    /**
     * @test
     */
    public function theRequestObjectParametersDoesNotContainAClientId(): void
    {
        try {
            $this->getAuthorizationRequestLoader()
                ->load([
                    'request' => 'eyJhbGciOiJub25lIn0.e30.',
                ])
            ;
            static::fail('The expected exception has not been thrown.');
        } catch (OAuth2Error $e) {
            static::assertSame(400, $e->getCode());
            static::assertSame('invalid_request', $e->getMessage());
            static::assertSame('Parameter "client_id" missing or invalid.', $e->getErrorDescription());
        }
    }

    /**
     * @test
     */
    public function theClientHasNoPublicKeysAndCannotUseTheRequestObjectParameters(): void
    {
        try {
            $this->getAuthorizationRequestLoader()
                ->load([
                    'request' => $this->generateRequestObject([
                        'client_id' => 'PUBLIC_CLIENT_ID',
                    ]),
                ])
            ;
            static::fail('The expected exception has not been thrown.');
        } catch (OAuth2Error $e) {
            static::assertSame(400, $e->getCode());
            static::assertSame('The client has no key or key set.', $e->getErrorDescription());
            static::assertSame('invalid_request_object', $e->getMessage());
        }
    }

    /**
     * @test
     */
    public function theClientDidNotReferencedAnySignatureAlgorithmAndTheUsedAlgorithmIsNotSupported(): void
    {
        try {
            $this->getAuthorizationRequestLoader()
                ->load([
                    'request' => 'eyJhbGciOiJub25lIn0.eyJjbGllbnRfaWQiOiJQUklWQVRFX0tFWV9KV1RfQ0xJRU5UX0lEIn0.',
                ])
            ;
            static::fail('The expected exception has not been thrown.');
        } catch (OAuth2Error $e) {
            static::assertSame(400, $e->getCode());
            static::assertSame('The algorithm "none" is not allowed by the client.', $e->getErrorDescription());
            static::assertSame('invalid_request_object', $e->getMessage());
        }
    }

    /**
     * @test
     */
    public function theSignatureAlgorithmIsNotAllowedForThatClient(): void
    {
        try {
            $this->getAuthorizationRequestLoader()
                ->load([
                    'request' => 'eyJhbGciOiJIUzI1NiJ9.eyJjbGllbnRfaWQiOiJQUklWQVRFX0tFWV9KV1RfQ0xJRU5UX0lEIn0.',
                ])
            ;
            static::fail('The expected exception has not been thrown.');
        } catch (OAuth2Error $e) {
            static::assertSame(400, $e->getCode());
            static::assertSame('The algorithm "HS256" is not allowed by the client.', $e->getErrorDescription());
            static::assertSame('invalid_request_object', $e->getMessage());
        }
    }

    /**
     * @test
     */
    public function theSignatureVerificationFailed(): void
    {
        try {
            $this->getAuthorizationRequestLoader()
                ->load([
                    'request' => 'eyJhbGciOiJSUzI1NiJ9.eyJjbGllbnRfaWQiOiJQUklWQVRFX0tFWV9KV1RfQ0xJRU5UX0lEIn0.',
                ])
            ;
            static::fail('The expected exception has not been thrown.');
        } catch (OAuth2Error $e) {
            static::assertSame(400, $e->getCode());
            static::assertSame('The verification of the request object failed.', $e->getErrorDescription());
            static::assertSame('invalid_request_object', $e->getMessage());
        }
    }

    /**
     * @test
     */
    public function theAssertionIsVerified(): void
    {
        $authorization = $this->getAuthorizationRequestLoader()
            ->load([
                'request' => $this->generateRequestObject([
                    'client_id' => 'PRIVATE_KEY_JWT_CLIENT_ID',
                ]),
            ])
        ;

        static::assertTrue($authorization->hasQueryParam('client_id'));
        static::assertTrue($authorization->hasQueryParam('request'));
        static::assertSame('PRIVATE_KEY_JWT_CLIENT_ID', $authorization->getQueryParam('client_id'));
        static::assertInstanceOf(Client::class, $authorization->getClient());
    }

    /**
     * @test
     */
    public function theRequestObjectUriIsVerified(): void
    {
        $response = new Response();
        $response->getBody()
            ->write($this->generateRequestObject([
                'client_id' => 'PRIVATE_KEY_JWT_CLIENT_ID',
            ]))
        ;
        $response->getBody()
            ->rewind()
        ;
        $this->getHttpClient()
            ->addResponse($response)
        ;

        $authorization = $this->getAuthorizationRequestLoader()
            ->load([
                'request_uri' => 'https://www.foo.bar/eyJhbGciOiJSUzI1NiJ9.eyJjbGllbnRfaWQiOiJDTElFTlRfSUQifQ.R09PRF9TSUdOQVRVUkU',
            ])
        ;

        static::assertTrue($authorization->hasQueryParam('client_id'));
        static::assertTrue($authorization->hasQueryParam('request_uri'));
        static::assertSame('PRIVATE_KEY_JWT_CLIENT_ID', $authorization->getQueryParam('client_id'));
        static::assertSame(
            'https://www.foo.bar/eyJhbGciOiJSUzI1NiJ9.eyJjbGllbnRfaWQiOiJDTElFTlRfSUQifQ.R09PRF9TSUdOQVRVUkU',
            $authorization->getQueryParam('request_uri')
        );
        static::assertInstanceOf(Client::class, $authorization->getClient());
    }

    /**
     * @test
     */
    public function theRequestUriIsNotAllowedForTheClient(): void
    {
        $response = new Response();
        $response->getBody()
            ->write($this->generateRequestObject([
                'client_id' => 'PRIVATE_KEY_JWT_CLIENT_ID',
            ]))
        ;
        $response->getBody()
            ->rewind()
        ;
        $this->getHttpClient()
            ->addResponse($response)
        ;

        try {
            $this->getAuthorizationRequestLoader()
                ->load([
                    'request_uri' => 'https://www.bad.host/FOO-BAR.request',
                ])
            ;
            static::fail('The expected exception has not been thrown.');
        } catch (OAuth2Error $e) {
            static::assertSame(400, $e->getCode());
            static::assertSame('The request Uri is not allowed.', $e->getErrorDescription());
            static::assertSame('invalid_request_uri', $e->getMessage());
        }
    }

    /**
     * @test
     */
    public function theRequestUriMustNotContainPathTraversal(): void
    {
        try {
            $this->getAuthorizationRequestLoader()
                ->load([
                    'request_uri' => 'https://www.foo.bar/../eyJhbGciOiJSUzI1NiJ9.eyJjbGllbnRfaWQiOiJDTElFTlRfSUQifQ.R09PRF9TSUdOQVRVUkU',
                ])
            ;
            static::fail('The expected exception has not been thrown.');
        } catch (OAuth2Error $e) {
            static::assertSame(400, $e->getCode());
            static::assertSame('invalid_request_uri', $e->getMessage());
            static::assertSame('The request Uri is not allowed.', $e->getErrorDescription());
        }
    }

    private function getAuthorizationRequestLoader(): AuthorizationRequestLoader
    {
        if ($this->authorizationRequestLoader === null) {
            $this->authorizationRequestLoader = AuthorizationRequestLoader::create($this->getClientRepository());
            $this->authorizationRequestLoader->enableSignedRequestObjectSupport(
                $this->getJwsVerifier(),
                $this->getClaimCheckerManager()
            );
            $this->authorizationRequestLoader->enableRequestObjectReferenceSupport($this->getHttpClient(), true);
        }

        return $this->authorizationRequestLoader;
    }

    private function getJwsVerifier(): JWSVerifier
    {
        return  new JWSVerifier(new AlgorithmManager([new RS256(), new ES256(), new HS256()]));
    }

    private function getClaimCheckerManager(): ClaimCheckerManager
    {
        return new ClaimCheckerManager([
            new IssuedAtChecker(),
            new NotBeforeChecker(),
            new ExpirationTimeChecker(),
        ]);
    }

    private function getHttpClient(): HttpClient
    {
        if ($this->httpClient === null) {
            $this->httpClient = new HttpClient(new Psr17Factory());
        }

        return $this->httpClient;
    }

    private function generateRequestObject(array $data): string
    {
        $jws = $this->getJwsBuilder()
            ->create()
            ->withPayload(json_encode($data, JSON_THROW_ON_ERROR))
            ->addSignature($this->getPrivateRsaKey(), [
                'alg' => 'RS256',
            ])
            ->build()
        ;

        return (new CompactSerializer())->serialize($jws, 0);
    }
}
