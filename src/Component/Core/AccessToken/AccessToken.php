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

use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\ResourceOwner\ResourceOwnerId;
use OAuth2Framework\Component\Core\ResourceServer\ResourceServerId;
use OAuth2Framework\Component\Core\Token\Token;
use OAuth2Framework\Component\Core\Token\TokenId;

class AccessToken extends Token
{
    public function __construct(AccessTokenId $accessTokenId, ClientId $clientId, ResourceOwnerId $resourceOwnerId, \DateTimeImmutable $expiresAt, DataBag $parameter, DataBag $metadata, ?ResourceServerId $resourceServerId)
    {
        parent::__construct($accessTokenId, $clientId, $resourceOwnerId, $parameter, $metadata, $expiresAt, $resourceServerId);
    }

    public function setTokenId(TokenId $tokenId): void
    {
        if (!$tokenId instanceof AccessTokenId) {
            throw new \RuntimeException('The token ID must be an Access Token ID.');
        }
        parent::setTokenId($tokenId);
    }

    public function jsonSerialize()
    {
        $data = parent::jsonSerialize() + [
            'access_token_id' => $this->getTokenId()->getValue(),
        ];

        return $data;
    }

    public function getResponseData(): array
    {
        $data = $this->getParameter()->all();
        $data['access_token'] = $this->getTokenId()->getValue();
        $data['expires_in'] = $this->getExpiresIn();

        return $data;
    }
}
