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

namespace OAuth2Framework\Component\Server\Command\InitialAccessToken;

use OAuth2Framework\Component\Server\Model\InitialAccessToken\InitialAccessTokenManagerInterface;

final class CreateInitialAccessTokenCommandHandler
{
    /**
     * @var InitialAccessTokenManagerInterface
     */
    private $initialAccessTokenManager;

    /**
     * CreateInitialAccessTokenCommandHandler constructor.
     *
     * @param InitialAccessTokenManagerInterface $initialAccessTokenManager
     */
    public function __construct(InitialAccessTokenManagerInterface $initialAccessTokenManager)
    {
        $this->initialAccessTokenManager = $initialAccessTokenManager;
    }

    /**
     * @param CreateInitialAccessTokenCommand $command
     */
    public function handle(CreateInitialAccessTokenCommand $command)
    {
        $initialAccessToken = $this->initialAccessTokenManager->create(
            $command->getUserAccountId(),
            $command->getExpiresAt()
        );
        $this->initialAccessTokenManager->save($initialAccessToken);
    }
}
