<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2019 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\Component\AuthorizationEndpoint\Tests\AuthorizationRequest;

use Http\Mock\Client;
use Jose\Component\Checker\ClaimCheckerManager;
use Jose\Component\Core\AlgorithmManager;
use Jose\Component\Core\JWKSet;
use Jose\Component\Signature\JWS;
use Jose\Component\Signature\JWSVerifier;
use Nyholm\Psr7\Factory\Psr17Factory;
use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationRequest\AuthorizationRequestLoader;
use OAuth2Framework\Component\Core\Client\ClientRepository;
use OAuth2Framework\Component\Core\Message\OAuth2Error;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\Http\Message\RequestInterface;

/**
 * @group AuthorizationEndpoint
 * @group AuthorizationRequest
 *
 * @internal
 * @coversNothing
 */
final class AuthorizationRequestLoaderTest extends TestCase
{
    /**
     * @var null|AuthorizationRequestLoader
     */
    private $authorizationRequestLoader;

    /**
     * @test
     */
    public function basicCalls()
    {
        $clientRepository = $this->prophesize(ClientRepository::class);
        $loader = $this->getAuthorizationRequestLoader(
            $clientRepository->reveal()
        );
        static::assertEquals([], $loader->getSupportedKeyEncryptionAlgorithms());
        static::assertEquals([], $loader->getSupportedContentEncryptionAlgorithms());
        static::assertEquals(['RS256'], $loader->getSupportedSignatureAlgorithms());
        static::assertTrue($loader->isRequestObjectSupportEnabled());
        static::assertTrue($loader->isRequestUriRegistrationRequired());
        static::assertTrue($loader->isRequestObjectReferenceSupportEnabled());
        static::assertFalse($loader->isEncryptedRequestSupportEnabled());

        //$this->authorizationRequestLoader->enableEncryptedRequestObjectSupport($jweLoader, $keyset, false);
    }

    /**
     * @test
     */
    public function theAuthorizationRequestMustContainAClientId()
    {
        $clientRepository = $this->prophesize(ClientRepository::class);

        try {
            $this->getAuthorizationRequestLoader($clientRepository->reveal())->load([]);
            static::fail('The expected exception has not been thrown.');
        } catch (OAuth2Error $e) {
            static::assertEquals(400, $e->getCode());
            static::assertEquals('invalid_request', $e->getMessage());
            static::assertEquals('Parameter "client_id" missing or invalid.', $e->getErrorDescription());
        }
    }

    /**
     * @test
     */
    public function theClientDoesNotExist()
    {
        $clientRepository = $this->prophesize(ClientRepository::class);
        $clientRepository->find(Argument::any())->willReturn(null)->shouldBeCalled();

        try {
            $this->getAuthorizationRequestLoader($clientRepository->reveal())->load([
                'client_id' => 'BAD_CLIENT_ID',
            ]);
            static::fail('The expected exception has not been thrown.');
        } catch (OAuth2Error $e) {
            static::assertEquals(400, $e->getCode());
            static::assertEquals('invalid_request', $e->getMessage());
            static::assertEquals('Parameter "client_id" missing or invalid.', $e->getErrorDescription());
        }
    }

    /**
     * @test
     */
    public function theClientIsDeleted()
    {
        $client = $this->prophesize(\OAuth2Framework\Component\Core\Client\Client::class);
        $client->isDeleted()->willReturn(true)->shouldBeCalled();

        $clientRepository = $this->prophesize(ClientRepository::class);
        $clientRepository->find(Argument::any())->willReturn($client->reveal())->shouldBeCalled();

        try {
            $this->getAuthorizationRequestLoader($clientRepository->reveal())->load([
                'client_id' => 'DELETED_CLIENT_ID',
            ]);
            static::fail('The expected exception has not been thrown.');
        } catch (OAuth2Error $e) {
            static::assertEquals(400, $e->getCode());
            static::assertEquals('invalid_request', $e->getMessage());
            static::assertEquals('Parameter "client_id" missing or invalid.', $e->getErrorDescription());
        }
    }

