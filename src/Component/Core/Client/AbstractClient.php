<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\Core\Client;

use function in_array;
use InvalidArgumentException;
use function is_array;
use OAuth2Framework\Component\Core\Client\Client as ClientInterface;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\ResourceOwner\ResourceOwnerId;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;

/**
 * This class is used for every client types. A client is a resource owner with a set of allowed grant types and can
 * perform requests against available endpoints.
 */
abstract class AbstractClient implements ClientInterface
{
    protected bool $deleted;

    public function __construct(
        protected DataBag $parameter,
        protected ?UserAccountId $ownerId
    ) {
        $this->deleted = false;
    }

    public function getPublicId(): ResourceOwnerId
    {
        return $this->getClientId();
    }

    public function getOwnerId(): ?UserAccountId
    {
        return $this->ownerId;
    }

    public function setParameter(DataBag $parameter): static
    {
        $this->parameter = $parameter;

        return $this;
    }

    public function markAsDeleted(): static
    {
        $this->deleted = true;

        return $this;
    }

    public function isDeleted(): bool
    {
        return $this->deleted;
    }

    public function isGrantTypeAllowed(string $grant_type): bool
    {
        $grant_types = $this->has('grant_types') ? $this->get('grant_types') : [];
        if (! is_array($grant_types)) {
            throw new InvalidArgumentException('The metadata "grant_types" must be an array.');
        }

        return in_array($grant_type, $grant_types, true);
    }

    public function isResponseTypeAllowed(string $response_type): bool
    {
        $response_types = $this->has('response_types') ? $this->get('response_types') : [];
        if (! is_array($response_types)) {
            throw new InvalidArgumentException('The metadata "response_types" must be an array.');
        }

        return in_array($response_type, $response_types, true);
    }

    public function isPublic(): bool
    {
        return $this->getTokenEndpointAuthenticationMethod() === 'none';
    }

    public function getTokenEndpointAuthenticationMethod(): string
    {
        if ($this->has('token_endpoint_auth_method')) {
            return $this->get('token_endpoint_auth_method');
        }

        return 'client_secret_basic';
    }

    public function getClientCredentialsExpiresAt(): int
    {
        if ($this->has('client_secret_expires_at')) {
            return $this->get('client_secret_expires_at');
        }

        return 0;
    }

    public function areClientCredentialsExpired(): bool
    {
        if ($this->getClientCredentialsExpiresAt() === 0) {
            return false;
        }

        return time() > $this->getClientCredentialsExpiresAt();
    }

    public function has(string $key): bool
    {
        return $this->parameter->has($key);
    }

    public function get(string $key)
    {
        if (! $this->has($key)) {
            throw new InvalidArgumentException(sprintf('Configuration value with key "%s" does not exist.', $key));
        }

        return $this->parameter->get($key);
    }

    public function all(): array
    {
        $all = $this->parameter->all();
        $all['client_id'] = $this->getPublicId()->getValue();

        return $all;
    }
}
