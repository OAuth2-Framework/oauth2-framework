<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\TestBundle\Entity;

use function array_key_exists;
use DateTimeImmutable;
use DateTimeInterface;
use InvalidArgumentException;
use OAuth2Framework\Component\Core\ResourceOwner\ResourceOwnerId;
use OAuth2Framework\Component\Core\UserAccount\UserAccount as UserAccountInterface;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;
use Symfony\Component\Security\Core\User\UserInterface as SymfonyUserInterface;

final class UserAccount implements UserAccountInterface, SymfonyUserInterface
{
    public function __construct(
        private readonly UserAccountId $userAccountId,
        private readonly string $username,
        private readonly array $roles,
        private readonly ?DateTimeImmutable $lastLoginAt,
        private readonly ?DateTimeImmutable $lastUpdateAt,
        private readonly array $data
    ) {
    }

    public static function create(
        UserAccountId $userAccountId,
        string $username,
        array $roles,
        ?DateTimeImmutable $lastLoginAt,
        ?DateTimeImmutable $lastUpdateAt,
        array $data
    ): self {
        return new self($userAccountId, $username, $roles, $lastLoginAt, $lastUpdateAt, $data);
    }

    public function getUserIdentifier(): string
    {
        return $this->userAccountId->getValue();
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

    public function getPassword(): string
    {
        return '';
    }

    public function getSalt(): ?string
    {
        return null;
    }

    public function eraseCredentials(): void
    {
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getUserAccountId(): UserAccountId
    {
        return $this->userAccountId;
    }

    public function getPublicId(): ResourceOwnerId
    {
        return $this->userAccountId;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->data);
    }

    public function get(string $key)
    {
        if (! $this->has($key)) {
            throw new InvalidArgumentException(sprintf('The user account parameter "%s" does not exist.', $key));
        }

        return $this->data[$key];
    }

    public function getLastLoginAt(): ?DateTimeInterface
    {
        return $this->lastLoginAt;
    }

    public function getLastUpdateAt(): ?DateTimeInterface
    {
        return $this->lastUpdateAt;
    }
}
