<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\AuthorizationCodeGrant;

use function array_key_exists;
use Assert\Assertion;
use DateTimeImmutable;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\ResourceServer\ResourceServerId;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;

abstract class AbstractAuthorizationCode implements AuthorizationCode
{
    private array $queryParameters;

    private bool $used;

    private bool $revoked;

    public function __construct(
        private ClientId $clientId,
        private UserAccountId $userAccountId,
        array $queryParameters,
        private string $redirectUri,
        private DateTimeImmutable $expiresAt,
        private DataBag $parameter,
        private DataBag $metadata,
        private ?ResourceServerId $resourceServerId
    ) {
        $this->queryParameters = $queryParameters;
        $this->used = false;
        $this->revoked = false;
    }

    public function isUsed(): bool
    {
        return $this->used;
    }

    public function markAsUsed(): self
    {
        $this->used = true;

        return $this;
    }

    public function getQueryParameters(): array
    {
        return $this->queryParameters;
    }

    public function getQueryParameter(string $key): mixed
    {
        Assertion::true($this->hasQueryParameter($key), sprintf('Query parameter with key "%s" does not exist.', $key));

        return $this->queryParameters[$key];
    }

    public function hasQueryParameter(string $key): bool
    {
        return array_key_exists($key, $this->getQueryParameters());
    }

    public function getRedirectUri(): string
    {
        return $this->redirectUri;
    }

    public function toArray(): array
    {
        return [
            'code' => $this->getId()
                ->getValue(),
        ];
    }

    public function getExpiresAt(): DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function hasExpired(): bool
    {
        return $this->expiresAt->getTimestamp() < time();
    }

    public function getUserAccountId(): UserAccountId
    {
        return $this->userAccountId;
    }

    public function getClientId(): ClientId
    {
        return $this->clientId;
    }

    public function getParameter(): DataBag
    {
        return $this->parameter;
    }

    public function getMetadata(): DataBag
    {
        return $this->metadata;
    }

    public function getResourceServerId(): ?ResourceServerId
    {
        return $this->resourceServerId;
    }

    public function getExpiresIn(): int
    {
        return $this->expiresAt->getTimestamp() - time() < 0 ? 0 : $this->expiresAt->getTimestamp() - time();
    }

    public function isRevoked(): bool
    {
        return $this->revoked;
    }

    public function markAsRevoked(): self
    {
        $this->revoked = true;

        return $this;
    }
}
