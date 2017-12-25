<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2017 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\Component\Server\ClientRegistrationEndpoint\Command;

use OAuth2Framework\Component\Server\ClientRegistrationEndpoint\InitialAccessTokenRepository;

final class RevokeInitialAccessTokenCommandHandler
{
    /**
     * @var InitialAccessTokenRepository
     */
    private $initialAccessTokenRepository;

    /**
     * CreateClientCommandHandler constructor.
     *
     * @param InitialAccessTokenRepository $initialAccessTokenRepository
     */
    public function __construct(InitialAccessTokenRepository $initialAccessTokenRepository)
    {
        $this->initialAccessTokenRepository = $initialAccessTokenRepository;
    }

    /**
     * @param RevokeInitialAccessTokenCommand $command
     */
    public function handle(RevokeInitialAccessTokenCommand $command)
    {
        $accessTokenId = $command->getInitialAccessTokenId();
        $accessToken = $this->initialAccessTokenRepository->find($accessTokenId);
        if (null !== $accessToken) {
            $accessToken = $accessToken->markAsRevoked();
            $this->initialAccessTokenRepository->save($accessToken);
        }
    }
}
