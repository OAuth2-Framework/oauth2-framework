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

namespace OAuth2Framework\Component\Server\TokenTypeHint;

use OAuth2Framework\Component\Server\Model\AccessToken\AccessToken;
use OAuth2Framework\Component\Server\Model\AccessToken\AccessTokenId;
use OAuth2Framework\Component\Server\Model\AccessToken\AccessTokenRepositoryInterface;
use OAuth2Framework\Component\Server\Model\Token\Token;

final class AccessTokenTypeHint implements TokenTypeHintInterface
{
    /**
     * @var AccessTokenRepositoryInterface
     */
    private $accessTokenRepository;

    /**
     * AccessToken constructor.
     *
     * @param AccessTokenRepositoryInterface $accessTokenRepository
     */
    public function __construct(AccessTokenRepositoryInterface $accessTokenRepository)
    {
        $this->accessTokenRepository = $accessTokenRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function hint(): string
    {
        return 'access_token';
    }

    /**
     * {@inheritdoc}
     */
    public function find(string $token)
    {
        $id = AccessTokenId::create($token);

        return $this->accessTokenRepository->find($id);
    }

    /**
     * {@inheritdoc}
     */
    public function revoke(Token $token)
    {
        if (!$token instanceof AccessToken || true === $token->isRevoked()) {
            return;
        }
        $token = $token->markAsRevoked();
        $this->accessTokenRepository->save($token);
    }

    /**
     * {@inheritdoc}
     */
    public function introspect(Token $token): array
    {
        if (!$token instanceof AccessToken || true === $token->isRevoked()) {
            return [
                'active' => false,
            ];
        }

        $values = [
            'active' => !$token->hasExpired(),
            'client_id' => $token->getClientId(),
            'resource_owner' => $token->getResourceOwnerId(),
            'expires_in' => $token->getExpiresIn(),
        ];
        if (!empty($token->getScopes())) {
            $values['scope'] = implode(' ', $token->getScopes());
        }

        return $values + $token->getParameters()->all();
    }
}
