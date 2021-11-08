<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\TestBundle\Repository;

use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationRequest\AuthorizationRequest;
use OAuth2Framework\Component\NoneGrant\AuthorizationStorage as AuthorizationStorageInterface;

final class AuthorizationRepository implements AuthorizationStorageInterface
{
    /**
     * @var AuthorizationRequest[]
     */
    private array $authorizations = [];

    public static function create(): self
    {
        return new self();
    }

    public function save(AuthorizationRequest $authorization): void
    {
        $this->authorizations[] = $authorization;
    }

    /**
     * @return AuthorizationRequest[]
     */
    public function getAuthorizations(): array
    {
        return $this->authorizations;
    }
}
