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

use OAuth2Framework\Component\Server\Model\InitialAccessToken\InitialAccessTokenRepositoryInterface;

final class CreateInitialAccessTokenCommandHandler
{
    /**
     * @var InitialAccessTokenRepositoryInterface
     */
    private $initialAccessTokenManager;

    /**
     * CreateInitialAccessTokenCommandHandler constructor.
     *
     * @param InitialAccessTokenRepositoryInterface $initialAccessTokenManager
     */
    public function __construct(InitialAccessTokenRepositoryInterface $initialAccessTokenManager)
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
