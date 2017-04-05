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

namespace OAuth2Framework\Component\Server\GrantType;

use OAuth2Framework\Component\Server\Endpoint\Token\GrantTypeData;
use OAuth2Framework\Component\Server\Model\Client\Client;
use OAuth2Framework\Component\Server\Model\RefreshToken\RefreshToken;
use OAuth2Framework\Component\Server\Model\RefreshToken\RefreshTokenId;
use OAuth2Framework\Component\Server\Model\RefreshToken\RefreshTokenRepositoryInterface;
use OAuth2Framework\Component\Server\Response\OAuth2Exception;
use OAuth2Framework\Component\Server\Response\OAuth2ResponseFactoryManager;
use Psr\Http\Message\ServerRequestInterface;

final class RefreshTokenGrantType implements GrantTypeInterface
{
    /**
     * @var RefreshTokenRepositoryInterface
     */
    private $refreshTokenRepository;

    /**
     * RefreshTokenGrantType constructor.
     *
     * @param RefreshTokenRepositoryInterface $refreshTokenRepository
     */
    public function __construct(RefreshTokenRepositoryInterface $refreshTokenRepository)
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

    public function checkTokenRequest(ServerRequestInterface $request)
    {
        $parameters = $request->getParsedBody() ?? [];
        $requiredParameters = ['refresh_token'];

        foreach ($requiredParameters as $requiredParameter) {
            if (!array_key_exists($requiredParameter, $parameters)) {
                throw new OAuth2Exception(400, ['error' => OAuth2ResponseFactoryManager::ERROR_INVALID_REQUEST, 'error_description' => sprintf('The parameter \'%s\' is missing.', $requiredParameter)]);
            }
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
            throw new OAuth2Exception(400, ['error' => OAuth2ResponseFactoryManager::ERROR_INVALID_GRANT, 'error_description' => 'Invalid refresh token.']);
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
        $grantTypeData = $grantTypeData->withRefreshToken();
        foreach ($token->getMetadatas() as $k => $v) {
            $grantTypeData = $grantTypeData->withMetadata($k, $v);
        }

        return $grantTypeData;
    }

    /**
     * {@inheritdoc}
     */
    public function checkRefreshToken(RefreshToken $token, Client $client)
    {
        if (true === $token->isRevoked() || $client->getPublicId()->getValue() !== $token->getClientId()->getValue()) {
            throw new OAuth2Exception(400, ['error' => OAuth2ResponseFactoryManager::ERROR_INVALID_GRANT, 'error_description' => 'The parameter \'refresh_token\' is invalid.']);
        }

        if ($token->hasExpired()) {
            throw new OAuth2Exception(400, ['error' => OAuth2ResponseFactoryManager::ERROR_INVALID_GRANT, 'error_description' => 'Refresh token has expired.']);
        }
    }
}
