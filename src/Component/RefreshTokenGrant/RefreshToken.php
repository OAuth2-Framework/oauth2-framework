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

use OAuth2Framework\Component\Core\AccessToken\AccessTokenId;
use OAuth2Framework\Component\Core\Token\Token;
use OAuth2Framework\Component\Core\Token\TokenId;

class RefreshToken extends Token
{
    /**
     * @var AccessTokenId[]
     */
    private $accessTokenIds = [];

    public function setTokenId(TokenId $tokenId): void
    {
        if (!$tokenId instanceof RefreshTokenId) {
            throw new \RuntimeException('The token ID must be an Refresh Token ID.');
        }
        parent::setTokenId($tokenId);
    }

    public function addAccessToken(AccessTokenId $accessTokenId): void
    {
        $id = $accessTokenId->getValue();
        if (!\array_key_exists($id, $this->accessTokenIds)) {
            $this->accessTokenIds[$id] = $accessTokenId;
        }
    }

    /**
     * @return AccessTokenId[]
     */
    public function getAccessTokenIds(): array
    {
        return $this->accessTokenIds;
    }

    public function getResponseData(): array
    {
        $data = $this->getParameter();
        $data->set('access_token', $this->getTokenId()->getValue());
        $data->set('expires_in', $this->getExpiresIn());
        if (!empty($this->getTokenId())) {
            $data->set('refresh_token', $this->getTokenId());
        }

        return $data->all();
    }

    public function jsonSerialize()
    {
        $data = parent::jsonSerialize() + [
            'refresh_token_id' => $this->getTokenId()->getValue(),
            'access_token_ids' => \array_keys($this->getAccessTokenIds()),
            'resource_server_id' => $this->getResourceServerId() ? $this->getResourceServerId()->getValue() : null,
        ];

        return $data;
    }
}
