<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\Core\AccessToken;

use DateTimeImmutable;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\ResourceOwner\ResourceOwnerId;
use OAuth2Framework\Component\Core\ResourceServer\ResourceServerId;

abstract class AbstractAccessToken implements AccessToken
{
    private bool $revoked;

    public function __construct(
        private ClientId $clientId,
        private ResourceOwnerId $resourceOwnerId,
        private DateTimeImmutable $expiresAt,
        private DataBag $parameter,
        private DataBag $metadata,
        private ?ResourceServerId $resourceServerId
    ) {
        $this->revoked = false;
    }

    public function getExpiresAt(): DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function hasExpired(): bool
    {
        return $this->expiresAt->getTimestamp() < time();
    }

    public function getResourceOwnerId(): ResourceOwnerId
    {
        return $this->resourceOwnerId;
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

    public function isRevoked(): bool
    {
        return $this->revoked;
    }

    public function markAsRevoked(): static
    {
        $this->revoked = true;

        return $this;
    }

    public function getResourceServerId(): ?ResourceServerId
    {
        return $this->resourceServerId;
    }

    public function getExpiresIn(): int
    {
        return $this->expiresAt->getTimestamp() - time() < 0 ? 0 : $this->expiresAt->getTimestamp() - time();
    }

    public function getResponseData(): array
    {
        $data = $this->getParameter()
            ->all()
        ;
        $data['access_token'] = $this->getId()->getValue();
        $data['expires_in'] = $this->getExpiresIn();

        return $data;
    }
}
