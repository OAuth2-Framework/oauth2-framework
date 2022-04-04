<?php

declare(strict_types=1);

namespace OAuth2Framework\SecurityBundle\Security\Authentication;

use OAuth2Framework\Component\Core\AccessToken\AccessToken;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\CredentialsInterface;

final class AccessTokenBadge implements CredentialsInterface
{
    public function __construct(
        private readonly AccessToken $accessToken
    ) {
    }

    public static function create(AccessToken $accessToken): static
    {
        return new self($accessToken);
    }

    public function isResolved(): bool
    {
        return ! $this->accessToken->isRevoked() && ! $this->accessToken->hasExpired();
    }

    public function getAccessToken(): AccessToken
    {
        return $this->accessToken;
    }
}