    /**
     * @test
     */
    public function theClientExistsAndTheParametersAreReturned()
    {
        $client = $this->prophesize(\OAuth2Framework\Component\Core\Client\Client::class);
        $client->isDeleted()->willReturn(false)->shouldBeCalled();

        $clientRepository = $this->prophesize(ClientRepository::class);
        $clientRepository->find(Argument::any())->willReturn($client->reveal())->shouldBeCalled();

        $authorization = $this->getAuthorizationRequestLoader($clientRepository->reveal())->load([
            'client_id' => 'CLIENT_ID',
        ]);

        static::assertTrue($authorization->hasQueryParam('client_id'));
        static::assertEquals('CLIENT_ID', $authorization->getQueryParam('client_id'));
        static::assertInstanceOf(\OAuth2Framework\Component\Core\Client\Client::class, $authorization->getClient());
    }

    /**
     * @test
     */
    public function theRequestObjectIsNotAValidAssertion()
    {
        $clientRepository = $this->prophesize(ClientRepository::class);

        try {
            $this->getAuthorizationRequestLoader($clientRepository->reveal())->load([
                'request' => 'INVALID_ASSERTION',
            ]);
            static::fail('The expected exception has not been thrown.');
        } catch (OAuth2Error $e) {
            static::assertEquals(400, $e->getCode());
            static::assertEquals('invalid_request_object', $e->getMessage());
            static::assertEquals('Unsupported input', $e->getErrorDescription());
        }
    }

    /**
     * @test
     */
    public function theRequestObjectPayloadDoesNotContainClaims()
    {
        $clientRepository = $this->prophesize(ClientRepository::class);

        try {
            $this->getAuthorizationRequestLoader($clientRepository->reveal())->load([
                'request' => 'eyJhbGciOiJub25lIn0.SEVMTE8gV09STEQh.',
            ]);
            static::fail('The expected exception has not been thrown.');
        } catch (OAuth2Error $e) {
            static::assertEquals(400, $e->getCode());
            static::assertEquals('invalid_request_object', $e->getMessage());
            static::assertEquals('Invalid assertion. The payload must contain claims.', $e->getErrorDescription());
        }
    }

    /**
     * @test
     */
    public function theRequestObjectParametersDoesNotContainAClientId()
    {
        $clientRepository = $this->prophesize(ClientRepository::class);

        try {
            $this->getAuthorizationRequestLoader($clientRepository->reveal())->load([
                'request' => 'eyJhbGciOiJub25lIn0.e30.',
            ]);
            static::fail('The expected exception has not been thrown.');
        } catch (OAuth2Error $e) {
            static::assertEquals(400, $e->getCode());
            static::assertEquals('invalid_request', $e->getMessage());
            static::assertEquals('Parameter "client_id" missing or invalid.', $e->getErrorDescription());
        }
    }

    /**
     * @test
     */
    public function theClientHasNoPublicKeysAndCannotUseTheRequestObjectParameters()
    {
        $client = $this->prophesize(\OAuth2Framework\Component\Core\Client\Client::class);
        $client->isDeleted()->willReturn(false)->shouldBeCalled();
        $client->has('jwks')->willReturn(false)->shouldBeCalled();
        $client->has('jwks_uri')->willReturn(false)->shouldBeCalled();
        $client->has('client_secret')->willReturn(false)->shouldBeCalled();
        $client->has('request_object_signing_alg')->willReturn(false)->shouldNotBeCalled();

        $clientRepository = $this->prophesize(ClientRepository::class);
        $clientRepository->find(Argument::any())->willReturn($client->reveal())->shouldBeCalled();

        try {
            $this->getAuthorizationRequestLoader($clientRepository->reveal())->load([
                'request' => 'eyJhbGciOiJub25lIn0.eyJjbGllbnRfaWQiOiJOT19LRVlfQ0xJRU5UX0lEIn0.',
            ]);
            static::fail('The expected exception has not been thrown.');
        } catch (OAuth2Error $e) {
            static::assertEquals(400, $e->getCode());
            static::assertEquals('invalid_request_object', $e->getMessage());
            static::assertEquals('The client has no key or key set.', $e->getErrorDescription());
        }
    }

