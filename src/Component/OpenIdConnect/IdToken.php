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

namespace OAuth2Framework\Component\OpenIdConnect;

use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;

class IdToken
{
    /**
     * @var IdTokenId
     */
    private $idTokenId;

    /**
     * @var array
     */
    private $claims;

    public function __construct(IdTokenId $idTokenId, array $claims)
    {
        $this->idTokenId = $idTokenId;
        $this->claims = $claims;
    }

    public function getId(): IdTokenId
    {
        return $this->idTokenId;
    }

    public function getNonce(): ?string
    {
        return \array_key_exists('nonce', $this->claims) ? $this->claims['nonce'] : null;
    }

    public function getAccessTokenHash(): ?string
    {
        return \array_key_exists('at_hash', $this->claims) ? $this->claims['at_hash'] : null;
    }

    public function getAuthorizationCodeHash(): ?string
    {
        return \array_key_exists('c_hash', $this->claims) ? $this->claims['c_hash'] : null;
    }

    public function getClientId(): ClientId
    {
        if (!\array_key_exists('aud', $this->claims)) {
            throw new \InvalidArgumentException('Invalid ID Token.');
        }

        return new ClientId($this->claims['aud']);
    }

    public function getUserAccountId(): UserAccountId
    {
        if (!\array_key_exists('sub', $this->claims)) {
            throw new \InvalidArgumentException('Invalid ID Token.');
        }

        return new UserAccountId($this->claims['sub']);
    }

    public function getExpiresAt(): \DateTimeImmutable
    {
        if (!\array_key_exists('exp', $this->claims)) {
            throw new \InvalidArgumentException('Invalid ID Token.');
        }

        return new \DateTimeImmutable((string) $this->claims['exp']);
    }

    public function hasExpired(): bool
    {
        return $this->getExpiresAt()->getTimestamp() < time();
    }

    public function getExpiresIn(): int
    {
        return $this->getExpiresAt()->getTimestamp() - time() < 0 ? 0 : $this->getExpiresAt()->getTimestamp() - time();
    }

    public function getClaims(): array
    {
        return $this->claims;
    }
}
