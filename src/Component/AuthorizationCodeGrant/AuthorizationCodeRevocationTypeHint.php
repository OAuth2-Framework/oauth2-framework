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

namespace OAuth2Framework\Component\AuthorizationCodeGrant;

use OAuth2Framework\Component\Core\Token\Token;
use OAuth2Framework\Component\TokenRevocationEndpoint\TokenTypeHint;

final class AuthorizationCodeRevocationTypeHint implements TokenTypeHint
{
    /**
     * @var AuthorizationCodeRepository
     */
    private $authorizationCodeRepository;

    public function __construct(AuthorizationCodeRepository $authorizationCodeRepository)
    {
        $this->authorizationCodeRepository = $authorizationCodeRepository;
    }

    public function hint(): string
    {
        return 'auth_code';
    }

    public function find(string $token): ?Token
    {
        $id = new AuthorizationCodeId($token);

        return $this->authorizationCodeRepository->find($id);
    }

    public function revoke(Token $token): void
    {
        if (!$token instanceof AuthorizationCode) {
            throw new \InvalidArgumentException('The token is not a valid authorization code.');
        }
        if ($token->isRevoked()) {
            return;
        }

        $token->markAsRevoked();
        $this->authorizationCodeRepository->save($token);
    }
}
