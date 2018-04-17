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

namespace OAuth2Framework\Component\AuthorizationEndpoint;

use Base64Url\Base64Url;
use Http\Client\HttpClient;
use Jose\Component\Checker\ClaimCheckerManager;
use Jose\Component\Core\Converter\StandardConverter;
use Jose\Component\Core\JWK;
use Jose\Component\Core\JWKSet;
use Jose\Component\Encryption\JWELoader;
use Jose\Component\KeyManagement\JKUFactory;
use Jose\Component\Signature\JWS;
use Jose\Component\Signature\JWSVerifier;
use Jose\Component\Signature\Serializer\CompactSerializer;
use OAuth2Framework\Component\Core\Client\Client;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\Client\ClientRepository;
use OAuth2Framework\Component\Core\Exception\OAuth2Exception;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Request;

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
    private $keyEncryptionKeySet = null;

    /**
     * @var bool
     */
    private $requireRequestUriRegistration = true;

    /**
     * @var bool
     */
    private $requireEncryption = false;

    /**
     * @var null|HttpClient
     */
    private $client = null;

    /**
     * @var JWSVerifier
     */
    private $jwsVerifier = null;

    /**
     * @var ClaimCheckerManager
     */
    private $claimCheckerManager = null;

    /**
     * @var JWELoader
     */
    private $jweLoader = null;

    /**
     * @var null|JKUFactory
     */
    private $jkuFactory = null;

    /**
     * AuthorizationRequestLoader constructor.
     *
     * @param ClientRepository $clientRepository
     */
    public function __construct(ClientRepository $clientRepository)
    {
        $this->clientRepository = $clientRepository;
    }

    /**
     * @return bool
     */
    public function isRequestUriRegistrationRequired(): bool
    {
        return $this->requireRequestUriRegistration;
    }

    /**
     * @return bool
     */
    public function isRequestObjectSupportEnabled(): bool
    {
        return $this->requestObjectAllowed;
    }

    /**
     * @return bool
     */
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

    /**
     * @param JWSVerifier         $jwsVerifier
     * @param ClaimCheckerManager $claimCheckerManager
     */
    public function enableSignedRequestObjectSupport(JWSVerifier $jwsVerifier, ClaimCheckerManager $claimCheckerManager)
    {
        $this->jwsVerifier = $jwsVerifier;
        $this->claimCheckerManager = $claimCheckerManager;
        $this->requestObjectAllowed = true;
    }

    /**
     * @param HttpClient $client
     * @param bool       $requireRequestUriRegistration
     */
    public function enableRequestObjectReferenceSupport(HttpClient $client, bool $requireRequestUriRegistration)
    {
        if (!$this->isRequestObjectSupportEnabled()) {
            throw new \InvalidArgumentException('Request object support must be enabled first.');
        }
        $this->requestObjectReferenceAllowed = true;
        $this->requireRequestUriRegistration = $requireRequestUriRegistration;
        $this->client = $client;
    }

    /**
     * @param JWELoader $jweLoader
     * @param JWKSet    $keyEncryptionKeySet
     * @param bool      $requireEncryption
     *
     * @throws \InvalidArgumentException
     */
    public function enableEncryptedRequestObjectSupport(JWELoader $jweLoader, JWKSet $keyEncryptionKeySet, bool $requireEncryption)
    {
        if (!$this->isRequestObjectSupportEnabled()) {
            throw new \InvalidArgumentException('Request object support must be enabled first.');
        }
        if (0 === $keyEncryptionKeySet->count()) {
            throw new \InvalidArgumentException('The encryption key set must have at least one key.');
        }
        $this->jweLoader = $jweLoader;
        $this->requireEncryption = $requireEncryption;
        $this->keyEncryptionKeySet = $keyEncryptionKeySet;
    }

    /**
     * @param JKUFactory $jkuFactory
     */
    public function enableJkuSupport(JKUFactory $jkuFactory)
    {
        $this->jkuFactory = $jkuFactory;
    }

    /**
     * @return bool
     */
    public function isEncryptedRequestSupportEnabled(): bool
    {
        return null !== $this->keyEncryptionKeySet;
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return Authorization
     *
     * @throws OAuth2Exception
     * @throws \Exception
     * @throws \Http\Client\Exception
     */
    public function load(ServerRequestInterface $request): Authorization
    {
        $client = null;
        $params = $request->getQueryParams();
        if (array_key_exists('request', $params)) {
            $params = $this->createFromRequestParameter($params, $client);
        } elseif (array_key_exists('request_uri', $params)) {
            $params = $this->createFromRequestUriParameter($params, $client);
        } else {
            $client = $this->getClient($params);
        }

        return Authorization::create($client, $params);
    }

    /**
     * @param array  $params
     * @param Client $client
     *
     * @throws OAuth2Exception
     *
     * @return array
     */
    private function createFromRequestParameter(array $params, Client &$client = null): array
    {
        if (false === $this->isRequestObjectSupportEnabled()) {
            throw new OAuth2Exception(400, OAuth2Exception::ERROR_REQUEST_NOT_SUPPORTED, 'The parameter "request" is not supported.');
        }
        $request = $params['request'];
        if (!is_string($request)) {
            throw new OAuth2Exception(400, OAuth2Exception::ERROR_REQUEST_NOT_SUPPORTED, 'The parameter "request" must be an assertion.');
        }

        $params = $this->loadRequestObject($params, $request, $client);
        $this->checkIssuerAndClientId($params);

        return $params;
    }

    /**
     * @param array  $params
     * @param Client $client
     *
     * @return array
     *
     * @throws OAuth2Exception
     * @throws \Exception
     * @throws \Http\Client\Exception
     */
    private function createFromRequestUriParameter(array $params, Client &$client = null): array
    {
        if (false === $this->isRequestObjectReferenceSupportEnabled()) {
            throw new OAuth2Exception(400, OAuth2Exception::ERROR_REQUEST_URI_NOT_SUPPORTED, 'The parameter "request_uri" is not supported.');
        }
        $requestUri = $params['request_uri'];
        if (preg_match('#/\.\.?(/|$)#', $requestUri)) {
            throw new OAuth2Exception(400, OAuth2Exception::ERROR_INVALID_REQUEST_URI, 'The request Uri is not allowed.');
        }
        $content = $this->downloadContent($requestUri);
        $params = $this->loadRequestObject($params, $content, $client);
        $this->checkRequestUri($client, $requestUri);
        $this->checkIssuerAndClientId($params);

        return $params;
    }

    /**
     * @param array $params
     *
     * @throws \InvalidArgumentException
     */
    private function checkIssuerAndClientId(array $params)
    {
        if (array_key_exists('iss', $params) && array_key_exists('client_id', $params)) {
            if ($params['iss'] !== $params['client_id']) {
                throw new \InvalidArgumentException('The issuer of the request object is not the client who requests the authorization.');
            }
        }
    }

    /**
     * @param Client $client
     * @param string $requestUri
     *
     * @throws OAuth2Exception
     */
    private function checkRequestUri(Client $client, $requestUri)
    {
        $storedRequestUris = $client->has('request_uris') ? $client->get('request_uris') : [];
        if (empty($storedRequestUris)) {
            if ($this->isRequestUriRegistrationRequired()) {
                throw new OAuth2Exception(400, OAuth2Exception::ERROR_INVALID_REQUEST_URI, 'The clients shall register at least one request object uri.');
            }

            return;
        }

        foreach ($storedRequestUris as $storedRequestUri) {
            if (0 === strcasecmp(mb_substr($requestUri, 0, mb_strlen($storedRequestUri, '8bit'), '8bit'), $storedRequestUri)) {
                return;
            }
        }

        throw new OAuth2Exception(400, OAuth2Exception::ERROR_INVALID_REQUEST_URI, 'The request Uri is not allowed.');
    }

    /**
     * @param array       $params
     * @param string      $request
     * @param Client|null $client
     *
     * @throws OAuth2Exception
     *
     * @return array
     */
    private function loadRequestObject(array $params, string $request, Client &$client = null): array
    {
        // FIXME Can be
        // - encrypted (not supported)
        // - encrypted and signed (supported)
        // - signed (supported)
        $request = $this->tryToLoadEncryptedRequest($request);

        try {
            $jsonConverter = new StandardConverter();
            $serializer = new CompactSerializer($jsonConverter);
            $jwt = $serializer->unserialize($request);

            $claims = $jsonConverter->decode(
                $jwt->getPayload()
            );
            if (!is_array($claims)) {
                throw new \InvalidArgumentException('Invalid assertion. The payload must contain claims.');
            }
            $this->claimCheckerManager->check($claims);
            $parameters = array_merge($params, $claims);
            $client = $this->getClient($parameters);

            $public_key_set = $this->getClientKeySet($client);
            $this->checkAlgorithms($jwt, $client);
            if (!$this->jwsVerifier->verifyWithKeySet($jwt, $public_key_set, 0)) { //FIXME: header checker should be used
                throw new \InvalidArgumentException('The verification of the request object failed.');
            }
        } catch (OAuth2Exception $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new OAuth2Exception(400, OAuth2Exception::ERROR_INVALID_REQUEST_OBJECT, $e->getMessage(), $e);
        }

        return $parameters;
    }

    /**
     * @param string $request
     *
     * @return string
     *
     * @throws OAuth2Exception
     */
    private function tryToLoadEncryptedRequest(string $request): string
    {
        if (null === $this->jweLoader) {
            return $request;
        }

        try {
            $jwe = $this->jweLoader->loadAndDecryptWithKeySet($request, $this->keyEncryptionKeySet, $recipient);
            if (1 !== $jwe->countRecipients()) {
                throw new \InvalidArgumentException('The request must use the compact serialization mode.');
            }

            return $jwe->getPayload();
        } catch (\Exception $e) {
            if (true === $this->requireEncryption) {
                throw new OAuth2Exception(400, OAuth2Exception::ERROR_INVALID_REQUEST_OBJECT, $e->getMessage(), $e);
            }

            return $request;
        }
    }

    /**
     * @param JWS    $jws
     * @param Client $client
     *
     * @throws \InvalidArgumentException
     */
    private function checkAlgorithms(JWS $jws, Client $client)
    {
        if ($client->has('request_object_signing_alg') && $jws->getSignature(0)->getProtectedHeaderParameter('alg') !== $client->get('request_object_signing_alg')) {
            throw new \InvalidArgumentException('Request Object signature algorithm not allowed for the client.');
        }

        $this->checkUsedAlgorithm($jws->getSignature(0)->getProtectedHeaderParameter('alg'));
    }

    /**
     * @param string $algorithm
     */
    private function checkUsedAlgorithm(string $algorithm)
    {
        $supportedAlgorithms = $this->getSupportedSignatureAlgorithms();
        if (!in_array($algorithm, $supportedAlgorithms)) {
            throw new \InvalidArgumentException(sprintf('The algorithm "%s" is not allowed for request object signatures. Please use one of the following algorithm(s): %s', $algorithm, implode(', ', $supportedAlgorithms)));
        }
    }

    /**
     * @param string $url
     *
     * @return string
     *
     * @throws OAuth2Exception
     * @throws \Exception
     * @throws \Http\Client\Exception
     */
    private function downloadContent(string $url): string
    {
        $request = new Request($url, 'GET');
        $response = $this->client->sendRequest($request);
        if (200 !== $response->getStatusCode()) {
            throw new \InvalidArgumentException();
        }

        $content = $response->getBody()->getContents();
        if (!is_string($content)) {
            throw new OAuth2Exception(400, OAuth2Exception::ERROR_INVALID_REQUEST_URI, 'Unable to get content.');
        }

        return $content;
    }

    /**
     * @param array $params
     *
     * @throws OAuth2Exception
     *
     * @return Client
     */
    private function getClient(array $params): Client
    {
        $client = array_key_exists('client_id', $params) ? $this->clientRepository->find(ClientId::create($params['client_id'])) : null;
        if (!$client instanceof Client || true === $client->isDeleted()) {
            throw new OAuth2Exception(400, OAuth2Exception::ERROR_INVALID_REQUEST, 'Parameter "client_id" missing or invalid.');
        }

        return $client;
    }

    /**
     * @param Client $client
     *
     * @return JWKSet
     */
    private function getClientKeySet(Client $client): JWKSet
    {
        switch (true) {
            case $client->has('jwks') && 'private_key_jwt' === $client->getTokenEndpointAuthenticationMethod():
                return JWKSet::createFromJson($client->get('jwks'));
            case $client->has('client_secret') && 'client_secret_jwt' === $client->getTokenEndpointAuthenticationMethod():
                $jwk = JWK::create([
                    'kty' => 'oct',
                    'use' => 'sig',
                    'k' => Base64Url::encode($client->get('client_secret')),
                ]);

                return JWKSet::createFromKeys([$jwk]);
            case $client->has('jwks_uri') && 'private_key_jwt' === $client->getTokenEndpointAuthenticationMethod() && null !== $this->jkuFactory:
                return $this->jkuFactory->loadFromUrl($client->get('jwks_uri'));
            default:
                throw new \InvalidArgumentException('The client has no key or key set.');
        }
    }
}
