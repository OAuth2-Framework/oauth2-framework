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

namespace OAuth2Framework\Component\OpenIdConnect\UserInfoEndpoint;

use function Safe\sprintf;
use Jose\Component\Core\JWKSet;
use Jose\Component\Encryption\JWEBuilder;
use Jose\Component\Signature\JWSBuilder;
use OAuth2Framework\Component\Core\AccessToken\AccessToken;
use OAuth2Framework\Component\Core\Client\Client;
use OAuth2Framework\Component\Core\Client\ClientRepository;
use OAuth2Framework\Component\Core\Message\OAuth2Error;
use OAuth2Framework\Component\Core\UserAccount\UserAccount;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;
use OAuth2Framework\Component\Core\UserAccount\UserAccountRepository;
use OAuth2Framework\Component\OpenIdConnect\IdTokenBuilderFactory;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class UserInfoEndpoint implements MiddlewareInterface
{
    private ?JWKSet $signatureKeys = null;

    private ?JWSBuilder $jwsBuilder = null;

    private ?JWEBuilder $jweBuilder = null;

    private ClientRepository $clientRepository;

    private UserAccountRepository $userAccountRepository;

    private ResponseFactoryInterface $responseFactory;

    private IdTokenBuilderFactory $idTokenBuilderFactory;

    public function __construct(IdTokenBuilderFactory $idTokenBuilderFactory, ClientRepository $clientRepository, UserAccountRepository $userAccountRepository, ResponseFactoryInterface $responseFactory)
    {
        $this->idTokenBuilderFactory = $idTokenBuilderFactory;
        $this->clientRepository = $clientRepository;
        $this->userAccountRepository = $userAccountRepository;
        $this->responseFactory = $responseFactory;
    }

    public function enableSignature(JWSBuilder $jwsBuilder, JWKSet $signatureKeys): void
    {
        $this->jwsBuilder = $jwsBuilder;
        $this->signatureKeys = $signatureKeys;
    }

    public function enableEncryption(JWEBuilder $jweBuilder): void
    {
        $this->jweBuilder = $jweBuilder;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $accessToken = $request->getAttribute('access_token');
        if (!$accessToken instanceof AccessToken) {
            throw new OAuth2Error(400, OAuth2Error::ERROR_INVALID_TOKEN, 'The access token is missing or invalid.');
        }

        $this->checkScope($accessToken);
        $this->checkRedirectUri($accessToken);

        $client = $this->getClient($accessToken);
        $userAccount = $this->getUserAccount($accessToken);
        $idToken = $this->buildUserinfoContent($client, $userAccount, $accessToken, $isJwt);

        $response = $this->responseFactory->createResponse();
        $response->getBody()->write($idToken);
        $headers = ['Content-Type' => sprintf('application/%s; charset=UTF-8', $isJwt ? 'jwt' : 'json'), 'Cache-Control' => 'no-cache, no-store, max-age=0, must-revalidate, private', 'Pragma' => 'no-cache'];
        foreach ($headers as $k => $v) {
            $response = $response->withHeader($k, $v);
        }

        return $response;
    }

    private function buildUserinfoContent(Client $client, UserAccount $userAccount, AccessToken $accessToken, ?bool &$isJwt): string
    {
        $isJwt = false;
        $requestedClaims = $this->getEndpointClaims($accessToken);
        $idTokenBuilder = $this->idTokenBuilderFactory->createBuilder($client, $userAccount, $accessToken->getMetadata()->get('redirect_uri'));

        if (null !== $this->jwsBuilder && null !== $this->signatureKeys && $client->has('userinfo_signed_response_alg')) {
            $isJwt = true;
            $signatureAlgorithm = $client->get('userinfo_signed_response_alg');
            $idTokenBuilder->withSignature($this->jwsBuilder, $this->signatureKeys, $signatureAlgorithm);
        }
        if (null !== $this->jweBuilder && $client->has('userinfo_encrypted_response_alg') && $client->has('userinfo_encrypted_response_enc')) {
            $isJwt = true;
            $keyEncryptionAlgorithm = $client->get('userinfo_encrypted_response_alg');
            $contentEncryptionAlgorithm = $client->get('userinfo_encrypted_response_enc');
            $idTokenBuilder->withEncryption($this->jweBuilder, $keyEncryptionAlgorithm, $contentEncryptionAlgorithm);
        }
        $idTokenBuilder->setAccessToken($accessToken);
        $idTokenBuilder->withRequestedClaims($requestedClaims);
        if ($client->has('require_auth_time') || $client->has('default_max_age')) {
            $idTokenBuilder->withAuthenticationTime();
        }

        return $idTokenBuilder->build();
    }

    private function getEndpointClaims(AccessToken $accessToken): array
    {
        if (!$accessToken->getMetadata()->has('requested_claims')) {
            return [];
        }

        $requested_claims = $accessToken->getMetadata()->get('requested_claims');
        if (true === \array_key_exists('userinfo', $requested_claims)) {
            return $requested_claims['userinfo'];
        }

        return [];
    }

    private function getClient(AccessToken $accessToken): Client
    {
        $clientId = $accessToken->getClientId();
        if (null === $client = $this->clientRepository->find($clientId)) {
            throw OAuth2Error::invalidRequest('Unable to find the client.');
        }

        return $client;
    }

    private function getUserAccount(AccessToken $accessToken): UserAccount
    {
        $userAccountId = $accessToken->getResourceOwnerId();
        if (!$userAccountId instanceof UserAccountId || null === $userAccount = $this->userAccountRepository->find($userAccountId)) {
            throw OAuth2Error::invalidRequest('Unable to find the resource owner.');
        }

        return $userAccount;
    }

    private function checkRedirectUri(AccessToken $accessToken): void
    {
        if (!$accessToken->getMetadata()->has('redirect_uri')) {
            throw new OAuth2Error(400, OAuth2Error::ERROR_INVALID_TOKEN, 'The access token has not been issued through the authorization endpoint and cannot be used.');
        }
    }

    private function checkScope(AccessToken $accessToken): void
    {
        if (!$accessToken->getParameter()->has('scope') || !\in_array('openid', explode(' ', $accessToken->getParameter()->get('scope')), true)) {
            throw new OAuth2Error(400, OAuth2Error::ERROR_INVALID_TOKEN, 'The access token does not contain the "openid" scope.');
        }
    }
}
