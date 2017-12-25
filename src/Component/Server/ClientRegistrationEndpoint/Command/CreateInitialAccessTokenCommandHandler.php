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

use OAuth2Framework\Component\Server\ClientRegistrationEndpoint\InitialAccessToken;
use OAuth2Framework\Component\Server\ClientRegistrationEndpoint\InitialAccessTokenRepository;

final class CreateInitialAccessTokenCommandHandler
{
    /**
     * @var InitialAccessTokenRepository
     */
    private $initialAccessTokenManager;

    /**
     * CreateInitialAccessTokenCommandHandler constructor.
     *
     * @param InitialAccessTokenRepository $initialAccessTokenManager
     */
    public function __construct(InitialAccessTokenRepository $initialAccessTokenManager)
    {
        $this->initialAccessTokenManager = $initialAccessTokenManager;
    }

    /**
     * @param CreateInitialAccessTokenCommand $command
     */
    public function handle(CreateInitialAccessTokenCommand $command)
    {
        $initialAccessToken = InitialAccessToken::createEmpty();
        $initialAccessToken = $initialAccessToken->create(
            $command->getInitialAccessTokenId(),
            $command->getUserAccountId(),
            $command->getExpiresAt()
        );
        $this->initialAccessTokenManager->save($initialAccessToken);
    }
}