    /**
     * @test
     */
    public function theClientDidNotReferencedAnySignatureAlgorithmAndTheUsedAlgorithmIsNotSupported()
    {
        $client = $this->prophesize(\OAuth2Framework\Component\Core\Client\Client::class);
        $client->isDeleted()->willReturn(false)->shouldBeCalled();
        $client->has('jwks')->willReturn(false)->shouldBeCalled();
        $client->has('jwks_uri')->willReturn(false)->shouldBeCalled();
        $client->has('client_secret')->willReturn(true)->shouldBeCalled();
        $client->get('client_secret')->willReturn('SECRET')->shouldBeCalled();
        $client->has('request_object_signing_alg')->willReturn(false);
        $client->getTokenEndpointAuthenticationMethod()->willReturn('client_secret_jwt');

        $clientRepository = $this->prophesize(ClientRepository::class);
        $clientRepository->find(Argument::any())->willReturn($client->reveal())->shouldBeCalled();

        try {
            $this->getAuthorizationRequestLoader($clientRepository->reveal())->load([
                'request' => 'eyJhbGciOiJub25lIn0.eyJjbGllbnRfaWQiOiJDTElFTlRfSUQifQ.',
            ]);
            static::fail('The expected exception has not been thrown.');
        } catch (OAuth2Error $e) {
            static::assertEquals(400, $e->getCode());
            static::assertEquals('invalid_request_object', $e->getMessage());
            static::assertEquals('The algorithm "none" is not allowed for request object signatures. Please use one of the following algorithm(s): RS256', $e->getErrorDescription());
        }
    }

    /**
     * @test
     */
    public function theSignatureAlgorithmIsNotAllowedForThatClient()
    {
        $client = $this->prophesize(\OAuth2Framework\Component\Core\Client\Client::class);
        $client->isDeleted()->willReturn(false)->shouldBeCalled();
        $client->has('jwks')->willReturn(false)->shouldBeCalled();
        $client->has('jwks_uri')->willReturn(false)->shouldBeCalled();
        $client->has('client_secret')->willReturn(true)->shouldBeCalled();
        $client->get('client_secret')->willReturn('SECRET')->shouldBeCalled();
        $client->has('request_object_signing_alg')->willReturn(true)->shouldBeCalled();
        $client->get('request_object_signing_alg')->willReturn('RS256')->shouldBeCalled();
        $client->getTokenEndpointAuthenticationMethod()->willReturn('client_secret_jwt');

        $clientRepository = $this->prophesize(ClientRepository::class);
        $clientRepository->find(Argument::any())->willReturn($client->reveal())->shouldBeCalled();

        try {
            $this->getAuthorizationRequestLoader($clientRepository->reveal())->load([
                'request' => 'eyJhbGciOiJub25lIn0.eyJjbGllbnRfaWQiOiJDTElFTlRfSUQifQ.',
            ]);
            static::fail('The expected exception has not been thrown.');
        } catch (OAuth2Error $e) {
            static::assertEquals(400, $e->getCode());
            static::assertEquals('invalid_request_object', $e->getMessage());
            static::assertEquals('Request Object signature algorithm not allowed for the client.', $e->getErrorDescription());
        }
    }

    /**
     * @test
     */
    public function theSignatureVerificationFailed()
    {
        $client = $this->prophesize(\OAuth2Framework\Component\Core\Client\Client::class);
        $client->isDeleted()->willReturn(false)->shouldBeCalled();
        $client->has('jwks')->willReturn(false)->shouldBeCalled();
        $client->has('jwks_uri')->willReturn(false)->shouldBeCalled();
        $client->has('client_secret')->willReturn(true)->shouldBeCalled();
        $client->get('client_secret')->willReturn('SECRET')->shouldBeCalled();
        $client->has('request_object_signing_alg')->willReturn(true)->shouldBeCalled();
        $client->get('request_object_signing_alg')->willReturn('RS256')->shouldBeCalled();
        $client->getTokenEndpointAuthenticationMethod()->willReturn('client_secret_jwt');

        $clientRepository = $this->prophesize(ClientRepository::class);
        $clientRepository->find(Argument::any())->willReturn($client->reveal())->shouldBeCalled();

        try {
            $this->getAuthorizationRequestLoader($clientRepository->reveal())->load([
                'request' => 'eyJhbGciOiJSUzI1NiJ9.eyJjbGllbnRfaWQiOiJDTElFTlRfSUQifQ.',
            ]);
            static::fail('The expected exception has not been thrown.');
        } catch (OAuth2Error $e) {
            static::assertEquals(400, $e->getCode());
            static::assertEquals('invalid_request_object', $e->getMessage());
            static::assertEquals('The verification of the request object failed.', $e->getErrorDescription());
        }
    }

