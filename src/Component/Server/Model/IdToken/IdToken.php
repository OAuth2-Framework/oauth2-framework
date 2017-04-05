<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2017 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\Component\Server\Model\IdToken;

use Assert\Assertion;
use OAuth2Framework\Component\Server\Model\Client\ClientId;
use OAuth2Framework\Component\Server\Model\UserAccount\UserAccountId;

final class IdToken
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
    public static function create(IdTokenId $idTokenId, array $claims): IdToken
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
        Assertion::keyExists($this->claims, 'aud', 'Invalid ID Token.');

        return ClientId::create($this->claims['aud']);
    }

    /**
     * @return UserAccountId
     */
    public function getUserAccountId(): UserAccountId
    {
        Assertion::keyExists($this->claims, 'sub', 'Invalid ID Token.');

        return UserAccountId::create($this->claims['sub']);
    }

    /**
     * @return \DateTimeImmutable
     */
    public function getExpiresAt(): \DateTimeImmutable
    {
        Assertion::keyExists($this->claims, 'exp', 'Invalid ID Token.');

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
