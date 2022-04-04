<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\Core\AccessToken;

use OAuth2Framework\Component\TokenRevocationEndpoint\TokenTypeHint;

final class AccessTokenRevocationTypeHint implements TokenTypeHint
{
    public function __construct(
        private readonly AccessTokenRepository $accessTokenRepository
    ) {
    }

    public static function create(AccessTokenRepository $accessTokenRepository): static
    {
        return new self($accessTokenRepository);
    }

    public function hint(): string
    {
        return 'access_token';
    }

    public function find(string $token): ?AccessToken
    {
        $id = AccessTokenId::create($token);

        return $this->accessTokenRepository->find($id);
    }

    public function revoke(mixed $token): void
    {
        if (! $token instanceof AccessToken || $token->isRevoked() === true) {
            return;
        }
        $token->markAsRevoked();
        $this->accessTokenRepository->save($token);
    }
}