    /**
     * @test
     */
    public function theAssertionIsVerified()
    {
        $client = $this->prophesize(\OAuth2Framework\Component\Core\Client\Client::class);
        $client->isDeleted()->willReturn(false)->shouldBeCalled();
        $client->has('jwks')->willReturn(false)->shouldBeCalled();
        $client->has('jwks_uri')->willReturn(false)->shouldBeCalled();
        $client->has('client_secret')->willReturn(true)->shouldBeCalled();
        $client->get('client_secret')->willReturn('SECRET')->shouldBeCalled();
        $client->has('request_object_signing_alg')->willReturn(true)->shouldBeCalled();
        $client->get('request_object_signing_alg')->willReturn('RS256')->shouldBeCalled();
        $client->getTokenEndpointAuthenticationMethod()->willReturn('client_secret_jwt');

        $clientRepository = $this->prophesize(ClientRepository::class);
        $clientRepository->find(Argument::any())->willReturn($client->reveal())->shouldBeCalled();

        $authorization = $this->getAuthorizationRequestLoader($clientRepository->reveal())->load([
            'request' => 'eyJhbGciOiJSUzI1NiJ9.eyJjbGllbnRfaWQiOiJDTElFTlRfSUQifQ.R09PRF9TSUdOQVRVUkU',
        ]);

        static::assertTrue($authorization->hasQueryParam('client_id'));
        static::assertTrue($authorization->hasQueryParam('request'));
        static::assertEquals('CLIENT_ID', $authorization->getQueryParam('client_id'));
        static::assertEquals('eyJhbGciOiJSUzI1NiJ9.eyJjbGllbnRfaWQiOiJDTElFTlRfSUQifQ.R09PRF9TSUdOQVRVUkU', $authorization->getQueryParam('request'));
        static::assertInstanceOf(\OAuth2Framework\Component\Core\Client\Client::class, $authorization->getClient());
    }

    /**
     * @test
     */
    public function theRequestObjectUriIsVerified()
    {
        $client = $this->prophesize(\OAuth2Framework\Component\Core\Client\Client::class);
        $client->isDeleted()->willReturn(false)->shouldBeCalled();
        $client->has('jwks')->willReturn(false)->shouldBeCalled();
        $client->has('jwks_uri')->willReturn(false)->shouldBeCalled();
        $client->has('client_secret')->willReturn(true)->shouldBeCalled();
        $client->get('client_secret')->willReturn('SECRET')->shouldBeCalled();
        $client->has('request_object_signing_alg')->willReturn(true)->shouldBeCalled();
        $client->get('request_object_signing_alg')->willReturn('RS256')->shouldBeCalled();
        $client->has('request_uris')->willReturn(true)->shouldBeCalled();
        $client->get('request_uris')->willReturn(['https://www.foo.bar/'])->shouldBeCalled();
        $client->getTokenEndpointAuthenticationMethod()->willReturn('client_secret_jwt');

        $clientRepository = $this->prophesize(ClientRepository::class);
        $clientRepository->find(Argument::any())->willReturn($client->reveal())->shouldBeCalled();

        $authorization = $this->getAuthorizationRequestLoader($clientRepository->reveal())->load([
            'request_uri' => 'https://www.foo.bar/eyJhbGciOiJSUzI1NiJ9.eyJjbGllbnRfaWQiOiJDTElFTlRfSUQifQ.R09PRF9TSUdOQVRVUkU',
        ]);

        static::assertTrue($authorization->hasQueryParam('client_id'));
        static::assertTrue($authorization->hasQueryParam('request_uri'));
        static::assertEquals('CLIENT_ID', $authorization->getQueryParam('client_id'));
        static::assertEquals('https://www.foo.bar/eyJhbGciOiJSUzI1NiJ9.eyJjbGllbnRfaWQiOiJDTElFTlRfSUQifQ.R09PRF9TSUdOQVRVUkU', $authorization->getQueryParam('request_uri'));
        static::assertInstanceOf(\OAuth2Framework\Component\Core\Client\Client::class, $authorization->getClient());
    }

