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

namespace OAuth2Framework\Component\RefreshTokenGrant;

use OAuth2Framework\Component\Core\Token\Token;
use OAuth2Framework\Component\TokenRevocationEndpoint\TokenTypeHint;

final class RefreshTokenRevocationTypeHint implements TokenTypeHint
{
    /**
     * @var RefreshTokenRepository
     */
    private $refreshTokenRepository;

    public function __construct(RefreshTokenRepository $refreshTokenRepository)
    {
        $this->refreshTokenRepository = $refreshTokenRepository;
    }

    public function hint(): string
    {
        return 'refresh_token';
    }

    public function find(string $token): ?RefreshToken
    {
        $id = new RefreshTokenId($token);

        return $this->refreshTokenRepository->find($id);
    }

    public function revoke($token): void
    {
        if (!$token instanceof RefreshToken || true === $token->isRevoked()) {
            return;
        }

        $token->markAsRevoked();
        $this->refreshTokenRepository->save($token);
    }
}
