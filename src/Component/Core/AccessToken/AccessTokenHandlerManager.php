<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\Core\AccessToken;

class AccessTokenHandlerManager
{
    /**
     * @var AccessTokenHandler[]
     */
    private array $accessTokenHandlers = [];

    public function add(AccessTokenHandler $accessTokenHandler): void
    {
        $this->accessTokenHandlers[] = $accessTokenHandler;
    }

    public function find(AccessTokenId $tokenId): ?AccessToken
    {
        foreach ($this->accessTokenHandlers as $accessTokenHandler) {
            $accessToken = $accessTokenHandler->find($tokenId);
            if ($accessToken !== null) {
                return $accessToken;
            }
        }

        return null;
    }
}
