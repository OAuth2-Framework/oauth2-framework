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

namespace OAuth2Framework\Component\Core\AccessToken;

use OAuth2Framework\Component\TokenIntrospectionEndpoint\TokenTypeHint;

final class AccessTokenIntrospectionTypeHint implements TokenTypeHint
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

    public function introspect($token): array
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
        if (!$token->getParameter()->has('scope')) {
            $values['scope'] = $token->getParameter()->get('scope');
        }

        return $values + $token->getParameter()->all();
    }
}
