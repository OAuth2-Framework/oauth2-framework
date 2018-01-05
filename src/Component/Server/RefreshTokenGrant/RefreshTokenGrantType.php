<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2017 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\Component\Server\RefreshTokenGrant;

use OAuth2Framework\Component\Server\TokenEndpoint\GrantTypeData;
use OAuth2Framework\Component\Server\Core\Client\Client;
use OAuth2Framework\Component\Server\Core\Response\OAuth2Exception;
use OAuth2Framework\Component\Server\TokenEndpoint\GrantType;
use Psr\Http\Message\ServerRequestInterface;

final class RefreshTokenGrantType implements GrantType
{
    /**
     * @var RefreshTokenRepository
     */
    private $refreshTokenRepository;

    /**
     * RefreshTokenGrantType constructor.
     *
     * @param RefreshTokenRepository $refreshTokenRepository
     */
    public function __construct(RefreshTokenRepository $refreshTokenRepository)
    {
        $this->refreshTokenRepository = $refreshTokenRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function getAssociatedResponseTypes(): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getGrantType(): string
    {
        return 'refresh_token';
    }

    /**
     * {@inheritdoc}
     */
    public function checkTokenRequest(ServerRequestInterface $request)
    {
        $parameters = $request->getParsedBody() ?? [];
        $requiredParameters = ['refresh_token'];

        $diff = array_diff($requiredParameters, array_keys($parameters));
        if (!empty($diff)) {
            throw new OAuth2Exception(400, OAuth2Exception::ERROR_INVALID_REQUEST, sprintf('Missing grant type parameter(s): %s.', implode(', ', $diff)));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function prepareTokenResponse(ServerRequestInterface $request, GrantTypeData $grantTypeData): GrantTypeData
    {
        $parameters = $request->getParsedBody() ?? [];
        $refreshToken = $parameters['refresh_token'];
        $token = $this->refreshTokenRepository->find(RefreshTokenId::create($refreshToken));

        if (null === $token) {
            throw new OAuth2Exception(400, OAuth2Exception::ERROR_INVALID_GRANT, 'The parameter "refresh_token" is invalid.');
        }
        $grantTypeData = $grantTypeData->withAvailableScopes($token->getScopes());

        return $grantTypeData;
    }

    /**
     * {@inheritdoc}
     */
    public function grant(ServerRequestInterface $request, GrantTypeData $grantTypeData): GrantTypeData
    {
        $parameters = $request->getParsedBody() ?? [];
        $refreshToken = $parameters['refresh_token'];
        $token = $this->refreshTokenRepository->find(RefreshTokenId::create($refreshToken));

        $client = $request->getAttribute('client');
        $this->checkRefreshToken($token, $client);

        $grantTypeData = $grantTypeData->withResourceOwnerId($token->getResourceOwnerId());
        foreach ($token->getMetadatas()->all() as $k => $v) {
            $grantTypeData = $grantTypeData->withMetadata($k, $v);
        }
        foreach ($token->getParameters()->all() as $k => $v) {
            $grantTypeData = $grantTypeData->withParameter($k, $v);
        }

        return $grantTypeData;
    }

    /**
     * {@inheritdoc}
     */
    private function checkRefreshToken(RefreshToken $token, Client $client)
    {
        if (true === $token->isRevoked() || $client->getPublicId()->getValue() !== $token->getClientId()->getValue()) {
            throw new OAuth2Exception(400, OAuth2Exception::ERROR_INVALID_GRANT, 'The parameter "refresh_token" is invalid.');
        }

        if ($token->hasExpired()) {
            throw new OAuth2Exception(400, OAuth2Exception::ERROR_INVALID_GRANT, 'Refresh token has expired.');
        }
    }
}
