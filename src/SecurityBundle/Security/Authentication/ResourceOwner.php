<?php

declare(strict_types=1);

namespace OAuth2Framework\SecurityBundle\Security\Authentication;

use Symfony\Component\Security\Core\User\UserInterface;

final class ResourceOwner implements UserInterface
{
    public function __construct(
        private readonly string $id
    ) {
    }

    public function getRoles(): array
    {
        return [];
    }

    public function getPassword(): ?string
    {
        return null;
    }

    public function getSalt(): ?string
    {
        return null;
    }

    public function eraseCredentials(): void
    {
    }

    public function getUserIdentifier(): string
    {
        return $this->id;
    }

    public function getUsername(): string
    {
        return $this->id;
    }
}
