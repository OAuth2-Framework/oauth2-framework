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

namespace OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationRequest;

use Assert\Assertion;
use Base64Url\Base64Url;
use Http\Client\HttpClient;
use Jose\Component\Checker\ClaimCheckerManager;
use Jose\Component\Core\JWK;
use Jose\Component\Core\JWKSet;
use Jose\Component\Core\Util\JsonConverter;
use Jose\Component\Encryption\JWELoader;
use Jose\Component\KeyManagement\JKUFactory;
use Jose\Component\Signature\JWS;
use Jose\Component\Signature\JWSVerifier;
use Jose\Component\Signature\Serializer\CompactSerializer;
use OAuth2Framework\Component\Core\Client\Client;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\Client\ClientRepository;
use OAuth2Framework\Component\Core\Message\OAuth2Error;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;

class AuthorizationRequestLoader
{
    /**
     * @var ClientRepository
     */
    private $clientRepository;

    /**
     * @var bool
     */
    private $requestObjectAllowed = false;

    /**
     * @var bool
     */
    private $requestObjectReferenceAllowed = false;

    /**
     * @var JWKSet
     */
    private $keyEncryptionKeySet;

    /**
     * @var bool
     */
    private $requireRequestUriRegistration = true;

    /**
     * @var bool
     */
    private $requireEncryption = false;

    /**
     * @var HttpClient|null
     */
    private $client;

    /**
     * @var JWSVerifier
     */
    private $jwsVerifier;

    /**
     * @var ClaimCheckerManager
     */
    private $claimCheckerManager;

    /**
     * @var JWELoader
     */
    private $jweLoader;

    /**
     * @var JKUFactory|null
     */
    private $jkuFactory;

    /**
     * @var RequestFactoryInterface
     */
    private $requestFactory;

    public function __construct(ClientRepository $clientRepository)
    {
        $this->clientRepository = $clientRepository;
    }

    public function isRequestUriRegistrationRequired(): bool
    {
        return $this->requireRequestUriRegistration;
    }

    public function isRequestObjectSupportEnabled(): bool
    {
        return $this->requestObjectAllowed;
    }

    public function isRequestObjectReferenceSupportEnabled(): bool
    {
        return $this->requestObjectReferenceAllowed;
    }

    /**
     * @return string[]
     */
    public function getSupportedSignatureAlgorithms(): array
    {
        return null === $this->jwsVerifier ? [] : $this->jwsVerifier->getSignatureAlgorithmManager()->list();
    }

    /**
     * @return string[]
     */
    public function getSupportedKeyEncryptionAlgorithms(): array
    {
        return null === $this->jweLoader ? [] : $this->jweLoader->getJweDecrypter()->getKeyEncryptionAlgorithmManager()->list();
    }

    /**
     * @return string[]
     */
    public function getSupportedContentEncryptionAlgorithms(): array
    {
        return null === $this->jweLoader ? [] : $this->jweLoader->getJweDecrypter()->getContentEncryptionAlgorithmManager()->list();
    }

    public function enableSignedRequestObjectSupport(JWSVerifier $jwsVerifier, ClaimCheckerManager $claimCheckerManager)
    {
        $this->jwsVerifier = $jwsVerifier;
        $this->claimCheckerManager = $claimCheckerManager;
        $this->requestObjectAllowed = true;
    }

    public function enableRequestObjectReferenceSupport(HttpClient $client, RequestFactoryInterface $requestFactory, bool $requireRequestUriRegistration): void
    {
        Assertion::true($this->isRequestObjectSupportEnabled(), 'Request object support must be enabled first.');
        $this->requestObjectReferenceAllowed = true;
        $this->requireRequestUriRegistration = $requireRequestUriRegistration;
        $this->requestFactory = $requestFactory;
        $this->client = $client;
    }

