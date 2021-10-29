<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\TestBundle\Service;

use OAuth2Framework\Component\Core\AccessToken\AccessToken;
use OAuth2Framework\Component\Core\AccessToken\AccessTokenId;

final class AccessTokenHandler implements \OAuth2Framework\Component\Core\AccessToken\AccessTokenHandler
{
    /**
     * @var AccessToken[]
     */
    private array $accessTokens = [];

    public function find(AccessTokenId $tokenId): ?AccessToken
    {
        return $this->accessTokens[$tokenId->getValue()] ?? null;
    }

    public function save(AccessToken $token): void
    {
        $this->accessTokens[$token->getId()->getValue()] = $token;
    }
}
