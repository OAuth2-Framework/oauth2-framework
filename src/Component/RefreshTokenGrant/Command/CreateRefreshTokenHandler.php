<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2018 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\Component\RefreshTokenGrant\Command;

use OAuth2Framework\Component\RefreshTokenGrant\Event\RefreshTokenCreatedEvent;
use OAuth2Framework\Component\RefreshTokenGrant\RefreshToken;
use OAuth2Framework\Component\RefreshTokenGrant\RefreshTokenRepository;
use SimpleBus\SymfonyBridge\Bus\EventBus;

class CreateRefreshTokenHandler
{
    private $refreshTokenRepository;
    private $eventBus;

    public function __construct(RefreshTokenRepository $refreshTokenRepository, EventBus $eventBus)
    {
        $this->refreshTokenRepository = $refreshTokenRepository;
        $this->eventBus = $eventBus;
    }

    public function handle(CreateRefreshToken $command): void
    {
        $refreshToken = $this->refreshTokenRepository->find($command->getRefreshTokenId());
        if ($refreshToken) {
            throw new \InvalidArgumentException(\sprintf('The authorization code/ with ID "%s" already exist.', $command->getRefreshTokenId()->getValue()));
        }

        $refreshToken = new RefreshToken(
            $command->getRefreshTokenId(),
            $command->getResourceOwnerId(),
            $command->getClientId(),
            $command->getParameter(),
            $command->getMetadata(),
            $command->getExpiresAt(),
            $command->getResourceServerId()
        );
        $this->refreshTokenRepository->save($refreshToken);
        $event = new RefreshTokenCreatedEvent(
            $command->getRefreshTokenId(),
            $command->getResourceOwnerId(),
            $command->getClientId(),
            $command->getParameter(),
            $command->getMetadata(),
            $command->getExpiresAt(),
            $command->getResourceServerId()
        );
        $this->eventBus->handle($event);
    }
}