    public function enableEncryptedRequestObjectSupport(JWELoader $jweLoader, JWKSet $keyEncryptionKeySet, bool $requireEncryption): void
    {
        Assertion::true($this->isRequestObjectSupportEnabled(), 'Request object support must be enabled first.');
        Assertion::greaterThan($keyEncryptionKeySet->count(), 0, 'The encryption key set must have at least one key.');
        $this->jweLoader = $jweLoader;
        $this->requireEncryption = $requireEncryption;
        $this->keyEncryptionKeySet = $keyEncryptionKeySet;
    }

    public function enableJkuSupport(JKUFactory $jkuFactory): void
    {
        $this->jkuFactory = $jkuFactory;
    }

    public function isEncryptedRequestSupportEnabled(): bool
    {
        return null !== $this->keyEncryptionKeySet;
    }

    public function load(ServerRequestInterface $request): AuthorizationRequest
    {
        $client = null;
        $params = $request->getQueryParams();
        if (\array_key_exists('request', $params)) {
            $params = $this->createFromRequestParameter($params, $client);
        } elseif (\array_key_exists('request_uri', $params)) {
            $params = $this->createFromRequestUriParameter($params, $client);
        } else {
            $client = $this->getClient($params);
        }

        return new AuthorizationRequest($client, $params);
    }

    private function createFromRequestParameter(array $params, Client &$client = null): array
    {
        if (false === $this->isRequestObjectSupportEnabled()) {
            throw OAuth2Error::requestNotSupported('The parameter "request" is not supported.');
        }
        $request = $params['request'];
        if (!\is_string($request)) {
            throw OAuth2Error::requestNotSupported('The parameter "request" must be an assertion.');
        }

        $params = $this->loadRequestObject($params, $request, $client);
        $this->checkIssuerAndClientId($params);

        return $params;
    }

    private function createFromRequestUriParameter(array $params, Client &$client = null): array
    {
        if (false === $this->isRequestObjectReferenceSupportEnabled()) {
            throw OAuth2Error::requestUriNotSupported('The parameter "request_uri" is not supported.');
        }
        $requestUri = $params['request_uri'];
        if (\Safe\preg_match('#/\.\.?(/|$)#', $requestUri)) {
            throw OAuth2Error::invalidRequestUri('The request Uri is not allowed.');
        }
        $content = $this->downloadContent($requestUri);
        $params = $this->loadRequestObject($params, $content, $client);
        $this->checkRequestUri($client, $requestUri);
        $this->checkIssuerAndClientId($params);

        return $params;
    }

    private function checkIssuerAndClientId(array $params): void
    {
        if (\array_key_exists('iss', $params) && \array_key_exists('client_id', $params)) {
            Assertion::eq($params['iss'], $params['client_id'], 'The issuer of the request object is not the client who requests the authorization.');
        }
    }

    private function checkRequestUri(Client $client, $requestUri)
    {
        $storedRequestUris = $client->has('request_uris') ? $client->get('request_uris') : [];
        if (empty($storedRequestUris)) {
            if ($this->isRequestUriRegistrationRequired()) {
                throw OAuth2Error::invalidRequestUri('The clients shall register at least one request object uri.');
            }

            return;
        }

        foreach ($storedRequestUris as $storedRequestUri) {
            if (0 === \strcasecmp(\mb_substr($requestUri, 0, \mb_strlen($storedRequestUri, '8bit'), '8bit'), $storedRequestUri)) {
                return;
            }
        }

        throw OAuth2Error::invalidRequestUri('The request Uri is not allowed.');
    }

    private function loadRequestObject(array $params, string $request, Client &$client = null): array
    {
        // FIXME Can be
        // - encrypted (not supported)
        // - encrypted and signed (supported)
        // - signed (supported)
        $request = $this->tryToLoadEncryptedRequest($request);

        try {
            $serializer = new CompactSerializer();
            $jwt = $serializer->unserialize($request);

            $claims = JsonConverter::decode(
                $jwt->getPayload()
            );
            Assertion::isArray($claims, 'Invalid assertion. The payload must contain claims.');
            $this->claimCheckerManager->check($claims);
            $parameters = \array_merge($params, $claims);
            $client = $this->getClient($parameters);

            $public_key_set = $this->getClientKeySet($client);
            $this->checkAlgorithms($jwt, $client);
            Assertion::true($this->jwsVerifier->verifyWithKeySet($jwt, $public_key_set, 0), 'The verification of the request object failed.'); //FIXME: header checker should be used

            return $parameters;
        } catch (OAuth2Error $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw OAuth2Error::invalidRequestObject($e->getMessage(), [], $e);
        }
    }

