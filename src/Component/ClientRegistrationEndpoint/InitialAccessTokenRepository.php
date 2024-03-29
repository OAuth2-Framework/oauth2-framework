<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\ClientRegistrationEndpoint;

interface InitialAccessTokenRepository
{
    public function save(InitialAccessToken $initialAccessToken): void;

    /**
     * This function verifies the request and validate or not the initial access token. MUST return null if the initial
     * access token is not valid (expired, revoked...).
     *
     * @param InitialAccessTokenId $initialAccessTokenId The initial access token
     *
     * @return InitialAccessToken|null Return the initial access token or null if the argument is not a valid initial access token
     */
    public function find(InitialAccessTokenId $initialAccessTokenId): ?InitialAccessToken;
}
