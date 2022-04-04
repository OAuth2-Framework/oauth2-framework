<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\RefreshTokenGrant;

use function array_key_exists;
use DateTimeImmutable;
use OAuth2Framework\Component\Core\AccessToken\AccessTokenId;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\ResourceOwner\ResourceOwnerId;
use OAuth2Framework\Component\Core\ResourceServer\ResourceServerId;

abstract class AbstractRefreshToken implements RefreshToken
{
    /**
     * @var AccessTokenId[]
     */
    private array $accessTokenIds = [];

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

    public function addAccessToken(AccessTokenId $accessTokenId): static
    {
        $id = $accessTokenId->getValue();
        if (! array_key_exists($id, $this->accessTokenIds)) {
            $this->accessTokenIds[$id] = $accessTokenId;
        }

        return $this;
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
        $data->set('access_token', $this->getId()->getValue());
        $data->set('expires_in', $this->getExpiresIn());
        $data->set('refresh_token', $this->getId());

        return $data->all();
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
}
