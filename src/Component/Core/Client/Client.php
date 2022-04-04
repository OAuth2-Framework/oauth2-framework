<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\Core\Client;

use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\ResourceOwner\ResourceOwner;
use OAuth2Framework\Component\Core\ResourceOwner\ResourceOwnerId;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;

/**
 * This interface is used for every client types. A client is a resource owner with a set of allowed grant types and can
 * perform requests against available endpoints.
 */
interface Client extends ResourceOwner
{
    public function getClientId(): ClientId;

    public function getOwnerId(): ?UserAccountId;

    public function setParameter(DataBag $parameter): static;

    public function markAsDeleted(): static;

    public function isDeleted(): bool;

    public function isGrantTypeAllowed(string $grant_type): bool;

    public function isResponseTypeAllowed(string $response_type): bool;

    public function isPublic(): bool;

    public function getTokenEndpointAuthenticationMethod(): string;

    public function getClientCredentialsExpiresAt(): int;

    public function areClientCredentialsExpired(): bool;

    public function getPublicId(): ResourceOwnerId;

    public function has(string $key): bool;

    /**
     * @return mixed|null
     */
    public function get(string $key);

    public function all(): array;
}
