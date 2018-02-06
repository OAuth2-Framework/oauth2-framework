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

    /**
     * IdToken constructor.
     *
     * @param IdTokenId $idTokenId
     * @param array     $claims
     */
    private function __construct(IdTokenId $idTokenId, array $claims)
    {
        $this->idTokenId = $idTokenId;
        $this->claims = $claims;
    }

    /**
     * @param IdTokenId $idTokenId
     * @param array     $claims
     *
     * @return IdToken
     */
    public static function create(IdTokenId $idTokenId, array $claims): self
    {
        return new self($idTokenId, $claims);
    }

    /**
     * @return IdTokenId
     */
    public function getId(): IdTokenId
    {
        return $this->idTokenId;
    }

    /**
     * @return null|string
     */
    public function getNonce()
    {
        return array_key_exists('nonce', $this->claims) ? $this->claims['nonce'] : null;
    }

    /**
     * @return null|string
     */
    public function getAccessTokenHash()
    {
        return array_key_exists('at_hash', $this->claims) ? $this->claims['at_hash'] : null;
    }

    /**
     * @return null|string
     */
    public function getAuthorizationCodeHash()
    {
        return array_key_exists('c_hash', $this->claims) ? $this->claims['c_hash'] : null;
    }

    /**
     * @return ClientId
     */
    public function getClientId(): ClientId
    {
        if (!array_key_exists('aud', $this->claims)) {
            throw new \InvalidArgumentException('Invalid ID Token.');
        }

        return ClientId::create($this->claims['aud']);
    }

    /**
     * @return UserAccountId
     */
    public function getUserAccountId(): UserAccountId
    {
        if (!array_key_exists('sub', $this->claims)) {
            throw new \InvalidArgumentException('Invalid ID Token.');
        }

        return UserAccountId::create($this->claims['sub']);
    }

    /**
     * @return \DateTimeImmutable
     */
    public function getExpiresAt(): \DateTimeImmutable
    {
        if (!array_key_exists('exp', $this->claims)) {
            throw new \InvalidArgumentException('Invalid ID Token.');
        }

        return new \DateTimeImmutable((string) $this->claims['exp']);
    }

    /**
     * @return bool
     */
    public function hasExpired(): bool
    {
        return $this->getExpiresAt()->getTimestamp() < time();
    }

    public function getExpiresIn(): int
    {
        return $this->getExpiresAt()->getTimestamp() - time() < 0 ? 0 : $this->getExpiresAt()->getTimestamp() - time();
    }

    /**
     * @return array
     */
    public function getClaims(): array
    {
        return $this->claims;
    }
}
