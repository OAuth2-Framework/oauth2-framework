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
use OAuth2Framework\Component\TokenIntrospectionEndpoint\TokenTypeHint;

class RefreshTokenIntrospectionTypeHint implements TokenTypeHint
{
    /**
     * @var RefreshTokenRepository
     */
    private $refreshTokenRepository;

    /**
     * RefreshTokenIntrospectionTypeHint constructor.
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
    public function hint(): string
    {
        return 'refresh_token';
    }

    /**
     * {@inheritdoc}
     */
    public function find(string $token): ?Token
    {
        $id = RefreshTokenId::create($token);

        return $this->refreshTokenRepository->find($id);
    }

    /**
     * {@inheritdoc}
     */
    public function introspect(Token $token): array
    {
        if (!$token instanceof RefreshToken || $token->isRevoked() || $token->hasExpired()) {
            return [
                'active' => false,
            ];
        }

        $result = [
            'active' => true,
            'client_id' => $token->getClientId(),
            'exp' => $token->getExpiresAt()->getTimestamp(),
        ];
        if ($token->hasParameter('scope')) {
            $result['scp'] = $token->getParameter('scope');
        }

        return $result;
    }
}
