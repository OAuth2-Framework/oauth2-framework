<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\OpenIdConnect;

use function array_key_exists;
use DateTimeImmutable;
use InvalidArgumentException;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;

class IdToken
{
    private array $claims;

    public function __construct(
        private IdTokenId $idTokenId,
        array $claims
    ) {
        $this->claims = $claims;
    }

    public function getId(): IdTokenId
    {
        return $this->idTokenId;
    }

    public function getNonce(): ?string
    {
        return array_key_exists('nonce', $this->claims) ? $this->claims['nonce'] : null;
    }

    public function getAccessTokenHash(): ?string
    {
        return array_key_exists('at_hash', $this->claims) ? $this->claims['at_hash'] : null;
    }

    public function getAuthorizationCodeHash(): ?string
    {
        return array_key_exists('c_hash', $this->claims) ? $this->claims['c_hash'] : null;
    }

    public function getClientId(): ClientId
    {
        if (! array_key_exists('aud', $this->claims)) {
            throw new InvalidArgumentException('Invalid ID Token.');
        }

        return new ClientId($this->claims['aud']);
    }

    public function getUserAccountId(): UserAccountId
    {
        if (! array_key_exists('sub', $this->claims)) {
            throw new InvalidArgumentException('Invalid ID Token.');
        }

        return new UserAccountId($this->claims['sub']);
    }

    public function getExpiresAt(): DateTimeImmutable
    {
        if (! array_key_exists('exp', $this->claims)) {
            throw new InvalidArgumentException('Invalid ID Token.');
        }

        return new DateTimeImmutable((string) $this->claims['exp']);
    }

    public function hasExpired(): bool
    {
        return $this->getExpiresAt()
            ->getTimestamp() < time();
    }

    public function getExpiresIn(): int
    {
        return $this->getExpiresAt()
            ->getTimestamp() - time() < 0 ? 0 : $this->getExpiresAt()
            ->getTimestamp() - time();
    }

    public function getClaims(): array
    {
        return $this->claims;
    }
}
