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

use Http\Client\HttpClient;
use Jose\Component\Checker\ClaimCheckerManager;
use Jose\Component\Core\JWKSet;
use Jose\Component\Encryption\JWELoader;
use Jose\Component\Signature\JWS;
use Jose\Component\Signature\JWSLoader;
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
     * @var string[]
     */
    private $mandatoryClaims = [];

    /**
     * @var null|HttpClient
     */
    private $client = null;

    /**
     * @var JWSLoader
     */
    private $jwsLoader = null;

    /**
     * @var ClaimCheckerManager
     */
    private $claimCheckerManager = null;

    /**
     * @var JWELoader
     */
    private $jweLoader = null;

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
        return null === $this->jwsLoader ? [] : $this->jwsLoader->getSignatureAlgorithmManager()->list();
    }

    /**
     * @return string[]
     */
    public function getSupportedKeyEncryptionAlgorithms(): array
    {
        return null === $this->jweLoader ? [] : $this->jweLoader->getKeyEncryptionAlgorithmManager()->list();
    }

    /**
     * @return string[]
     */
    public function getSupportedContentEncryptionAlgorithms(): array
    {
        return null === $this->jweLoader ? [] : $this->jweLoader->getContentEncryptionAlgorithmManager()->list();
    }

    /**
     * @param JWSLoader           $jwsLoader
     * @param ClaimCheckerManager $claimCheckerManager
     * @param string[]            $mandatoryClaims
     */
    public function enableRequestObjectSupport(JWSLoader $jwsLoader, ClaimCheckerManager $claimCheckerManager, array $mandatoryClaims = [])
    {
        foreach ($mandatoryClaims as $mandatoryClaim) {
            if (!is_string($mandatoryClaim)) {
                throw new \InvalidArgumentException('The mandatory claims array should contain only claims.');
            }
        }
        $this->jwsLoader = $jwsLoader;
        $this->claimCheckerManager = $claimCheckerManager;
        $this->requestObjectAllowed = true;
        $this->mandatoryClaims = $mandatoryClaims;
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
     * @return bool
     */
    public function isEncryptedRequestsSupportEnabled(): bool
    {
        return null !== $this->keyEncryptionKeySet;
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return array
     *
     * @throws OAuth2Exception
     * @throws \Exception
     * @throws \Http\Client\Exception
     */
    public function loadParametersFromRequest(ServerRequestInterface $request): array
    {
        $params = $request->getQueryParams();
        if (array_key_exists('request', $params)) {
            $params = $this->createFromRequestParameter($params);
        } elseif (array_key_exists('request_uri', $params)) {
            $params = $this->createFromRequestUriParameter($params);
        } else {
            $params = $this->createFromStandardRequest($params);
        }

        $client = $params['client'];
        unset($params['client']);

        return [$client, $params];
    }

    /**
     * @param array $params
     *
     * @throws OAuth2Exception
     *
     * @return array
     */
    private function createFromRequestParameter(array $params): array
    {
        if (false === $this->isRequestObjectSupportEnabled()) {
            throw new OAuth2Exception(400, OAuth2Exception::ERROR_REQUEST_NOT_SUPPORTED, 'The parameter "request" is not supported.');
        }
        $request = $params['request'];
        if (!is_string($request)) {
            throw new OAuth2Exception(400, OAuth2Exception::ERROR_REQUEST_NOT_SUPPORTED, 'The parameter "request" must be an assertion.');
        }

        $jws = $this->loadRequest($params, $request, $client);
        $claims = json_decode($jws->getPayload(), true);
        $params = array_merge($params, $claims, ['client' => $client]);
        $this->checkIssuerAndClientId($params);

        return $params;
    }

    /**
     * @param array $params
     *
     * @return array
     *
     * @throws OAuth2Exception
     */
    private function createFromStandardRequest(array $params): array
    {
        $client = $this->getClient($params);

        return array_merge($params, ['client' => $client]);
    }

    /**
     * @param array $params
     *
     * @return array
     *
     * @throws OAuth2Exception
     * @throws \Exception
     * @throws \Http\Client\Exception
     */
    private function createFromRequestUriParameter(array $params): array
    {
        if (false === $this->isRequestObjectReferenceSupportEnabled()) {
            throw new OAuth2Exception(400, OAuth2Exception::ERROR_REQUEST_URI_NOT_SUPPORTED, 'The parameter "request_uri" is not supported.');
        }
        $requestUri = $params['request_uri'];

        $content = $this->downloadContent($requestUri);
        $jws = $this->loadRequest($params, $content, $client);
        if (true === $this->isRequestUriRegistrationRequired()) {
            $this->checkRequestUri($client, $requestUri);
        }
        $claims = json_decode($jws->getPayload(), true);
        $params = array_merge($params, $claims, ['client' => $client]);
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
        $this->checkRequestUriPathTraversal($requestUri);
        $stored_request_uris = $this->getClientRequestUris($client);

        foreach ($stored_request_uris as $stored_request_uri) {
            if (0 === strcasecmp(mb_substr($requestUri, 0, mb_strlen($stored_request_uri, '8bit'), '8bit'), $stored_request_uri)) {
                return;
            }
        }

        throw new OAuth2Exception(400, OAuth2Exception::ERROR_INVALID_REQUEST_URI, 'The request Uri is not allowed.');
    }

    /**
     * @param string $requestUri
     *
     * @throws OAuth2Exception
     */
    private function checkRequestUriPathTraversal($requestUri)
    {
        if (false === Uri::checkUrl($requestUri, false)) {
            throw new OAuth2Exception(400, OAuth2Exception::ERROR_INVALID_CLIENT, 'The request Uri must not contain path traversal.');
        }
    }

    /**
     * @param Client $client
     *
     * @throws OAuth2Exception
     *
     * @return string[]
     */
    private function getClientRequestUris(Client $client): array
    {
        if (false === $client->has('request_uris') || empty($requestUris = $client->get('request_uris'))) {
            throw new OAuth2Exception(400, OAuth2Exception::ERROR_INVALID_CLIENT, 'The client must register at least one request Uri.');
        }

        return $requestUris;
    }

    /**
     * @param array       $params
     * @param string      $request
     * @param Client|null $client
     *
     * @throws OAuth2Exception
     *
     * @return JWS
     */
    private function loadRequest(array $params, string $request, Client &$client = null): JWS
    {
        $request = $this->tryToLoadEncryptedRequest($request);

        try {
            $jwt = $this->jwsLoader->loadAndVerifyWithKeySet($request);
            $this->claimCheckerManager->check($jwt);
            $claims = json_decode($jwt->getPayload(), true);

            $client = $this->getClient(array_merge($params, $claims));
            $public_key_set = $client->getPublicKeySet();
            if (null === $public_key_set) {
                throw new \InvalidArgumentException('The client does not have signature capabilities.');
            }
            $index = $this->jwsLoader->verifyWithKeySet($jwt, $public_key_set);
            $this->checkAlgorithms($jwt, $index, $client);
            $missing_claims = array_keys(array_diff_key(array_flip($this->mandatoryClaims), $claims));
            if (!empty($missing_claims)) {
                throw new \InvalidArgumentException(sprintf('The following mandatory claims are missing: %s.', implode(', ', $missing_claims)));
            }
        } catch (\Exception $e) {
            throw new OAuth2Exception(400, OAuth2Exception::ERROR_INVALID_REQUEST_OBJECT, $e->getMessage(), $e);
        }

        return $jwt;
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
     * @param int    $index
     * @param Client $client
     *
     * @throws \InvalidArgumentException
     */
    private function checkAlgorithms(JWS $jws, int $index, Client $client)
    {
        if (!$client->has('request_object_signing_alg')) {
            throw new \InvalidArgumentException('Request Object signature algorithm not defined for the client.');
        }
        if ($jws->getSignature($index)->getProtectedHeaderParameter('alg') !== $client->get('request_object_signing_alg')) {
            throw new \InvalidArgumentException('Request Object signature algorithm not supported by the client.');
        }
    }

    /**
     * @param $url
     *
     * @return string
     *
     * @throws OAuth2Exception
     * @throws \Exception
     * @throws \Http\Client\Exception
     */
    private function downloadContent($url): string
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
}