    private function tryToLoadEncryptedRequest(string $request): string
    {
        if (null === $this->jweLoader) {
            return $request;
        }

        try {
            $jwe = $this->jweLoader->loadAndDecryptWithKeySet($request, $this->keyEncryptionKeySet, $recipient);
            Assertion::eq(1, $jwe->countRecipients(), 'The request must use the compact serialization mode.');

            return $jwe->getPayload();
        } catch (OAuth2Error $e) {
            throw $e;
        } catch (\Throwable $e) {
            if (true === $this->requireEncryption) {
                throw OAuth2Error::invalidRequestObject($e->getMessage(), [], $e);
            }

            return $request;
        }
    }

    private function checkAlgorithms(JWS $jws, Client $client): void
    {
        $signatureAlgorithm = $jws->getSignature(0)->getProtectedHeaderParameter('alg');
        Assertion::string($signatureAlgorithm, 'Invalid algorithm parameter in Request Object.');
        if ($client->has('request_object_signing_alg')) {
            Assertion::eq($signatureAlgorithm, $client->get('request_object_signing_alg'), 'Request Object signature algorithm not allowed for the client.');
        }

        $this->checkUsedAlgorithm($signatureAlgorithm);
    }

    private function checkUsedAlgorithm(string $algorithm): void
    {
        $supportedAlgorithms = $this->getSupportedSignatureAlgorithms();
        Assertion::inArray($algorithm, $supportedAlgorithms, \Safe\sprintf('The algorithm "%s" is not allowed for request object signatures. Please use one of the following algorithm(s): %s', $algorithm, \implode(', ', $supportedAlgorithms)));
    }

    private function downloadContent(string $url): string
    {
        $request = $this->requestFactory->createRequest('GET', $url);
        $response = $this->client->sendRequest($request);
        Assertion::eq(200, $response->getStatusCode(), 'Unable to load the request object');

        return $response->getBody()->getContents();
    }

    private function getClient(array $params): Client
    {
        $client = \array_key_exists('client_id', $params) ? $this->clientRepository->find(new ClientId($params['client_id'])) : null;
        if (!$client instanceof Client || true === $client->isDeleted()) {
            throw OAuth2Error::invalidRequest('Parameter "client_id" missing or invalid.');
        }

        return $client;
    }

    private function getClientKeySet(Client $client): JWKSet
    {
        $keyset = JWKSet::createFromKeys([]);
        if ($client->has('jwks')) {
            $jwks = JWKSet::createFromJson($client->get('jwks'));
            foreach ($jwks as $jwk) {
                $keyset = $keyset->with($jwk);
            }
        }
        if ($client->has('client_secret')) {
            $jwk = new JWK([
                'kty' => 'oct',
                'use' => 'sig',
                'k' => Base64Url::encode($client->get('client_secret')),
            ]);
            $keyset = $keyset->with($jwk);
        }
        if ($client->has('jwks_uri') && null !== $this->jkuFactory) {
            $jwks_uri = $this->jkuFactory->loadFromUrl($client->get('jwks_uri'));
            foreach ($jwks_uri as $jwk) {
                $keyset = $keyset->with($jwk);
            }
        }
        if (\in_array('none', $this->getSupportedSignatureAlgorithms(), true)) {
            $keyset = $keyset->with(new JWK([
                'kty' => 'none',
                'alg' => 'none',
                'use' => 'sig',
            ]));
        }

        Assertion::greaterThan($keyset->count(), 0, 'The client has no key or key set.');

        return $keyset;
    }
}
