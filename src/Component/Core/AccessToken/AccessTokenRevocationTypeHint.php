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

namespace OAuth2Framework\Component\Core\AccessToken;

use OAuth2Framework\Component\TokenRevocationEndpoint\TokenTypeHint;

final class AccessTokenRevocationTypeHint implements TokenTypeHint
{
    /**
     * @var AccessTokenRepository
     */
    private $accessTokenRepository;

    /**
     * AccessToken constructor.
     */
    public function __construct(AccessTokenRepository $accessTokenRepository)
    {
        $this->accessTokenRepository = $accessTokenRepository;
    }

    public function hint(): string
    {
        return 'access_token';
    }

    public function find(string $token): ?AccessToken
    {
        $id = new AccessTokenId($token);

        return $this->accessTokenRepository->find($id);
    }

    public function revoke($token): void
    {
        if (!$token instanceof AccessToken || true === $token->isRevoked()) {
            return;
        }
        $token->markAsRevoked();
        $this->accessTokenRepository->save($token);
    }
}
