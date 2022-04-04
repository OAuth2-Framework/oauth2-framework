<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\Component\ClientRegistrationEndpoint;

use DateTimeImmutable;
use OAuth2Framework\Component\ClientRegistrationEndpoint\AbstractInitialAccessToken;
use OAuth2Framework\Component\ClientRegistrationEndpoint\InitialAccessTokenId;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;

final class InitialAccessToken extends AbstractInitialAccessToken
{
    public function __construct(
        private readonly InitialAccessTokenId $id,
        ?UserAccountId $userAccountId,
        ?DateTimeImmutable $expiresAt
    ) {
        parent::__construct($userAccountId, $expiresAt);
    }

    public static function create(
        InitialAccessTokenId $id,
        ?UserAccountId $userAccountId,
        ?DateTimeImmutable $expiresAt
    ): self {
        return new self($id, $userAccountId, $expiresAt);
    }

    public function getId(): InitialAccessTokenId
    {
        return $this->id;
    }
}