    /**
     * @test
     */
    public function theRequestUriIsNotAllowedForTheClient()
    {
        $client = $this->prophesize(\OAuth2Framework\Component\Core\Client\Client::class);
        $client->isDeleted()->willReturn(false)->shouldBeCalled();
        $client->has('jwks')->willReturn(false)->shouldBeCalled();
        $client->has('jwks_uri')->willReturn(false)->shouldBeCalled();
        $client->has('client_secret')->willReturn(true)->shouldBeCalled();
        $client->get('client_secret')->willReturn('SECRET')->shouldBeCalled();
        $client->has('request_object_signing_alg')->willReturn(true)->shouldBeCalled();
        $client->get('request_object_signing_alg')->willReturn('RS256')->shouldBeCalled();
        $client->has('request_uris')->willReturn(true)->shouldBeCalled();
        $client->get('request_uris')->willReturn(['https://www.foo.bar/'])->shouldBeCalled();
        $client->getTokenEndpointAuthenticationMethod()->willReturn('client_secret_jwt');

        $clientRepository = $this->prophesize(ClientRepository::class);
        $clientRepository->find(Argument::any())->willReturn($client->reveal())->shouldBeCalled();

        try {
            $this->getAuthorizationRequestLoader($clientRepository->reveal())->load([
                'request_uri' => 'https://www.bad.host/eyJhbGciOiJSUzI1NiJ9.eyJjbGllbnRfaWQiOiJDTElFTlRfSUQifQ.R09PRF9TSUdOQVRVUkU',
            ]);
            static::fail('The expected exception has not been thrown.');
        } catch (OAuth2Error $e) {
            static::assertEquals(400, $e->getCode());
            static::assertEquals('invalid_request_uri', $e->getMessage());
            static::assertEquals('The request Uri is not allowed.', $e->getErrorDescription());
        }
    }

    /**
     * @test
     */
    public function theRequestUriMustNotContainPathTraversal()
    {
        $clientRepository = $this->prophesize(ClientRepository::class);

        try {
            $this->getAuthorizationRequestLoader($clientRepository->reveal())->load([
                'request_uri' => 'https://www.foo.bar/../eyJhbGciOiJSUzI1NiJ9.eyJjbGllbnRfaWQiOiJDTElFTlRfSUQifQ.R09PRF9TSUdOQVRVUkU',
            ]);
            static::fail('The expected exception has not been thrown.');
        } catch (OAuth2Error $e) {
            static::assertEquals(400, $e->getCode());
            static::assertEquals('invalid_request_uri', $e->getMessage());
            static::assertEquals('The request Uri is not allowed.', $e->getErrorDescription());
        }
    }

    private function getAuthorizationRequestLoader(ClientRepository $clientRepository): AuthorizationRequestLoader
    {
        if (null === $this->authorizationRequestLoader) {
            $this->authorizationRequestLoader = new AuthorizationRequestLoader($clientRepository);
            $this->authorizationRequestLoader->enableSignedRequestObjectSupport(
                $this->getJWSVerifier(),
                $this->getClaimCheckerManager()
            );
            $this->authorizationRequestLoader->enableRequestObjectReferenceSupport(
                $this->getHttpClient(),
                new Psr17Factory(),
                true
            );
        }

        return $this->authorizationRequestLoader;
    }

    private function getJWSVerifier(): JWSVerifier
    {
        $verifier = $this->prophesize(JWSVerifier::class);
        $verifier->getSignatureAlgorithmManager()->willReturn(
            $this->getSignatureAlgorithmManager()
        );
        $verifier->verifyWithKeySet(Argument::type(JWS::class), Argument::type(JWKSet::class), 0, null)->will(function (array $args) {
            return
                'RS256' === ($args[0])->getSignature(0)->getProtectedHeaderParameter('alg') &&
                '' !== ($args[0])->getSignature(0)->getSignature();
        });

        return $verifier->reveal();
    }

    private function getSignatureAlgorithmManager(): AlgorithmManager
    {
        $manager = $this->prophesize(AlgorithmManager::class);
        $manager->list()->willReturn(['RS256']);

        return $manager->reveal();
    }

    private function getClaimCheckerManager(): ClaimCheckerManager
    {
        $manager = $this->prophesize(ClaimCheckerManager::class);
        $manager->check(Argument::any())->will(function (array $args) {
            return $args[0];
        });

        return $manager->reveal();
    }

    private function getHttpClient(): Client
    {
        $client = $this->prophesize(Client::class);
        $client->sendRequest(Argument::type(RequestInterface::class))->will(function (array $args) {
            /** @var Uri $uri */
            $uri = ($args[0])->getUri();
            switch ($uri->getPath()) {
                case '/eyJhbGciOiJSUzI1NiJ9.eyJjbGllbnRfaWQiOiJDTElFTlRfSUQifQ.R09PRF9TSUdOQVRVUkU':
                    $response = new \Nyholm\Psr7\Response();
                    $response->getBody()->write('eyJhbGciOiJSUzI1NiJ9.eyJjbGllbnRfaWQiOiJDTElFTlRfSUQifQ.R09PRF9TSUdOQVRVUkU');
                    $response->getBody()->rewind();

                    return $response;
            }
        });

        return $client->reveal();
    }
}
